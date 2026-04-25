from flask import Flask, request, jsonify
from flask_cors import CORS
import threading
import time
import os
import platform
import sys
import traceback
import random
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.select import Select
from selenium.webdriver.common.keys import Keys
from webdriver_manager.firefox import GeckoDriverManager

app = Flask(__name__)
CORS(app) # Enable CORS for all routes

@app.before_request
def log_request_info():
    print(f"\n[HTTP] {request.method} {request.url}")
    if request.method == 'POST' and request.is_json:
        print(f"[BODY] {request.get_json(silent=True)}")

@app.after_request
def log_response_info(response):
    print(f"[RESPONSE] {response.status_code}")
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response.headers['Access-Control-Allow-Methods'] = 'GET,POST,OPTIONS'
    return response

# Configuration
LIS_URL = "https://depaid.ct.ws/"
LIS_EMAIL = "depedsample@gmail.com"
LIS_PASSWORD = "deped123"

# Global state
active_driver = None
driver_lock = threading.Lock()

def find_firefox_path():
    """Auto-detect Firefox installation path"""
    possible_paths = [
        r"D:\Program Files\Mozilla Firefox\firefox.exe",
        r"C:\Program Files\Mozilla Firefox\firefox.exe",
        r"C:\Program Files (x86)\Mozilla Firefox\firefox.exe",
        os.path.expandvars(r"%ProgramFiles%\Mozilla Firefox\firefox.exe"),
        os.path.expandvars(r"%ProgramFiles(x86)%\Mozilla Firefox\firefox.exe"),
        os.path.expandvars(r"%LOCALAPPDATA%\Mozilla Firefox\firefox.exe"),
    ]
    for path in possible_paths:
        if os.path.exists(path):
            return path
    return None

def get_firefox_options():
    options = Options()
    firefox_path = find_firefox_path()
    if firefox_path:
        options.binary_location = firefox_path
    
    # Speed Optimization
    options.set_preference("permissions.default.image", 2)
    options.set_preference("dom.ipc.plugins.enabled.libflashplayer.so", "false")
    options.set_preference("dom.webdriver.enabled", False)
    options.set_preference("useAutomationExtension", False)
    return options

# --- ROBOT NAVIGATION HELPERS ---

def click_smart(driver, wait, xpaths, name):
    """Robust clicking with multiple XPaths and standard/JS fallback."""
    print(f"[*] Clicking: {name}")
    for xpath in xpaths:
        try:
            el = WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.XPATH, xpath)))
            driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
            try:
                wait.until(EC.element_to_be_clickable((By.XPATH, xpath))).click()
            except:
                driver.execute_script("arguments[0].click();", el)
            return True
        except: continue
    return False

def type_slowly(element, text):
    """Fast but reliable typing."""
    if not element: return
    element.clear()
    for char in str(text):
        element.send_keys(char)
        time.sleep(0.01)
    time.sleep(0.1)

def find_field_by_html(driver, name_or_id):
    selectors = [
        f"//*[@name='{name_or_id}']",
        f"//*[@id='{name_or_id}']",
        f"//input[@type='email']" if name_or_id == 'email' else None,
        f"//*[contains(text(), '{name_or_id}')]/following::input[1]",
        f"//*[contains(text(), '{name_or_id}')]/following::select[1]"
    ]
    for sel in [s for s in selectors if s]:
        try:
            el = driver.find_element(By.XPATH, sel)
            if el.is_displayed() or el.tag_name.lower() == 'select': 
                return el
        except: continue
    return None

def wait_for_options(driver, el, timeout=10):
    try:
        WebDriverWait(driver, timeout).until(
            lambda d: len(Select(el).options) > 1
        )
        return True
    except:
        return False

def select2_set_value(driver, select_el, value):
    try:
        option_data = driver.execute_script("""
            var sel = arguments[0];
            var search = arguments[1].toLowerCase().trim();
            var options = sel.options;
            for (var i = 0; i < options.length; i++) {
                var text = options[i].text.toLowerCase();
                var val = options[i].value.toLowerCase();
                if (text.includes(search) || val === search) {
                    return { id: options[i].value, text: options[i].text };
                }
            }
            return null;
        """, select_el, value)

        if not option_data:
            return False

        print(f"[OK] Found option: {option_data['text']}")
        driver.execute_script("""
            var el = arguments[0];
            var data = arguments[1];
            var $el = window.jQuery(el);
            $el.val(data.id).trigger('change');
            var event = jQuery.Event('select2:select', {
                params: { data: data }
            });
            $el.trigger(event);
        """, select_el, option_data)
        return True
    except Exception as e:
        print(f"[WARN] jQuery/Select2 injection failed: {e}")
        return False

