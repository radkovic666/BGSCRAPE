import ctypes
import re
import sys
import requests
import subprocess
import os
import time
import logging
from datetime import datetime, timedelta

# --- Setup Paths ---
script_dir = os.path.dirname(os.path.abspath(__file__))
temp_file_path = os.path.join(script_dir, 'temp.txt')
tezt2_script_path = os.path.join(script_dir, 'tezt2.py')
ftp_script_path = os.path.join(script_dir, 'ftp.py')
ftplog_script_path = os.path.join(script_dir, 'ftplog.py')
sanitizer_script_path = os.path.join(script_dir, 'sanitizer.py')
log_file_path = os.path.join(script_dir, 'scrapelog.txt')

# --- Logging Configuration ---
logger = logging.getLogger("scraper")
logger.setLevel(logging.INFO)

# Remove existing handlers
while logger.hasHandlers():
    logger.removeHandler(logger.handlers[0])

# Create File Handler
try:
    file_handler = logging.FileHandler(log_file_path, mode='w')
except PermissionError:
    print("Permission denied to write to scrapelog.txt. Exiting.")
    sys.exit(1)

formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
file_handler.setFormatter(formatter)
logger.addHandler(file_handler)

def log_and_print(message):
    print(message)
    logger.info(message)

def clear_screen():
    os.system("cls" if os.name == "nt" else "clear")

def minimize_window():
    try:
        hwnd = ctypes.windll.kernel32.GetConsoleWindow()
        if hwnd:
            ctypes.windll.user32.ShowWindow(hwnd, 6)
    except Exception:
        pass  # Skip on Linux/Mac

def append_additional_urls(file_path):
    additional_urls = [
        "https://bss1.neterra.tv/magictv/stream_0.m3u8",
        "https://bss1.neterra.tv/thevoice/stream_0.m3u8",
        #"https://dwamdstream102.akamaized.net/hls/live/2015525/dwstream102/stream04/streamPlaylist.m3u8"
    ]
    with open(file_path, 'a') as f:
        for url in additional_urls:
            f.write(url + '\n')

