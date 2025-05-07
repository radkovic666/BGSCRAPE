# arabskivestnik H0rnbow12 otustanausta.com

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

def get_updated_pass():
    try:
        response = requests.get("https://www.seir-sanduk.com/linkzagledane.php?parola=aeagaDs3AdKaAf2", allow_redirects=True, timeout=10)
        redirected_url = response.url
        match = re.search(r'pass=([a-zA-Z0-9]+)', redirected_url)
        if match:
            return match.group(1)
        else:
            logger.warning("⚠️ No 'pass' parameter found in redirect URL.")
            return None
    except Exception as e:
        logger.error(f"❌ Failed to fetch updated pass: {e}")
        return None

# --- Main Scraping Loop ---
while True:
    # --- Logging Configuration ---
    logger = logging.getLogger("scraper")
    logger.setLevel(logging.INFO)

    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
        handler.close()

    try:
        file_handler = logging.FileHandler(log_file_path, mode='a')
    except PermissionError:
        print("Permission denied to write to scrapelog.txt. Exiting.")
        sys.exit(1)

    formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    clear_screen()
    start_time = time.time()
    logger.info("🚀 Starting new Mail sorting job")

    # Get the dynamic pass
    dynamic_pass = get_updated_pass()
    if not dynamic_pass:
        log_and_print("❌ Using default pass due to failure.")
        dynamic_pass = "22kalAdKaAf2l22"  # fallback

    # Base URLs without pass
    base_urls = [
        "https://www.seir-sanduk.com/?id=hd-bnt-1-hd",
        "https://www.seir-sanduk.com/?id=bnt-2",
        "https://www.seir-sanduk.com/?id=hd-bnt-3-hd",
        "https://www.seir-sanduk.com/?id=bnt-4",
        "https://www.seir-sanduk.com/?id=hd-btv-hd",
        "https://www.seir-sanduk.com/?id=hd-nova-tv-hd",
        "https://www.seir-sanduk.com/?id=btv-cinema",
        "https://www.seir-sanduk.com/?id=hd-btv-action-hd",
        "https://www.seir-sanduk.com/?id=btv-comedy",
        "https://www.seir-sanduk.com/?id=btv-story",
        "https://www.seir-sanduk.com/?id=diema",
        "https://www.seir-sanduk.com/?id=diema-family",
        "https://www.seir-sanduk.com/?id=kino-nova",
        "https://www.seir-sanduk.com/?id=hd-star-channel-hd",
        "https://www.seir-sanduk.com/?id=hd-star-crime-hd",
        "https://www.seir-sanduk.com/?id=hd-star-life-hd",
        "https://www.seir-sanduk.com/?id=hd-epic-drama-hd",
        "https://www.seir-sanduk.com/?id=axn",
        "https://www.seir-sanduk.com/?id=axn-black",
        "https://www.seir-sanduk.com/?id=axn-white",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-hd",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-2-hd",
        "https://www.seir-sanduk.com/?id=hd-diema-sport-3-hd",
        "https://www.seir-sanduk.com/?id=hd-max-sport-1-hd",
        "https://www.seir-sanduk.com/?id=hd-max-sport-2-hd",
        "https://www.seir-sanduk.com/?id=hd-max-sport-3-hd",
        "https://www.seir-sanduk.com/?id=hd-max-sport-4-hd",
        "https://www.seir-sanduk.com/?id=hd-nova-sport-hd",
        "https://www.seir-sanduk.com/?id=hd-ring-bg-hd",
        "https://www.seir-sanduk.com/?id=hd-eurosport-1-hd",
        "https://www.seir-sanduk.com/?id=hd-eurosport-2-hd",
        "https://www.seir-sanduk.com/?id=hd-discovery-channel-hd",
        "https://www.seir-sanduk.com/?id=hd-nat-geo-hd",
        "https://www.seir-sanduk.com/?id=hd-nat-geo-wild-hd",
        "https://www.seir-sanduk.com/?id=tlc",
        "https://www.seir-sanduk.com/?id=hd-food-network-hd",
        "https://www.seir-sanduk.com/?id=hd-24-kitchen-hd",
        "https://www.seir-sanduk.com/?id=hd-travel-channel-hd",
        "https://www.seir-sanduk.com/?id=cartoon-network",
        "https://www.seir-sanduk.com/?id=disney-channel",
        "https://www.seir-sanduk.com/?id=e-kids",
        "https://www.seir-sanduk.com/?id=nickelodeon",
        "https://www.seir-sanduk.com/?id=nicktoons",
        "https://www.seir-sanduk.com/?id=nick-jr",
        "https://www.seir-sanduk.com/?id=kanal-3",
        "https://www.seir-sanduk.com/?id=evrokom",
        "https://www.seir-sanduk.com/?id=hd-nova-news-hd",
        "https://www.seir-sanduk.com/?id=hd-78-tv-hd",
        "https://www.seir-sanduk.com/?id=bloomberg-tv",
        "https://www.seir-sanduk.com/?id=euronews-bulgaria",
        "https://www.seir-sanduk.com/?id=tv-1",
        "https://www.seir-sanduk.com/?id=bulgaria-on-air",
        "https://www.seir-sanduk.com/?id=vtk",
        "https://www.seir-sanduk.com/?id=skat",
        "https://www.seir-sanduk.com/?id=hd-code-fashion-tv-hd",
        "https://www.seir-sanduk.com/?id=travel-tv",
        "https://www.seir-sanduk.com/?id=hd-planeta-hd",
        "https://www.seir-sanduk.com/?id=planeta-folk",
        "https://www.seir-sanduk.com/?id=tiankov-tv",
        "https://www.seir-sanduk.com/?id=rodina-tv",
        "https://www.seir-sanduk.com/?id=folklor-tv",
        "https://www.seir-sanduk.com/?id=dstv",
        "https://www.seir-sanduk.com/?id=city-tv"
    ]
    urls = [f"{base}%26pass={dynamic_pass}" for base in base_urls]

    with open(temp_file_path, 'w') as f:
        for url in urls:
            retries = 0
            success = False

            while retries < 10 and not success:
                try:
                    response = requests.get(f"http://localhost:8000/html?url={url}", timeout=30)
                    response.raise_for_status()
                    html = response.text

                    m3u8_match = re.search(r'(https?://[^\s"\']+\.m3u8[^\s"\']*)', html)

                    if m3u8_match:
                        m3u8_url = m3u8_match.group(1).replace("&amp;", "&")
                        f.write(m3u8_url + '\n')
                        log_and_print("✅ Envelope Found")
                        success = True
                    else:
                        logger.warning("❌ No Envelope found")
                        retries += 1
                        time.sleep(1)

                except requests.exceptions.RequestException:
                    logger.error("⚠️ Error")
                    retries += 1
                    time.sleep(1)

            if not success:
                logger.error("❌ Failed to get Envelope after 10 attempts")

    clear_screen()
    append_additional_urls(temp_file_path)
    log_and_print("📦 Non-address Envelopes appended to parcel")

    log_and_print("🧪 Calling packaging operator to work...")
    subprocess.run(['sudo', 'python3', tezt2_script_path])

    elapsed = time.time() - start_time
    remaining = max(0, 3600 - elapsed)
    next_scrape_time = datetime.now() + timedelta(seconds=remaining)

    log_and_print("")
    log_and_print(f"✅ Mail sorting finished in {int(elapsed // 60)}m {int(elapsed % 60)}s")
    log_and_print(f"⏰ Next run scheduled for {next_scrape_time.strftime('%H:%M:%S')}")
    log_and_print("📮 Sending Peyo the Postman on a job...")

    subprocess.run(['sudo', 'python3', ftplog_script_path])
    subprocess.run(['sudo', 'python3', ftp_script_path])
    subprocess.run(['sudo', 'python3', sanitizer_script_path])

    for handler in logger.handlers[:]:
        handler.close()
        logger.removeHandler(handler)

    time.sleep(remaining)
