#!/bin/bash
pkill -9 -f ocr_server.py
sleep 2
python3 python_services/ocr_server.py > ocr_real_v6.log 2>&1
