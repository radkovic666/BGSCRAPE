import os
import shutil
from pathlib import Path

print("\nApplying sanitizer...")

# List of files to delete in the current directory
files_to_delete = ["temp.txt", "scrapelog.txt", "playlist.m3u"]

for file_name in files_to_delete:
    if os.path.exists(file_name):
        try:
            os.remove(file_name)
            print(f"Wiped {file_name}")
        except Exception as e:
            print(f"Error deleting {file_name}: {e}")
    else:
        print(f"{file_name} does not exist")
        print("Can't apply sanitizer!")

# Get the user's home directory dynamically
user_profile = Path.home()

# Chrome cache paths
cache_data_path = user_profile / "AppData" / "Local" / "Google" / "Chrome" / "User Data" / "Default" / "Cache" / "Cache_Data"
wasm_cache_path = user_profile / "AppData" / "Local" / "Google" / "Chrome" / "User Data" / "Default" / "Code Cache" / "wasm"
js_cache_path = user_profile / "AppData" / "Local" / "Google" / "Chrome" / "User Data" / "Default" / "Code Cache" / "js"

# Temp folder path
#temp_folder_path = user_profile / "AppData" / "Local" / "Temp"

# Function to delete all files in a directory
def delete_files_in_directory(directory, exclude=None):
    if directory.exists() and directory.is_dir():
        for item in directory.iterdir():
            if exclude and item.name in exclude:
                continue
            try:
                if item.is_file():
                    item.unlink()
                    print(f"Deleted: {item}")
                elif item.is_dir():
                    shutil.rmtree(item)
                    print(f"Deleted directory: {item}")
            except Exception as e:
                print(f"Error deleting {item}: {e}")
    else:
        print(f"Directory not found: {directory}")

# Delete all files in Cache_Data
delete_files_in_directory(cache_data_path)

# Delete all files in wasm except index-dir
delete_files_in_directory(wasm_cache_path, exclude=["index-dir"])

# Delete all files in js except index-dir
delete_files_in_directory(js_cache_path, exclude=["index-dir"])

# Delete everything in Temp
#delete_files_in_directory(temp_folder_path)

print("\nSanitization complete.")
