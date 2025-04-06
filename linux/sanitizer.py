import os
import shutil
from pathlib import Path

print("\nApplying sanitizer...")

# List of files to delete in the current directory
files_to_delete = ["temp.txt", "scrapelog.txt"]

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

print("\nSanitization complete.")
