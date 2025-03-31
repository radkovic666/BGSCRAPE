import shutil
import os
import requests

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

# Run import_playlist.php to update the database
try:
    response = requests.get("http://localhost/import_playlist.php", timeout=10)
    if response.status_code == 200:
        print("Database updated successfully.")
        
        # Delete playlist.m3u from destination after successful update
        playlist_path = os.path.join(destination_dir, "playlist.m3u")
        if os.path.exists(playlist_path):
            os.remove(playlist_path)
            print(f"Deleted: {playlist_path}")
    else:
        print(f"Error: {response.status_code} - {response.text}")
except requests.RequestException as e:
    print(f"Failed to update database: {e}")