# --- Main Scraping Loop ---
while True:
    clear_screen()
    start_time = time.time()

    logger.info("🚀 Starting new scraping iteration")

    urls = [
        "https://www.seirsanduk.com/bnt-1-online/",
        "https://www.seirsanduk.com/bnt-2-online/",
        "https://www.seirsanduk.com/bnt-3-online/",
        "https://www.seirsanduk.com/bnt-4-online/",
        "https://www.seirsanduk.com/btv-online/",
        "https://www.seirsanduk.com/nova-tv-online/",
        "https://www.seirsanduk.com/btv-cinema-online/",
        "https://www.seirsanduk.com/btv-action-online/",
        "https://www.seirsanduk.com/btv-comedy-online/",
        "https://www.seirsanduk.com/btv-lady-online/",
        "https://www.seirsanduk.com/diema-online/",
        "https://www.seirsanduk.com/diema-family-online/",
        "https://www.seirsanduk.com/kino-nova-online/",
        "https://www.seirsanduk.com/star-channel-online/",
        "https://www.seirsanduk.com/star-crime-online/",
        "https://www.seirsanduk.com/star-life-online/",
        "https://www.seirsanduk.com/epic-drama-online/",
        "https://www.seirsanduk.com/axn-online-gledai-tv/",
        "https://www.seirsanduk.com/axn-black-online/",
        "https://www.seirsanduk.com/axn-white-online/",
        "https://www.seirsanduk.com/diema-sport-online/",
        "https://www.seirsanduk.com/diema-sport-2-online/",
        "https://www.seirsanduk.com/diema-sport-3-online/",
        "https://www.seirsanduk.com/max-sport-1-online/",
        "https://www.seirsanduk.com/max-sport-2-online/",
        "https://www.seirsanduk.com/max-sport-3-online/",
        "https://www.seirsanduk.com/max-sport-4-online/",
        "https://www.seirsanduk.com/nova-sport-online/",        
        "https://www.seirsanduk.com/ring-bg-online/",
        "https://www.seirsanduk.com/eurosport-1-online/",
        "https://www.seirsanduk.com/eurosport-2-online/",        
        "https://www.seirsanduk.com/discovery-channel-online/",
        "https://www.seirsanduk.com/investigation-discovery-online/",
        "https://www.seirsanduk.com/national-geographic-online/",
        "https://www.seirsanduk.com/nat-geo-wild-online/",
        "https://www.seirsanduk.com/tlc-online/",
        "https://www.seirsanduk.com/24-kitchen-televizia-online/",
        "https://www.seirsanduk.com/travel-channel-online/",
        "https://www.seirsanduk.com/cartoon-network-online/",
        "https://www.seirsanduk.com/ekids-online/",
        "https://www.seirsanduk.com/nickelodeon-online/",
        "https://www.seirsanduk.com/nicktoons-online/",
        "https://www.seirsanduk.com/nick-jr-online/",
        "https://www.seirsanduk.com/evrokom-online/",
        "https://www.seirsanduk.com/nova-news-online/",
        "https://www.seirsanduk.com/7-8-tv-online/",
        "https://www.seirsanduk.com/bloomberg-tv-online/",
        "https://www.seirsanduk.com/tv-1-online/",
        "https://www.seirsanduk.com/bulgaria-on-air-online/",
        "https://www.seirsanduk.com/vtk-online/",
        "https://www.seirsanduk.com/skat-online/",
        "https://www.seirsanduk.com/travel-tv-online/",
        "https://www.seir-sanduk.com/hd-planeta-hd",
        "https://www.seir-sanduk.com/planeta-folk",
        "https://www.seirsanduk.com/tiankov-folk-online/",
        "https://www.seirsanduk.com/rodina-online/",
        "https://www.seirsanduk.com/folklor-tv-online/",
        "https://www.seirsanduk.com/dstv-online/",
        "https://www.seirsanduk.com/city-tv-online/",

        
    ]

    with open(temp_file_path, 'w') as f:
        for url in urls:
            retries = 0
            success = False

            while retries < 10 and not success:
                try:
                    response = requests.get(f"http://localhost:8000/html?url={url}", timeout=10)
                    response.raise_for_status()
                    html = response.text

                    # Regex for finding m3u8 link
                    m3u8_match = re.search(r'(https?://[^\s"\']+\.m3u8[^\s"\']*)', html)

                    if m3u8_match:
                        m3u8_url = m3u8_match.group(1)
                        m3u8_url = m3u8_url.replace("&amp;", "&")
                        f.write(m3u8_url + '\n')
                        log_and_print(f"✅ Found M3U8")
                        success = True
                    else:
                        logger.warning(f"❌ No M3U8 found")
                        retries += 1
                        time.sleep(1)

                except requests.exceptions.RequestException as e:
                    logger.error(f"⚠️ Error")
                    retries += 1
                    time.sleep(1)

            if not success:
                logger.error(f"❌ Failed to get M3U8 after 10 attempts")

    clear_screen()
    # Append static URLs
    append_additional_urls(temp_file_path)
    log_and_print("📦 Static URLs appended to parcel")

    # Run packaging step
    log_and_print("🧪 Calling packaging operator to work...")
    subprocess.run(['sudo', 'python3', tezt2_script_path])

    elapsed = time.time() - start_time
    remaining = max(0, 3600 - elapsed)
    next_scrape_time = datetime.now() + timedelta(seconds=remaining)

    #clear_screen()

    log_and_print("")
    log_and_print(f"✅ Mail sorting finished in {int(elapsed // 60)}m {int(elapsed % 60)}s")
    log_and_print(f"⏰ Next run scheduled for {next_scrape_time.strftime('%H:%M:%S')}")
    log_and_print("📮 Sending Peyo the Postman on a job...")

    # FTP Upload
    subprocess.run(['sudo', 'python3', ftplog_script_path])
    subprocess.run(['sudo', 'python3', ftp_script_path])

    # Sanitize
    subprocess.run(['sudo', 'python3', sanitizer_script_path])

    # Sleep until next iteration
    time.sleep(remaining)
