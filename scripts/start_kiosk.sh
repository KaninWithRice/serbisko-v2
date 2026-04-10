#!/bin/bash

# Configuration
PROJECT_ROOT="/home/serbisko/serbisko-v2"
KIOSK_URL="http://serbisko.local"
LOG_FILE="/home/serbisko/kiosk_autostart.log"

# Ensure we have a display set for Chrome
export DISPLAY=:0
export XDG_RUNTIME_DIR=/run/user/$(id -u)

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    echo "$1"
}

# 0. Initial Delay to let the desktop environment load
log "[*] Waiting 5 seconds for desktop session to settle..."
sleep 5

log "[*] Starting Kiosk Script..."

# 1. Start Python Services (if not running)
log "[*] Starting Python Services..."

# OCR Server (Port 9001)
if ! pgrep -f ocr_server.py > /dev/null; then
    nohup python3 -u $PROJECT_ROOT/python_services/ocr_server.py >> "$LOG_FILE" 2>&1 &
    log "[+] OCR Server started."
fi

# LIS Server (Port 5001)
if ! pgrep -f lis_server.py > /dev/null; then
    nohup python3 -u $PROJECT_ROOT/python_services/lis_server.py >> "$LOG_FILE" 2>&1 &
    log "[+] LIS Server started."
fi

# Arduino Server (Port 51234)
if ! pgrep -f arduino_server_fixed.py > /dev/null; then
    nohup python3 -u $PROJECT_ROOT/scripts/arduino_server_fixed.py >> "$LOG_FILE" 2>&1 &
    log "[+] Fixed Arduino Server started."
fi

# 1.5 Wait for Arduino Server to confirm connection
log "[*] Waiting for Arduino to be ready..."
MAX_HW_RETRIES=10 # Reduced retries for faster boot if disconnected
HW_COUNT=0
while true; do
    # Check if the Arduino server is up and says it's connected
    STATUS_JSON=$(curl -s --max-time 2 http://localhost:51234/status || echo '{"arduino_connected":false}')
    if [[ "$STATUS_JSON" == *'"arduino_connected":true'* ]]; then
        log "[+] Arduino Connected and Server Ready!"
        sleep 1
        break
    fi
    
    if [ $HW_COUNT -ge $MAX_HW_RETRIES ]; then
        log "[-] WARNING: Arduino not detected or Server timed out. Proceeding anyway..."
        break
    fi
    
    log "    (Attempt $((HW_COUNT+1))/$MAX_HW_RETRIES) Waiting for serial connection..."
    sleep 2
    ((HW_COUNT++))
done

# 2. Wait for Web Server (Apache) to be ready
log "[*] Waiting for $KIOSK_URL to be online..."
MAX_RETRIES=15
COUNT=0
while ! curl -s $KIOSK_URL > /dev/null; do
    if [ $COUNT -ge $MAX_RETRIES ]; then
        log "[-] ERROR: Web server timed out. Proceeding anyway..."
        break
    fi
    sleep 2
    ((COUNT++))
done
log "[+] Web Server check complete."

# 3. Launch Chrome in Kiosk Mode
log "[*] Launching Chrome..."
google-chrome \
    --kiosk \
    --incognito \
    --force-device-scale-factor=1.1 \
    --disable-infobars \
    --no-first-run \
    --autoplay-policy=no-user-gesture-required \
    --use-fake-ui-for-media-stream \
    --noerrdialogs \
    --disable-session-crashed-bubble \
    --disable-features=TranslateUI \
    --password-store=basic \
    --disable-pinch \
    --overscroll-history-navigation=0 \
    --window-position=0,0 \
    --window-size=1920,1080 \
    --remote-debugging-port=9222 \
    "$KIOSK_URL" >> "$LOG_FILE" 2>&1 &
log "[+] Chrome launched."
