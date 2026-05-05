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

log("OCR ENGINE v7.0 STARTING...")
log("ULTRA-HD PRE-PROCESSING + MULTI-CANDIDATE LRN EXTRACTION")

# Global reader
reader = easyocr.Reader(['en'], gpu=False)

def clean_text(text):
    text = str(text).lower().replace('ñ', 'n')
    text = re.sub(r'[|\[\]_!/\\(){}:;.\-+=—–]', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()

def normalize_digits(text):
    norm = text.upper()
    replacements = {
        'O': '0', 'D': '0', 'Q': '0', 'U': '0',
        'I': '1', 'L': '1', '|': '1', 'J': '1', 'T': '1',
        'Z': '7', 'S': '5', 'G': '6', 'B': '8', 'E': '8',
        'A': '4', 'Y': '4', 'H': '4', 'K': '4'
    }
    for char, digit in replacements.items():
        norm = norm.replace(char, digit)
    return re.sub(r'[^0-9]', '', norm)

def extract_candidates(text):
    """Extracts all potential 12-digit LRNs from text using both raw and normalized approaches."""
    candidates = []
    
    # 1. Raw extraction
    clean_blob = text.replace(" ", "").replace(":", "").replace("-", "")
    candidates.extend(re.findall(r'\d{12}', clean_blob))
    
    # 2. Normalized extraction
    norm_blob = normalize_digits(text)
    candidates.extend(re.findall(r'\d{12}', norm_blob))
    
    # 3. Fuzzy sequence (10-14 digits)
    potentials = re.findall(r'\d{10,14}', norm_blob)
    for p in potentials:
        if len(p) != 12: candidates.append(p)

    return list(set(candidates))

def fuzzy_match(expected, text):
    if not expected or expected.lower() == 'unknown': return True
    blob = clean_text(text).replace(" ", "")
    parts = clean_text(expected).split()
    for part in parts:
        if len(part) < 3: continue
        p_clean = part.replace(" ", "")
        if p_clean in blob: return True
        window = len(p_clean)
        for i in range(len(blob) - window + 1):
            chunk = blob[i:i+window]
            if difflib.SequenceMatcher(None, p_clean, chunk).ratio() >= 0.55:
                return True
    return False

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files: return jsonify({'success': False, 'error': 'No image'}), 400
    
    doc_type = request.form.get('doc_type', 'generic')
    first_name = request.form.get('first_name', '').lower()
    last_name = request.form.get('last_name', '').lower()
    expected_lrn = request.form.get('expected_lrn', '')
    
    log(f"RECEIVED REQUEST: {doc_type.upper()} | NAME: {first_name} {last_name} | EXPECTED LRN: {expected_lrn}")

    try:
        file = request.files['image']
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None: return jsonify({'success': False, 'error': 'Invalid image format.'}), 400

        # --- SMART HIGH-DEFINITION PRE-PROCESSING ---
        h, w = img.shape[:2]
        # Upscale small images to improve OCR accuracy
        if w < 2000:
            scale = 2000 / w
            img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_LANCZOS4)
        
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Pass 1: Enhanced (CLAHE)
        clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8,8))
        enhanced = clahe.apply(gray)
        
        # Pass 2: Sharp Binarization
        thresh = cv2.adaptiveThreshold(enhanced, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 21, 10)
        
        # Pass 3: Denoised & Sharpened
        denoised = cv2.fastNlMeansDenoising(enhanced, None, 10, 7, 21)
        kernel = np.array([[-1,-1,-1], [-1,9,-1], [-1,-1,-1]])
        sharpened = cv2.filter2D(denoised, -1, kernel)

        # Pass 4: Morphological (Dilation) for thin handwriting
        kernel_dilate = np.ones((2,2), np.uint8)
        dilated = cv2.dilate(thresh, kernel_dilate, iterations=1)

        best_text = ""
        found_doc = False
        all_lrn_candidates = []
        
        # Execute multi-pass OCR
        # P1: Enhanced, P2: Threshold, P3: Sharpened, P4: Dilated
        for p_idx, proc_img in enumerate([enhanced, thresh, sharpened, dilated]):
            p_name = ["ENHANCED", "THRESHOLD", "SHARPENED", "DILATED"][p_idx]
            for r_idx, rot in enumerate([None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_90_COUNTERCLOCKWISE]):
                rotated = proc_img if rot is None else cv2.rotate(proc_img, rot)
                
                # --- PASS A: GENERAL (For Name/Keywords) ---
                results = reader.readtext(rotated, detail=1, paragraph=False)
                text = " ".join([res[1] for res in results]).lower()
                clean_blob = text.replace(" ", "").replace("\n", "")
                
                log(f"OCR ({p_name} @ {r_idx*90}°): Read {len(text)} chars.")
                
                is_match = False
                if 'report' in doc_type or 'sf9' in doc_type:
                    keywords = ['sf9', 'reportcard', 'form9', 'deped', 'republic', 'attendance', 'progress', 'learner', 'schoolform9', 'guardian', 'rating']
                    # Require at least 2 keywords for stronger validation if it's SF9
                    match_count = sum(1 for k in keywords if k in clean_blob)
                    if match_count >= 2: is_match = True
                elif 'birth' in doc_type or 'psa' in doc_type:
                    keywords = ['birth', 'certificate', 'psa', 'nso', 'registry', 'born', 'live', 'republic', 'child', 'mother', 'father']
                    match_count = sum(1 for k in keywords if k in clean_blob)
                    if match_count >= 2: is_match = True
                elif 'enroll' in doc_type:
                    keywords = ['enrollment', 'form', 'registration', 'basic', 'education', 'learner', 'semester', 'track', 'strand']
                    match_count = sum(1 for k in keywords if k in clean_blob)
                    if match_count >= 2: is_match = True
                elif 'moral' in doc_type:
                    keywords = ['good', 'moral', 'character', 'conduct', 'recommendation', 'student', 'school']
                    match_count = sum(1 for k in keywords if k in clean_blob)
                    if match_count >= 2: is_match = True
                else:
                    is_match = True # Fallback for other types
                
                if is_match:
                    found_doc = True
                    if len(text) > len(best_text): best_text = text
                    all_lrn_candidates.extend(extract_candidates(text))

                # --- PASS B: NUMERIC ONLY (Targeted for handwritten digits) ---
                num_results = reader.readtext(rotated, detail=0, allowlist='0123456789', mag_ratio=1.5)
                num_text = " ".join(num_results)
                all_lrn_candidates.extend(extract_candidates(num_text))
            
            # Optimization: If we find a VERY strong match for the expected LRN, we can stop early
            # But ONLY if we also found document keywords
            if expected_lrn and found_doc:
                for candidate in all_lrn_candidates:
                    if difflib.SequenceMatcher(None, expected_lrn, candidate).ratio() >= 0.85:
                        break
            
            if found_doc and any(len(c) == 12 for c in all_lrn_candidates): break

        if not found_doc:
            return jsonify({'success': False, 'error': f"Document mismatch. Ensure it is a valid {doc_type.replace('_', ' ')} and well-lit."})

        # --- MANDATORY NAME VERIFICATION ---
        # All documents (SF9, PSA, etc.) MUST have the student's name.
        name_verified = fuzzy_match(first_name, best_text) and fuzzy_match(last_name, best_text)
        if not name_verified:
            log(f"NAME MISMATCH. Best read snippet: {best_text[:300]}")
            # If SF9, we can be slightly more lenient if LRN is a perfect match, but generally name should be there.
            # But let's keep it strict as per user request to not be "fake".
            return jsonify({'success': False, 'error': f"Name mismatch. Student name '{first_name} {last_name}' not detected on this {doc_type.replace('_', ' ')}."})

        # LRN Processing (Primarily for SF9)
        is_sf9 = 'report' in doc_type or 'sf9' in doc_type
        if is_sf9:
            log(f"BEST TEXT EXTRACTED: {best_text[:500]}")
            
            # Clean and filter candidates
            unique_candidates = list(set(all_lrn_candidates))
            
            best_lrn = None
            # Prioritize the expected LRN if a VERY strong match is found
            if expected_lrn:
                for candidate in unique_candidates:
                    if difflib.SequenceMatcher(None, expected_lrn, candidate).ratio() >= 0.85:
                        log(f"FUZZY MATCH SUCCESS (>=85%): Using expected LRN {expected_lrn} instead of candidate {candidate}")
                        best_lrn = expected_lrn
                        break
            
            # If no strong match to expected_lrn, fall back to standard selection (closest to 12 digits)
            if not best_lrn and unique_candidates:
                # Sort by: how close length is to 12, then if it starts with '000' (common in LRNs)
                sorted_candidates = sorted(unique_candidates, key=lambda x: (abs(len(x)-12), not x.startswith('000')))
                best_lrn = sorted_candidates[0]
            
            if best_lrn:
                log(f"LRN Selected: {best_lrn} (Candidates: {unique_candidates})")
                return jsonify({
                    'success': True, 
                    'lrn': best_lrn, 
                    'candidates': unique_candidates,
                    'message': "Document and LRN Verified!"
                })
            else:
                return jsonify({'success': False, 'error': "Document found, but 12-digit LRN is unreadable."})
            
        return jsonify({'success': True, 'message': "Document Verified!"})

    except Exception as e:
        log(f"ERROR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)
