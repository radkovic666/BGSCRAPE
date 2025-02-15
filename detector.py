import pyshorteners
import re

# File path
m3u_file = "playlist.m3u"

# Initialize is.gd shortener
s = pyshorteners.Shortener()

def shorten_url(url):
    """Shortens a given URL using is.gd"""
    try:
        return s.isgd.short(url)
    except Exception as e:
        print(f"Failed to shorten {url}: {e}")
        return url  # Keep original if shortening fails

def process_m3u():
    with open(m3u_file, "r", encoding="utf-8") as file:
        lines = file.readlines()

    updated_lines = []
    
    for line in lines:
        line = line.strip()
        if line.startswith("http"):  # Detect M3U8 URLs
            short_url = shorten_url(line)
            updated_lines.append(short_url)
        else:
            updated_lines.append(line)

    # Overwrite the original file
    with open(m3u_file, "w", encoding="utf-8") as file:
        file.write("\n".join(updated_lines) + "\n")

    print("M3U file updated successfully!")

# Run the script
process_m3u()