def fill_field_smart(driver, wait, target_name, value, is_dropdown=False):
    if not value: return False
    
    if not is_dropdown and target_name.lower() not in ['email', 'lrn', 'zip']:
        value = str(value).title()
        
    print(f"[*] Handling field '{target_name}' with value '{value}'")
    
    for attempt in range(2):
        try:
            el = find_field_by_html(driver, target_name)
            if not el:
                time.sleep(0.5); continue

            driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
            
            if "select2-hidden-accessible" in el.get_attribute("class") or is_dropdown:
                if wait_for_options(driver, el, timeout=5):
                    if select2_set_value(driver, el, value):
                        time.sleep(0.3)
                        return True
            
            if el.tag_name.lower() == "input":
                type_slowly(el, value)
                driver.execute_script("arguments[0].dispatchEvent(new Event('change', {bubbles:true}));", el)
                return True
                
        except:
            time.sleep(0.5)
            
    return False

# --- MAIN AUTOMATION ---

def automate_enrollment(student_data):
    global active_driver
    print(f"\n[DEBUG] Full Data Received: {json.dumps(student_data, indent=2)}")
    
    lrn = student_data.get('lrn')
    section_name = student_data.get('section_name')
    
    print(f"\n[START] Automation for LRN: {lrn} | Target Section: {section_name}")
    
    try:
        options = get_firefox_options()
        driver = webdriver.Firefox(service=Service(GeckoDriverManager().install()), options=options)
        
        with driver_lock:
            if active_driver:
                try: active_driver.quit()
                except: pass
            active_driver = driver
        
        driver.maximize_window()
        wait = WebDriverWait(driver, 15)

        # 1. Login
        print(f"[*] Navigating to {LIS_URL}")
        driver.get(LIS_URL)
        wait.until(EC.presence_of_element_located((By.NAME, "email"))).send_keys(LIS_EMAIL)
        driver.find_element(By.NAME, "password").send_keys(LIS_PASSWORD)
        driver.find_element(By.XPATH, "//button[contains(.,'Sign in')]").click()
        
        # 2. Navigation
        wait.until(EC.url_contains("index.php"))
        click_smart(driver, wait, ["//a[contains(., 'Learner Information')]"], "LIS Dashboard")
        
        wait.until(EC.presence_of_element_located((By.XPATH, "//a[normalize-space()='Masterlist']")))
        click_smart(driver, wait, ["//a[normalize-space()='Masterlist']"], "Masterlist")
        
        # 3. Section Selection
        print("[*] Selecting Section...")
        try:
            s_el = WebDriverWait(driver, 10).until(lambda d: 
                find_field_by_html(d, "selected_class") or 
                find_field_by_html(d, "section") or 
                find_field_by_html(d, "class")
            )
            
            if s_el:
                target_text = section_name if section_name else "Grade 11"
                print(f"[*] Selecting section: {target_text}")
                
                if select2_set_value(driver, s_el, target_text):
                    print(f"[OK] Section selected.")
                else:
                    sel = Select(s_el)
                    for o in sel.options:
                        if target_text.lower() in o.text.lower():
                            sel.select_by_visible_text(o.text)
                            break
                
                driver.execute_script("arguments[0].dispatchEvent(new Event('change', {bubbles:true}));", s_el)
                time.sleep(1)
        except Exception as e:
            print(f"[WARN] Section skipped: {e}")

        # 4. Enrolment Sequence
        click_smart(driver, wait, ["//a[contains(., 'Enrol Learner')]"], "Enrol Button")
        click_smart(driver, wait, ["//button[contains(., 'Proceed')]", "//a[contains(., 'Proceed')]"], "Proceed")
        
        print(f"[*] Typing LRN: {lrn}")
        inp = wait.until(EC.presence_of_element_located((By.NAME, "lrn")))
        type_slowly(inp, lrn)
        inp.send_keys(Keys.ENTER)
        
        # 5. Preview & Enrollment Form Transition
        click_smart(driver, wait, ["//*[contains(text(), 'Preview')]", "//a[contains(@href, 'profile')]"], "Preview")
        click_smart(driver, wait, ["//*[contains(@class, 'btn-continue')]", "//a[contains(@href, 'final_enrolment')]"], "Continue")
        
        print("[*] Setting Attendance Date...")
        try:
            wait.until(EC.presence_of_all_elements_located((By.XPATH, "//main//select")))
            dropdowns = driver.find_elements(By.XPATH, "//main//select")
            for s_el in dropdowns[:3]:
                sel = Select(s_el)
                if len(sel.options) > 1: sel.select_by_index(1)
            time.sleep(0.5)
        except: pass
        
        click_smart(driver, wait, ["//a[contains(@href, 'detailed_enrolment')]", "//button[contains(., 'Continue')]"], "Final Form")

        # 6. FILLING THE ENROLLMENT FORM
        wait.until(lambda d: "detailed_enrolment" in d.current_url)
        print("\n" + "="*50 + "\n[INFO] FILLING ENROLLMENT FORM\n" + "="*50 + "\n")

        data = {
            "motherTongue": student_data.get('mother_tongue', 'Tagalog'),
            "religion": student_data.get('religion', 'Roman Catholic'),
            "citizenship": student_data.get('citizenship', 'Philippines'),
            "email": student_data.get('email', ''),
            "province": student_data.get('curr_province', ''),
            "city": student_data.get('curr_city', ''),
            "barangay": student_data.get('curr_barangay', ''),
            "zip": student_data.get('curr_zip_code', ''),
            "modality": student_data.get('modality', 'Face to face'),
            "guardianFirstName": student_data.get('guardian_first_name', ''),
            "guardianLastName": student_data.get('guardian_last_name', ''),
            "guardianMiddleName": student_data.get('guardian_middle_name', ''),
            "guardianRelationship": student_data.get('guardian_relationship', 'Relative'),
            "motherFirstName": student_data.get('mother_first_name', ''),
            "motherLastName": student_data.get('mother_last_name', ''),
            "motherMiddleName": student_data.get('mother_middle_name', ''),
            "fatherFirstName": student_data.get('father_first_name', ''),
            "fatherLastName": student_data.get('father_last_name', ''),
            "fatherMiddleName": student_data.get('father_middle_name', ''),
            "fatherExtName": student_data.get('father_extension_name', '')
        }

        # --- CORE INFO ---
        fill_field_smart(driver, wait, "motherTongue", data['motherTongue'])
        fill_field_smart(driver, wait, "religion", data['religion'])
        fill_field_smart(driver, wait, "email", data['email'])
        fill_field_smart(driver, wait, "citizenship", data['citizenship'], True)

        # --- RESIDENCY ---
        for pre in ['current', 'permanent']:
            fill_field_smart(driver, wait, pre + 'Province', data['province'], True)
            fill_field_smart(driver, wait, pre + 'City', data['city'], True)
            fill_field_smart(driver, wait, pre + 'Barangay', data['barangay'], True)
            fill_field_smart(driver, wait, pre + 'Zip', data['zip'], True)

        # --- FAMILY INFO ---
        fill_field_smart(driver, wait, "guardianFirstName", data['guardianFirstName'])
        fill_field_smart(driver, wait, "guardianLastName", data['guardianLastName'])
        fill_field_smart(driver, wait, "guardianMiddleName", data['guardianMiddleName'])
        fill_field_smart(driver, wait, "guardianRelationship", data['guardianRelationship'], True)
        
        fill_field_smart(driver, wait, "motherFirstName", data['motherFirstName'])
        fill_field_smart(driver, wait, "motherLastName", data['motherLastName'])
        fill_field_smart(driver, wait, "motherMiddleName", data['motherMiddleName'])
        
        fill_field_smart(driver, wait, "fatherFirstName", data['fatherFirstName'])
        fill_field_smart(driver, wait, "fatherLastName", data['fatherLastName'])
        fill_field_smart(driver, wait, "fatherMiddleName", data['fatherMiddleName'])
        fill_field_smart(driver, wait, "fatherExtName", data['fatherExtName'])

        # --- ADDITIONAL INFO ---
        fill_field_smart(driver, wait, "modality", data['modality'], True)

        print("\n" + "="*50 + "\n[SUCCESS] ENROLLMENT AUTOMATION COMPLETE\n" + "="*50 + "\n")

    except Exception as e:
        print(f"[ERROR] {e}")
        traceback.print_exc()
    
    # Stay open for inspection
    while active_driver:
        try: _ = active_driver.title; time.sleep(1)
        except: break

@app.route('/fill-enrollment', methods=['POST'])
def fill_enrollment():
    data = request.json
    threading.Thread(target=automate_enrollment, args=(data,), daemon=True).start()
    return jsonify({'status': 'started'})

@app.route('/status', methods=['GET'])
def status():
    return jsonify({'status': 'online', 'driver_active': active_driver is not None})

if __name__ == '__main__':
    print('[INFO] Enrollment Automation Service starting (Firefox Mode)...')
    app.run(host='0.0.0.0', port=5002)
