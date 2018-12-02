#!/usr/bin/env bash
chromium-browser --headless --disable-gpu --screenshot --no-sandbox --run-all-compositor-stages-before-draw --virtual-time-budget=5000 https://dev.keystone.guru/DDhw5Cc/preview
