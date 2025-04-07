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
tezt2_script_path = os.path.join(script_dir, 'tezt4.py')
ftp_script_path = os.path.join(script_dir, 'ftp.py')
ftplog_script_path = os.path.join(script_dir, 'ftplog.py')
sanitizer_script_path = os.path.join(script_dir, 'sanitizer.py')
log_file_path = os.path.join(script_dir, 'scrapelog.txt')

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
        "https://dwamdstream102.akamaized.net/hls/live/2015525/dwstream102/stream04/streamPlaylist.m3u8"
    ]
    with open(file_path, 'a') as f:
        for url in additional_urls:
            f.write(url + '\n')

# --- Main Scraping Loop ---
while True:
    # --- Logging Configuration ---
    logger = logging.getLogger("scraper")
    logger.setLevel(logging.INFO)
    
    # Clear existing handlers
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
        handler.close()
    
    # Create new file handler
    try:
        file_handler = logging.FileHandler(log_file_path, mode='a')  # Append mode
    except PermissionError:
        print("Permission denied to write to scrapelog.txt. Exiting.")
        sys.exit(1)
    
    formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    clear_screen()
    start_time = time.time()
    logger.info("🚀 Starting new Mail sorting job")

    urls = [
        "https://www.seir-sanduk.com/?id=hd-bnt-1-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=bnt-2%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-bnt-3-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=bnt-4%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-btv-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-nova-tv-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=btv-cinema%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-btv-action-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=btv-comedy%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=btv-story%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=diema%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=diema-family%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=kino-nova%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-star-channel-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-star-crime-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-star-life-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-epic-drama-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=axn%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=axn-black%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=axn-white%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-2-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-3-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-max-sport-1-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-max-sport-2-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-max-sport-3-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-max-sport-4-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-nova-sport-hd%26pass=33aj3daawtDafra33",        
        "https://www.seir-sanduk.com/?id=hd-ring-bg-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-eurosport-1-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-eurosport-2-hd%26pass=33aj3daawtDafra33",        
        "https://www.seir-sanduk.com/?id=hd-discovery-channel-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-nat-geo-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-nat-geo-wild-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=tlc%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-food-network-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-24-kitchen-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-travel-channel-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=cartoon-network%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=disney-channel%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=e-kids%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=nickelodeon%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=nicktoons%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=nick-jr%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=kanal-3%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=evrokom%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-nova-news-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-78-tv-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=bloomberg-tv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=euronews-bulgaria%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=tv-1%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=bulgaria-on-air%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=vtk%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=skat%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-code-fashion-tv-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=travel-tv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=hd-planeta-hd%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=planeta-folk%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=tiankov-tv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=rodina-tv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=folklor-tv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=dstv%26pass=33aj3daawtDafra33",
        "https://www.seir-sanduk.com/?id=city-tv%26pass=33aj3daawtDafra33"
    ]

    with open(temp_file_path, 'w') as f:
        for url in urls:
            retries = 0
            success = False

            while retries < 10 and not success:
                try:
                    response = requests.get(f"http://localhost:8000/html?url={url}", timeout=30)
                    response.raise_for_status()
                    html = response.text

                    # Regex for finding m3u8 link
                    m3u8_match = re.search(r'(https?://[^\s"\']+\.m3u8[^\s"\']*)', html)

                    if m3u8_match:
                        m3u8_url = m3u8_match.group(1)
                        m3u8_url = m3u8_url.replace("&amp;", "&")
                        f.write(m3u8_url + '\n')
                        log_and_print(f"✅ Envelope Found")
                        success = True
                    else:
                        logger.warning(f"❌ No Envelope found")
                        retries += 1
                        time.sleep(1)

                except requests.exceptions.RequestException as e:
                    logger.error(f"⚠️ Error")
                    retries += 1
                    time.sleep(1)

            if not success:
                logger.error(f"❌ Failed to get Envelope after 10 attempts")

    clear_screen()
    # Append static URLs
    append_additional_urls(temp_file_path)
    log_and_print("📦 Non-address Envelopes appended to parcel")

    # Run packaging step
    log_and_print("🧪 Calling packaging operator to work...")
    subprocess.run(['sudo', 'python3', tezt2_script_path])

    elapsed = time.time() - start_time
    remaining = max(0, 3600 - elapsed)
    next_scrape_time = datetime.now() + timedelta(seconds=remaining)

    log_and_print("")
    log_and_print(f"✅ Mail sorting finished in {int(elapsed // 60)}m {int(elapsed % 60)}s")
    log_and_print(f"⏰ Next run scheduled for {next_scrape_time.strftime('%H:%M:%S')}")
    log_and_print("📮 Sending Peyo the Postman on a job...")

    # FTP Upload
    subprocess.run(['sudo', 'python3', ftplog_script_path])
    subprocess.run(['sudo', 'python3', ftp_script_path])

    # Sanitize
    subprocess.run(['sudo', 'python3', sanitizer_script_path])

    # Clean up logger handlers
    for handler in logger.handlers[:]:
        handler.close()
        logger.removeHandler(handler)

    # Sleep until next iteration
    time.sleep(remaining)
