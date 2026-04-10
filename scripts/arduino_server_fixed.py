from flask import Flask, jsonify, request
from flask_cors import CORS
import serial, time
import serial.tools.list_ports
import threading

app = Flask(__name__)
CORS(app)

# ==========================================
# HARDWARE CONNECTION (With Auto-Reconnect)
# ==========================================
print("\n" + "="*50)
print("🤖 SERBISKO HARDWARE CONTROLLER ONLINE!")
print("="*50 + "\n")

def find_arduino_port(baud=9600, timeout=1):
    """Scan available serial ports and try to open one that responds like an Arduino."""
    # Prioritize ACM/USB ports and check descriptions for 'Arduino'
    all_ports = list(serial.tools.list_ports.comports())
    
    # First pass: try ports that explicitly mention 'Arduino'
    for port in all_ports:
        desc = (port.description or "").lower()
        if "arduino" in desc:
            try:
                ser = serial.Serial(port.device, baud, timeout=timeout)
                time.sleep(2)
                if ser.is_open:
                    print(f"🔍 Found Arduino on {port.device} ({port.description})")
                    return ser
            except Exception:
                continue

    # Second pass: try any ACM or USB ports
    for port in all_ports:
        if "ACM" in port.device or "USB" in port.device:
            try:
                ser = serial.Serial(port.device, baud, timeout=timeout)
                time.sleep(2)
                if ser.is_open:
                    print(f"🔍 Found potential Arduino on {port.device} ({port.description})")
                    return ser
            except Exception:
                continue
                
    # Final pass: try anything that isn't a standard ttyS port (fallback to anything if desperate)
    for port in all_ports:
        if "ttyS" in port.device:
            continue
        try:
            ser = serial.Serial(port.device, baud, timeout=timeout)
            time.sleep(2)
            if ser.is_open:
                print(f"🔍 Fallback: Found serial device on {port.device} ({port.description})")
                return ser
        except Exception:
            continue
            
    return None

arduino = find_arduino_port()
if arduino:
    print("✅ Arduino Connected Successfully!")
else:
    print("❌ WARNING: Arduino NOT found. Running in simulation mode.")

def monitor_connection(interval=5):
    global arduino
    while True:
        if arduino and arduino.is_open:
            time.sleep(interval)
            continue
        ser = find_arduino_port()
        if ser:
            arduino = ser
            print("🔁 Arduino reconnected.")
        else:
            print("⏳ Arduino still not available, will retry...")
        time.sleep(interval)

threading.Thread(target=monitor_connection, daemon=True).start()

def send_command(cmd):
    global arduino
    if arduino and getattr(arduino, 'is_open', False):
        try:
            arduino.write((cmd + '\n').encode())
            print(f"📡 Sent to Arduino: [{cmd}]")
            return True
        except Exception as e:
            print(f"⚠️ Error sending to Arduino: {e}. Marking as disconnected.")
            arduino = None
            
    print(f"⚠️ SIMULATED Arduino Command: [{cmd}]")
    return False

# ==========================================
# 1. DOOR / SLOT CONTROLS (Fixes the /api/door 404 error)
# ==========================================
@app.route('/api/door', methods=['POST'])
@app.route('/api/door/<action>', methods=['POST'])
def control_door(action=None):
    # If the frontend just hits /api/door without an action in the URL, try to guess or read the JSON
    if not action:
        data = request.get_json(silent=True) or {}
        action = data.get('action', 'open') # Default to open if frontend doesn't specify
        
    if action.lower() == 'open':
        send_command('r')
        return jsonify({'status': 'success', 'command': 'r', 'message': 'Slot opened'})
    elif action.lower() == 'close':
        send_command('f')
        return jsonify({'status': 'success', 'command': 'f', 'message': 'Slot closed'})
        
    return jsonify({'error': 'Invalid slot action'}), 400

