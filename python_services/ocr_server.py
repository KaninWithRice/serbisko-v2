from flask import Flask, request, jsonify
from flask_cors import CORS
import easyocr
import cv2
import numpy as np
import re
import difflib 
import sys

app = Flask(__name__)
CORS(app)

def log(msg):
    print(f"🟢 [OCR-LOG] {msg}", flush=True)

log("OCR ENGINE v8.2 BOX-AWARE STARTING...")

# Global reader - allow auto-detection of GPU for performance
reader = easyocr.Reader(['en'], gpu=False) 

def clean_text(text):
    # Remove obvious noise and box characters
    text = str(text).lower().replace('ñ', 'n')
    # Replace common box-like characters with space
    text = re.sub(r'[|\[\]_!/\\(){}:;.\-+=—–°ø•*#@]', ' ', text)
    # Remove single characters that look like noise (except 'a' or 'i' in names, though rare in standalone boxes)
    # Actually, for boxed forms, we WANT to keep single characters to join them later.
    return re.sub(r'\s+', ' ', text).strip()

def fuzzy_match(expected, text, threshold=0.6):
    if not expected or expected.lower() == 'unknown': return True
    
    expected = expected.lower().replace(" ", "")
    # Clean the blob but KEEP spaces for segment joining analysis
    blob_with_spaces = clean_text(text)
    blob_no_spaces = blob_with_spaces.replace(" ", "")

    # 1. Standard check (no spaces)
    if expected in blob_no_spaces: return True
    
    # 2. Segment Joining (for boxed letters like "K I N G")
    # We look for the characters of 'expected' with 0-1 noise characters between them
    pattern = ".*?".join([re.escape(char) for char in expected])
    if re.search(pattern, blob_no_spaces):
        log(f"Matched '{expected}' via segment sequence.")
        return True

    # 3. Fuzzy window check
    window = len(expected)
    for i in range(len(blob_no_spaces) - window + 1):
        chunk = blob_no_spaces[i:i+window]
        if difflib.SequenceMatcher(None, expected, chunk).ratio() >= threshold:
            return True
            
    return False

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files: return jsonify({'success': False, 'error': 'No image'}), 400
    
    doc_type = request.form.get('doc_type', 'generic')
    first_name = request.form.get('first_name', '').lower()
    last_name = request.form.get('last_name', '').lower()
    expected_lrn = request.form.get('expected_lrn', '')
    
    log(f"REQUEST: {doc_type.upper()} | NAME: {first_name} {last_name} | EXPECTED LRN: {expected_lrn}")

    try:
        file = request.files['image']
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None: return jsonify({'success': False, 'error': 'Invalid image format.'}), 400

        # Resize for speed
        h, w = img.shape[:2]
        if w > 1800:
            scale = 1800 / w
            img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
        
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Passes (P1: CLAHE, P2: Dilated Threshold)
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        p1 = clahe.apply(gray)
        
        thresh = cv2.adaptiveThreshold(p1, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 31, 10)
        kernel = np.ones((2,2), np.uint8)
        p2 = cv2.dilate(thresh, kernel, iterations=1)

        best_text = ""
        found_doc = False
        all_lrn_candidates = []
        is_sf9 = 'report' in doc_type or 'sf9' in doc_type

        # Multi-pass loop
        for p_idx, proc_img in enumerate([p1, p2]):
            for r_idx, rot in enumerate([None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_90_COUNTERCLOCKWISE]):
                rotated = proc_img if rot is None else cv2.rotate(proc_img, rot)
                
                # Run OCR
                results = reader.readtext(rotated, detail=0, paragraph=False)
                text = " ".join(results).lower()
                clean_blob = text.replace(" ", "").replace("\n", "")
                
                # Document Keyword Check
                is_match = False
                if is_sf9:
                    keywords = ['sf9', 'reportcard', 'form9', 'deped', 'republic', 'attendance', 'learner', 'schoolform9', 'guardian', 'rating']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'birth' in doc_type or 'psa' in doc_type:
                    keywords = ['certificate', 'psa', 'live', 'birth', 'nso', 'registry', 'born', 'republic', 'child', 'mother', 'father']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'enroll' in doc_type:
                    keywords = ['enrollment', 'form', 'annex1', 'basiceducation', 'registration', 'learner', 'semester', 'legibly']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'moral' in doc_type:
                    keywords = ['good', 'moral', 'character', 'conduct', 'recommendation', 'student', 'school']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'affidavit' in doc_type:
                    keywords = ['affidavit', 'sworn', 'statement', 'republic', 'notary', 'legal', 'evidence']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'als' in doc_type:
                    keywords = ['als', 'alternative', 'learning', 'system', 'certificate', 'passer', 'rating', 'elementary', 'secondary']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                else: is_match = True 

                if is_match:
                    found_doc = True
                    if len(text) > len(best_text): best_text = text
                    from python_services.ocr_server import extract_candidates
                    all_lrn_candidates.extend(extract_candidates(text))
                    
                    if expected_lrn:
                        for cand in all_lrn_candidates:
                            if difflib.SequenceMatcher(None, expected_lrn, cand).ratio() >= 0.9:
                                break

                # Numeric pass
                if is_sf9 and not any(len(c) == 12 for c in all_lrn_candidates):
                    num_results = reader.readtext(rotated, detail=0, allowlist='0123456789')
                    from python_services.ocr_server import extract_candidates
                    all_lrn_candidates.extend(extract_candidates(" ".join(num_results)))

                if found_doc:
                    if is_sf9 and any(len(c) == 12 for c in all_lrn_candidates): break
                    elif not is_sf9: break 
            
            if found_doc:
                if is_sf9 and any(len(c) == 12 for c in all_lrn_candidates): break
                elif not is_sf9: break

        if not found_doc:
            return jsonify({'success': False, 'error': f"Document mismatch. Ensure it is a valid {doc_type.replace('_', ' ')}."})

        # Name Verification
        name_threshold = 0.5 if any(x in doc_type for x in ['enroll_form', 'birth_certificate', 'affidavit', 'als_certificate']) else 0.6
        
        name_verified = fuzzy_match(first_name, best_text, threshold=name_threshold) and \
                        fuzzy_match(last_name, best_text, threshold=name_threshold)
        
        if not name_verified:
            log(f"NAME MISMATCH. Snippet: {best_text[:300]}")
            return jsonify({'success': False, 'error': f"Name mismatch. '{first_name} {last_name}' not detected on this {doc_type.replace('_', ' ')}."})

        # LRN Selection
        if is_sf9:
            from python_services.ocr_server import normalize_digits
            unique_candidates = list(set(all_lrn_candidates))
            best_lrn = None
            if expected_lrn:
                for candidate in unique_candidates:
                    if difflib.SequenceMatcher(None, expected_lrn, candidate).ratio() >= 0.67:
                        best_lrn = expected_lrn
                        break
            if not best_lrn and unique_candidates:
                best_lrn = sorted(unique_candidates, key=lambda x: (abs(len(x)-12), not x.startswith('000')))[0]
            
            if best_lrn:
                return jsonify({'success': True, 'lrn': best_lrn, 'message': "Verified!"})
            return jsonify({'success': False, 'error': "LRN unreadable."})
            
        return jsonify({'success': True, 'message': "Verified!"})

    except Exception as e:
        log(f"ERROR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)
