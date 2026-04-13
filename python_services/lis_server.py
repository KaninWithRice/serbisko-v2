from flask import Flask, request, jsonify
from flask_cors import CORS
import threading, requests, time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.firefox.service import Service
from webdriver_manager.firefox import GeckoDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
import urllib3

# Suppress the local SSL warning in the terminal
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

app = Flask(__name__)
CORS(app)

# CONFIG
LIS_URL = "https://depaid.ct.ws/"
CREDS = ("depedsample@gmail.com", "deped123")

def run_check(lrn, expected_grade, webhook, scan_id):
    print(f"[*] Verifying LRN: {lrn}. Looking for: {expected_grade} completer.")
    result = "failed_lis"
    driver = None
    
    try:
        options = webdriver.FirefoxOptions()
        # options.add_argument("--headless") # Disabled headless mode as requested

        driver = webdriver.Firefox(service=Service(GeckoDriverManager().install()), options=options)
        
        driver.get(LIS_URL)
        
        # Explicitly wait up to 15 seconds for the Email field to appear
        wait = WebDriverWait(driver, 15)
        email_input = wait.until(EC.presence_of_element_located((By.XPATH, "//input[@type='email']")))
        
        # Login
        email_input.send_keys(CREDS[0])
        driver.find_element(By.XPATH, "//input[@type='password']").send_keys(CREDS[1])
        driver.find_element(By.XPATH, "//button[@type='submit']").click()
        
        # Click Learner Information System
        lis_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(),'Learner Information System')]")))
        lis_link.click()

        # Navigate
        print("[*] Navigating to Enrol Learner...")
        wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(),'Masterlist')]"))).click()
        wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(),'Enrol Learner')]"))).click()
        
        # Click Proceed and wait for redirect
        print("[*] Clicking Proceed...")
        proceed_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(),'Proceed')]")))
        proceed_btn.click()
        
        # Wait for the search page to load
        print("[*] Waiting for search page...")
        time.sleep(3) # Give it some time to redirect and load
        
        # --- ROBUST SEARCH BOX DETECTION ---
        inp = None
        selectors = [
            "//input[@name='lrn']",
            "//input[@id='lrn']",
            "//input[contains(@placeholder,'Search LRN')]",
            "//input[contains(@placeholder,'LRN')]",
            "//input[contains(@placeholder,'Search')]",
            "//input[@type='text']",
            "//input[not(@type) or @type='text']"
        ]
        
        for selector in selectors:
            try:
                inp = driver.find_element(By.XPATH, selector)
                if inp.is_displayed():
                    print(f"[*] Found search box with selector: {selector}")
                    break
            except:
                continue
        
        if not inp:
            print("❌ Could not find search box with standard selectors. Trying all visible inputs...")
            all_inputs = driver.find_elements(By.TAG_NAME, "input")
            for i in all_inputs:
                if i.is_displayed():
                    inp = i
                    print(f"[*] Using first visible input as search box: {i.get_attribute('outerHTML')}")
                    break

        if inp:
            print(f"[*] Entering LRN: {lrn}")
            driver.execute_script("arguments[0].click();", inp)
            time.sleep(0.5)
            inp.clear()
            inp.send_keys(lrn)
            time.sleep(0.5)
            # Try to press ENTER just in case
            inp.send_keys(Keys.ENTER)
            
            # Click the search button as well
            print("[*] Clicking search button...")
            try:
                # Try sibling button or a button with 'Search' text
                search_btn = None
                btn_selectors = [
                    "./following-sibling::button",
                    "//button[contains(., 'Search')]",
                    "//button[contains(@class, 'search')]",
                    "//button[@type='submit']",
                    "//i[contains(@class, 'search')]/parent::button",
                    "//button[contains(@class, 'btn-primary')]",
                    "//input[@type='submit']",
                    "//button[.//i]", # Any button with an icon
                    "//a[contains(@class, 'btn') and contains(., 'Search')]"
                ]
                
                for b_sel in btn_selectors:
                    try:
                        if b_sel.startswith("./"):
                            search_btn = inp.find_element(By.XPATH, b_sel)
                        else:
                            search_btn = driver.find_element(By.XPATH, b_sel)
                        
                        if search_btn.is_displayed():
                            print(f"[*] Found search button with: {b_sel}")
                            break
                    except:
                        search_btn = None
                        continue
                
                if search_btn:
                    # Move to element and click
                    from selenium.webdriver.common.action_chains import ActionChains
                    actions = ActionChains(driver)
                    actions.move_to_element(search_btn).click().perform()
                    print("[*] Search button clicked via ActionChains.")
                else:
                    # Final fallback: press ENTER on the input again
                    print("[*] No button found, sending RETURN key to input.")
                    inp.send_keys(Keys.RETURN)
            except Exception as e:
                print(f"[*] Warning: Search button click failed, trying RETURN key. {e}")
                inp.send_keys(Keys.RETURN)
        else:
            raise Exception("No input box found on the search page.")
        
        # --- Check Result ---
        try:
            print("[*] Searching for student record...")
            wait_preview = WebDriverWait(driver, 10)
            preview_btn = wait_preview.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(),'Preview')]")))
            
            # Force click using Javascript
            driver.execute_script("arguments[0].click();", preview_btn)
            print("[*] Preview clicked! Waiting for modal to open...")
            
            # Wait for modal text
            wait_modal = WebDriverWait(driver, 10)
            wait_modal.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(),'Most recent enrolment')]")))
            
            time.sleep(1) 
            text = driver.find_element(By.TAG_NAME, "body").text
            
            print("\n=== WHAT SELENIUM SEES ===")
            print(text[:800] + "...") 
            print("==========================\n")
            
            # --- DYNAMIC GRADE CHECK ---
            if expected_grade.lower() in text.lower():
                result = "verified_lis"
                print(f"✅ SUCCESS: {expected_grade} verified successfully in LIS!")
            else:
                result = "failed_lis"
                print(f"❌ FAILED: Grade mismatch. Expected {expected_grade}, but it was not found in the modal.")
                
        except Exception as e:
            result = "failed_lis"
            print(f"❌ LRN not found or Modal failed to open. Error: {e}")
            
        driver.quit()
    except Exception as e:
        print(f"❌ Selenium Error: {e}")
        if driver:
            try:
                driver.quit()
            except:
                pass

    # --- UPDATED: Actively print Laravel's HTTP Response ---
    print(f"[*] Sending webhook to Laravel at: {webhook}")
    try:
        response = requests.post(webhook, json={'scan_id': scan_id, 'result': result}, verify=False)
        
        if response.status_code == 200:
            print("✅ Webhook accepted! Laravel updated the database successfully.")
        else:
            print(f"❌ Webhook REJECTED by Laravel! Status Code: {response.status_code}")
            print(f"Laravel Response: {response.text[:300]}")
    except Exception as e:
        print(f"❌ Webhook Network Error: {e}")

@app.route('/verify', methods=['POST'])
def verify():
    data = request.json
    lrn = data.get('lrn')
    expected_grade = data.get('expected_grade')
    webhook_url = data.get('webhook_url')
    scan_id = data.get('scan_id')

    threading.Thread(target=run_check, args=(lrn, expected_grade, webhook_url, scan_id)).start()
    return jsonify({'status': 'started'})

@app.route('/status', methods=['GET'])
def server_status():
    return jsonify({'status': 'online'})

if __name__ == '__main__':
    print("Starting LIS Verifier on Port 5001...")
    app.run(host='0.0.0.0', port=5001, debug=True)