# ==========================================
# 2. CONVEYOR CONTROLS
# ==========================================
@app.route('/api/conveyor/start', methods=['POST'])
def start_conveyor():
    send_command('c0')
    
    def conveyor_timer():
        time.sleep(6)
        send_command('c1')
        print("🛑 Conveyor Auto-Stopped (c1 triggered).")
        
    threading.Thread(target=conveyor_timer).start()
    return jsonify({'status': 'success', 'command': 'c0', 'message': 'Conveyor started. Will auto-stop in 6s.'})

@app.route('/api/conveyor/w', methods=['POST'])
def trigger_w():
    send_command('w')
    
    def delayed_c1():
        time.sleep(10)
        send_command('c1')
        print("🛑 Conveyor Auto-Stopped after W (c1 triggered).")
        
    threading.Thread(target=delayed_c1).start()
    return jsonify({'status': 'success', 'command': 'w', 'message': 'Conveyor W command triggered. c1 in 10s.'})

# ==========================================
# 3. BIN ROUTING (Fixes the /api/strand/be 404 error)
# ==========================================
@app.route('/api/strand/<cluster>', methods=['POST'])
def select_bin(cluster):
    # Mapping based on your Tray ID screenshot (lowercase as requested)
    cluster_map = {
        'ASSH': 'b1',
        'BE': 'b2',
        'STEM': 'b3',
        'CSS': 'b4',
        'DIGITAL': 'b4',   
        'EIM': 'b5',
        'EPAS': 'b5',
        'HARDWARE': 'b5'   
    }
    
    # .upper() makes it case-insensitive so 'be' or 'BE' both work!
    cmd = cluster_map.get(cluster.upper())
    
    if cmd:
        send_command(cmd)
        return jsonify({'status': 'success', 'command': cmd, 'cluster': cluster.upper()})
    else:
        return jsonify({'error': f'Unknown cluster mapping for: {cluster}'}), 400

# ==========================================
# 4. IR SENSOR READER
# ==========================================
@app.route('/api/sensor/read', methods=['GET'])
def read_sensor():
    if not arduino:
        return jsonify({'status': 'success', 'sensor': 'I1', 'message': 'Simulated Success'})
        
    # Removed reset_input_buffer() to avoid missing signals from Arduino
    time.sleep(0.1) 
    
    found_i1 = False
    found_i0 = False
    
    while arduino.in_waiting > 0:
        try:
            line = arduino.readline().decode('utf-8').strip()
            print(f"📡 Serial Read (Sensor): {line}")
            if 'I1' in line: found_i1 = True
            if 'I0' in line or 'PAPER_REJECTED' in line: found_i0 = True
        except: break
            
    if found_i1:
        return jsonify({'status': 'success', 'sensor': 'I1', 'message': 'Success! Go to next document.'})
    elif found_i0:
        return jsonify({'status': 'error', 'sensor': 'I0', 'message': 'Error/Reject Bin. Please rescan.'})
            
    return jsonify({'status': 'waiting', 'message': 'No sensor data detected yet.'})

@app.route('/api/sensor/check-rejection', methods=['GET'])
def check_rejection():
    """Specifically checks for PAPER_REJECTED or I0 signal from Arduino."""
    if not arduino:
        return jsonify({'rejected': False})
        
    found = False
    while arduino.in_waiting > 0:
        try:
            line = arduino.readline().decode('utf-8', errors='ignore').strip()
            if line:
                print(f"📡 Serial Read (Rejection Check): {line}")
                if 'PAPER_REJECTED' in line or 'I0' in line:
                    found = True
        except Exception as e:
            print(f"⚠️ Error reading serial: {e}")
            break
            
    return jsonify({'rejected': found})

# ==========================================
# STATUS PING
# ==========================================
@app.route('/status', methods=['GET'])
def server_status():
    return jsonify({
        'status': 'online', 
        'arduino_connected': arduino is not None
    })

if __name__ == '__main__':
    print("Starting Arduino Hardware Controller on Port 51234...")
    app.run(host='0.0.0.0', port=51234, debug=False)