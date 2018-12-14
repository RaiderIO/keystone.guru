#!/usr/bin/env bash
chromium-browser --headless --disable-gpu --window-size=768,512 --screenshot --no-sandbox --run-all-compositor-stages-before-draw --virtual-time-budget=7000 https://dev.keystone.guru/DDhw5Cc/preview
