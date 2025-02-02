import os
import time

# Introduce a short delay before attempting to delete the files
#time.sleep(2)  # Wait 2 seconds to ensure the file is released
print("")
print("Applying sanitizer")
# List of files to delete
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
        print(f"Can't apply sanitizer !")
