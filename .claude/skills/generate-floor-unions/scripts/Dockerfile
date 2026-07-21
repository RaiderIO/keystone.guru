FROM python:3.12-slim

# Pinned: the pipeline's thresholds (SIFT inliers, edge-corr 0.2) were
# calibrated against these exact versions - unpinned bumps can silently shift
# registration results between machines.
RUN pip install --no-cache-dir opencv-python-headless==5.0.0.93 numpy==2.5.1
