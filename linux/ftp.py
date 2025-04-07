import shutil
import os
import requests

# Get the script's directory as the source directory
source_dir = os.path.dirname(os.path.abspath(__file__))

# Linux Apache default web directory
destination_dir = "/var/www/html"

# List of files to copy
files_to_copy = ["playlist.m3u", "scrapelog.html"]

# Ensure the destination exists
os.makedirs(destination_dir, exist_ok=True)

# Copy files with permission handling
for file in files_to_copy:
    src_path = os.path.join(source_dir, file)
    dest_path = os.path.join(destination_dir, file)

    if os.path.exists(src_path):
        try:
            shutil.copy2(src_path, dest_path)
            print(f"Delivered: {file} → {destination_dir}")
        except PermissionError:
            print(f"Permission denied: Cannot copy {file} to {destination_dir}. Try running with sudo.")
    else:
        print(f"File not found: {src_path}")

# Run import_playlist.php to update the database
try:
    response = requests.get("http://localhost/import_playlist.php", timeout=60)
    if response.status_code == 200:
        print("Peyo accomplished his job successfully.")

        # Delete playlist.m3u from destination after successful update
        playlist_path = os.path.join(destination_dir, "playlist.m3u")
        if os.path.exists(playlist_path):
            try:
                os.remove(playlist_path)
                #print(f"Deleted: {playlist_path}")
            except PermissionError:
                print(f"Permission denied: Cannot delete {playlist_path}. Try running with sudo.")
    else:
        print(f"Error: {response.status_code} - {response.text}")
except requests.RequestException as e:
    print(f"Failed to update database: {e}")
