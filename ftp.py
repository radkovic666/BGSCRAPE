import shutil
import os

# Get the script's directory as the source directory
source_dir = os.path.dirname(os.path.abspath(__file__))
destination_dir = r"C:\xampp\htdocs"

# List of files to copy
files_to_copy = ["playlist.m3u", "scrapelog.html"]

# Ensure destination exists
os.makedirs(destination_dir, exist_ok=True)

# Copy files
for file in files_to_copy:
    src_path = os.path.join(source_dir, file)
    dest_path = os.path.join(destination_dir, file)

    if os.path.exists(src_path):
        shutil.copy2(src_path, dest_path)
        print(f"Delivered: {file} → {destination_dir}")
    else:
        print(f"File not found: {src_path}")
