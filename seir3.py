import ctypes
import re
import sys
import requests
import subprocess
import os
import time
import logging
from datetime import datetime, timedelta

# Paths
script_dir = os.path.dirname(os.path.abspath(__file__))
temp_file_path = os.path.join(script_dir, 'temp.txt')
tezt2_script_path = os.path.join(script_dir, 'seir2.py')  # Path to packaging operator
ftp_script_path = os.path.join(script_dir, 'ftp.py')  # Path to ftp to github
ftplog_script_path = os.path.join(script_dir, 'ftplog.py')  # Path to ftp to scrapelog domain.
log_file_path = os.path.join(script_dir, 'scrapelog.txt')

# Ensure the old handlers are cleared
for handler in logging.root.handlers[:]:
    logging.root.removeHandler(handler)

# Logging configuration
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s]: %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
    filename=log_file_path,
    filemode='w'
)

file_handler = logging.FileHandler(log_file_path, mode='w')
file_handler.setFormatter(logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
logger = logging.getLogger()
logger.addHandler(file_handler)

def log_and_print(message):
    print(message)
    logger.info(message)

def clear_screen():
    os.system("cls" if os.name == "nt" else "clear")

def minimize_window():
    hwnd = ctypes.windll.kernel32.GetConsoleWindow()
    if hwnd:
        ctypes.windll.user32.ShowWindow(hwnd, 6)

def append_additional_urls(file_path):
    additional_urls = [
        "https://streamer103.neterra.tv/tiankov-folk/live.m3u8",
        "https://shorturl.at/RNYlf",
        "https://bss1.neterra.tv/magictv/stream_0.m3u8",
        "https://bss1.neterra.tv/thevoice/stream_0.m3u8",
        "https://live.ecomservice.bg/hls/stream.m3u8",
        "https://streamer103.neterra.tv/travel/live.m3u8",
        "https://streamer103.neterra.tv/thisisbulgaria/live.m3u8",
        "https://tv1.cloudcdn.bg/temp/livestream-720p.m3u8",
        "http://100automoto.tv:1935/bgtv1/autotv/playlist.m3u8",
        "https://dwamdstream102.akamaized.net/hls/live/2015525/dwstream102/stream04/streamPlaylist.m3u8"
    ]
    with open(file_path, 'a') as f:
        for url in additional_urls:
            f.write(url + '\n')

while True:
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s [%(levelname)s]: %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S',
        filename=log_file_path,
        filemode='w'
    )
    
    logger.handlers = []
    file_handler = logging.FileHandler(log_file_path, mode='w')
    file_handler.setFormatter(logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
    logger.addHandler(file_handler)
    
    clear_screen()
    start_time = time.time()
    
    logger.info("Starting new scraping iteration.")
    
    urls = [
        "https://www.seirsanduk.net/?id=hd-bnt-1-hd",
        "https://www.seirsanduk.net/?id=bnt-2",
        "https://www.seirsanduk.net/?id=hd-bnt-3-hd",
        "https://www.seirsanduk.net/?id=bnt-4",
        "https://www.seirsanduk.net/?id=hd-nova-tv-hd",
        "https://www.seirsanduk.net/?id=hd-btv-hd",
        "https://www.seirsanduk.net/?id=hd-btv-action-hd",
        "https://www.seirsanduk.net/?id=btv-cinema",
        "https://www.seirsanduk.net/?id=btv-comedy",
        "https://www.seirsanduk.net/?id=btv-story",
        "https://www.seirsanduk.net/?id=diema",  
        "https://www.seirsanduk.net/?id=diema-family",
        "https://www.seirsanduk.net/?id=hd-star-channel-hd",
        "https://www.seirsanduk.net/?id=hd-star-life-hd",
        "https://www.seirsanduk.net/?id=hd-star-crime-hd",
        "https://www.seirsanduk.net/?id=kino-nova",
        "https://www.seirsanduk.net/?id=hd-epic-drama-hd",
        "https://www.seirsanduk.net/?id=axn",
        "https://www.seirsanduk.net/?id=axn-black",
        "https://www.seirsanduk.net/?id=axn-white",
        "https://www.seirsanduk.net/?id=hd-diema-sport-hd",
        "https://www.seirsanduk.net/?id=hd-diema-sport-2-hd",
        "https://www.seirsanduk.net/?id=hd-diema-sport-3-hd",
        "https://www.seirsanduk.net/?id=hd-nova-sport-hd",
        "https://www.seirsanduk.net/?id=hd-max-sport-1-hd",
        "https://www.seirsanduk.net/?id=hd-max-sport-2-hd",
        "https://www.seirsanduk.net/?id=hd-max-sport-3-hd",
        "https://www.seirsanduk.net/?id=hd-max-sport-4-hd",
        "https://www.seirsanduk.net/?id=hd-ring-bg-hd",
        "https://www.seirsanduk.net/?id=hd-eurosport-1-hd",
        "https://www.seirsanduk.net/?id=hd-eurosport-2-hd",
        "https://www.seirsanduk.net/?id=hd-nat-geo-wild-hd",
        "https://www.seirsanduk.net/?id=hd-nat-geo-hd",
        "https://www.gledaitv.live/watch-tv/22/investigation-discovery-online",
        "https://www.gledaitv.live/watch-tv/18/discovery-channel-online",
        "https://www.gledaitv.live/watch-tv/21/history-channel-online",
        "https://www.gledaitv.live/watch-tv/19/docubox-online",
        "https://www.gledaitv.live/watch-tv/35/24-kitchen-online",
        "https://www.gledaitv.live/watch-tv/36/tlc-online",
        "https://www.gledaitv.live/watch-tv/15/disney-channel-online",
        "https://www.seirsanduk.net/?id=cartoon-network",
        "https://www.gledaitv.live/watch-tv/14/nickelodeon-online",
        "https://www.gledaitv.live/watch-tv/29/nick-jr-online",
        "https://www.gledaitv.live/watch-tv/30/nicktoons-online",
        "https://www.gledaitv.live/watch-tv/40/planeta-hd-online",
        "https://www.gledaitv.live/watch-tv/39/planeta-folk-online",
        "https://www.gledaitv.live/watch-tv/74/fen-tv-online",
        "https://www.gledaitv.live/watch-tv/73/balkanika-tv-online",
        "https://www.seirsanduk.net/?id=bulgaria-on-air"
    ]
    
    with open(temp_file_path, 'w') as f:
        for url in urls:
            retries = 0
            success = False

            while retries < 10 and not success:
                try:
                    response = requests.get(f"http://localhost:8000/html?url={url}")
                    response.raise_for_status()
                    html_content = response.text

                    m3u8_url = re.search(r'https?:\/\/cdn\d+\.glebul\.com\/hls\/[\w-]+\/index\.m3u8\?e=\d+&hash=[\w-]+', html_content)

                    if m3u8_url:
                        clean_url = m3u8_url.group(0).strip('"')
                        f.write(clean_url + '\n')
                        log_and_print(f"Valid M3U8 URL found")
                        success = True
                    else:
                        logger.warning(f"No M3U8 URL found. Retrying...")
                        retries += 1
                        time.sleep(1)

                except requests.exceptions.RequestException as e:
                    logger.error(f"Error: {e}")
                    retries += 1
                    time.sleep(1)

            if not success:
                logger.error(f"Failed to retrieve M3U8 URL after 10 attempts.")
    
    append_additional_urls(temp_file_path)
    log_and_print("Additional static URLs added.")
    
    log_and_print("Running packaging operator...")
    subprocess.run(['python', tezt2_script_path])
    
    elapsed_time = time.time() - start_time
    elapsed_minutes, elapsed_seconds = divmod(elapsed_time, 60)
    remaining_time = max(0, 3600 - elapsed_time)
    remaining_minutes, remaining_seconds = divmod(remaining_time, 60)
    next_scrape_time = datetime.now() + timedelta(seconds=remaining_time)
    next_scrape_time_formatted = next_scrape_time.strftime("%H:%M:%S")

    clear_screen()
    log_and_print("")
    log_and_print(f"Scraping finished in {int(elapsed_minutes)} minutes and {int(elapsed_seconds)} seconds.")
    log_and_print(f"Next scraping cycle will start at {next_scrape_time_formatted}.")
    log_and_print("")
    log_and_print("Sending Peyo The Postman on a job...")
    
    subprocess.run(['python', ftplog_script_path])    
    subprocess.run(['python', ftp_script_path])
    
    for handler in logger.handlers:
        handler.close()
    logger.handlers[0].flush()
    
    subprocess.run(['python', "sanitizer.py"])
    
    time.sleep(remaining_time)
