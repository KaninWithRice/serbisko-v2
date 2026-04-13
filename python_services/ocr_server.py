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

log("OCR ENGINE v6.1 STARTING...")
log("HIGH-FIDELITY IMAGE ENHANCEMENT + ADAPTIVE PASS ENABLED")

# Global reader
reader = easyocr.Reader(['en'], gpu=False)

def clean_text(text):
    text = str(text).lower().replace('ñ', 'n')
    # Strip box lines and symbols common in grids
    text = re.sub(r'[|\[\]_!/\\(){}:;.\-+=—–]', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()

def fuzzy_match(expected, text):
    if not expected or expected.lower() == 'unknown': return True
    blob = clean_text(text).replace(" ", "")
    parts = clean_text(expected).split()
    log(f"Fuzzy Match check: '{expected}' against blob of length {len(blob)}")
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
    
    log(f"RECEIVED REQUEST: {doc_type.upper()} | NAME: {first_name} {last_name}")

    try:
        file = request.files['image']
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        
        if img is None:
            return jsonify({'success': False, 'error': 'Invalid image format.'}), 400

        # --- HIGH-QUALITY PRE-PROCESSING PIPELINE ---
        
        # 1. Resize if too large
        h, w = img.shape[:2]
        if max(h, w) > 3000:
            scale = 3000 / max(h, w)
            img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_LANCZOS4)

        # 2. Denoising
        denoised = cv2.fastNlMeansDenoisingColored(img, None, 10, 10, 7, 21)
        gray = cv2.cvtColor(denoised, cv2.COLOR_BGR2GRAY)
        
        # Pass 1: CLAHE Enhanced
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        enhanced = clahe.apply(gray)

        # Pass 2: Adaptive Thresholding (Stronger for high-contrast text)
        thresh = cv2.adaptiveThreshold(enhanced, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)

        # Pass 3: Sharpened
        kernel = np.array([[-1,-1,-1], [-1,9,-1], [-1,-1,-1]])
        sharpened = cv2.filter2D(enhanced, -1, kernel)

        # Sequential Scanning
        best_text = ""
        found_doc = False
        
        # We try 3 different image versions to catch the best OCR
        for p_idx, proc_img in enumerate([enhanced, thresh, sharpened]):
            if found_doc: break
            p_name = ["ENHANCED", "THRESHOLD", "SHARPENED"][p_idx]
            log(f"Starting Scan Pass: {p_name}")
            
            # Try 4 orientations
            for r_idx, rot in enumerate([None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_180, cv2.ROTATE_90_COUNTERCLOCKWISE]):
                r_name = ["0°", "90°", "180°", "270°"][r_idx]
                rotated = proc_img if rot is None else cv2.rotate(proc_img, rot)
                results = reader.readtext(rotated, detail=0, paragraph=True)
                text = " ".join(results).lower()
                clean_blob = text.replace(" ", "")
                
                log(f"OCR ({p_name} @ {r_name}): Read {len(text)} chars. Snippet: {text[:80]}")
                
                # Logic: Check document type keywords
                is_match = False
                if 'report' in doc_type or 'sf9' in doc_type:
                    keywords = ['sf9', 'reportcard', 'form9', 'deped', 'republicofthephilippines', 'attendance', 'progressreport', 'learnerinformation']
                    if any(k in clean_blob for k in keywords): is_match = True
                elif 'birth' in doc_type or 'psa' in doc_type:
                    keywords = ['birth', 'psa', 'nso', 'registry', 'certificateoflive', 'civilregistrar', 'republicofthephilippines']
                    if any(k in clean_blob for k in keywords): is_match = True
                elif 'enroll' in doc_type:
                    keywords = ['enrollment', 'basiceducation', 'learnerinformation', 'beal', 'personaldata', 'deped']
                    if any(k in clean_blob for k in keywords): is_match = True
                elif 'als' in doc_type:
                    keywords = ['als', 'alternativelearning', 'rating', 'certificateofcompletion', 'deped']
                    if any(k in clean_blob for k in keywords): is_match = True
                elif 'moral' in doc_type:
                    keywords = ['moral', 'character', 'recommendation', 'goodbehavior', 'conduct']
                    if any(k in clean_blob for k in keywords): is_match = True
                else:
                    is_match = True 
                
                if is_match:
                    log(f"DOCUMENT MATCH FOUND ({p_name} @ {r_name})!")
                    best_text = text
                    found_doc = True
                    break
                
                if len(text) > len(best_text): best_text = text

        if not found_doc:
            log("DOCUMENT TYPE MISMATCH - No keywords found in any pass.")
            return jsonify({'success': False, 'error': f"Document mismatch. Ensure the {doc_type.replace('_', ' ').title()} is well-lit and flat."})

        # Step 2: Verify LRN (For Report Card only)
        if 'report' in doc_type or 'sf9' in doc_type:
            clean_blob = best_text.replace(" ", "").replace(":", "").replace("-", "")
            log("Extracting LRN from blob...")
            # Look for 12 digits
            lrn_matches = re.findall(r'\d{12}', clean_blob)
            if lrn_matches:
                lrn = lrn_matches[0]
                log(f"LRN VERIFIED: {lrn}")
                return jsonify({'success': True, 'lrn': lrn, 'message': "SF9 and LRN Verified!"})
            else:
                log("LRN NOT FOUND in text.")
                return jsonify({'success': False, 'error': "SF9 found, but 12-digit LRN is unreadable. Please ensure it is sharp."})
        
        # Step 3: Verify Names
        log("Verifying Student Name...")
        if not fuzzy_match(first_name, best_text):
            log(f"First Name '{first_name}' mismatch.")
            return jsonify({'success': False, 'error': f"Document found, but First Name ({first_name}) missing."})
        if not fuzzy_match(last_name, best_text):
            log(f"Last Name '{last_name}' mismatch.")
            return jsonify({'success': False, 'error': f"Document found, but Last Name ({last_name}) missing."})
            
        log("SUCCESS: Document fully verified.")
        return jsonify({'success': True, 'message': f"{doc_type.replace('_', ' ').title()} Verified!"})

    except Exception as e:
        log(f"FATAL ERROR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)
