import ctypes
import re
import requests
import subprocess
import os
import time
import logging
from datetime import datetime, timedelta

# Paths
script_dir = os.path.dirname(os.path.abspath(__file__))
exe_path = os.path.join(script_dir, "exiter.exe")
temp_file_path = os.path.join(script_dir, 'temp.txt')
tezt2_script_path = os.path.join(script_dir, 'tezt2.py')  # Path to tezt2.py
ftp_script_path = os.path.join(script_dir, 'ftp.py')  # Path to ftp.py
log_file_path = os.path.join(script_dir, 'scrapelog.txt')

# Ensure the old handlers are cleared
for handler in logging.root.handlers[:]:
    logging.root.removeHandler(handler)

# Logging configuration
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s]: %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
    filename=log_file_path,  # Set log file path here
    filemode='w'  # This will ensure the log file is overwritten each time
)

# Add a FileHandler with 'w' mode
file_handler = logging.FileHandler(log_file_path, mode='w')  # 'w' ensures overwrite
file_handler.setFormatter(logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
logger = logging.getLogger()
logger.addHandler(file_handler)

def log_and_print(message):
    """Log a message to the log file and print it to the console."""
    print(message)  # Print to console
    logger.info(message)  # Write to the log file

# Function to clear the terminal screen
def clear_screen():
    os.system("cls" if os.name == "nt" else "clear")

# Function to minimize the console window
def minimize_window():
    hwnd = ctypes.windll.kernel32.GetConsoleWindow()
    if hwnd:
        ctypes.windll.user32.ShowWindow(hwnd, 6)

# Function to append additional URLs to the temp file
def append_additional_urls(file_path):
    additional_urls = [
        "https://streamer103.neterra.tv/tiankov-folk/live.m3u8", #TiankovFolk
        #"https://streamer103.neterra.tv/tiankov-orient/live.m3u8", #TiankovOrient
        "https://bss1.neterra.tv/magictv/stream_0.m3u8",
        "https://bss1.neterra.tv/thevoice/stream_0.m3u8",
        "https://dwamdstream102.akamaized.net/hls/live/2015525/dwstream102/stream04/streamPlaylist.m3u8",
        "https://live.ecomservice.bg/hls/stream.m3u8",
        "https://streamer103.neterra.tv/travel/live.m3u8",
        "https://streamer103.neterra.tv/thisisbulgaria/live.m3u8", #ThisIsBulgaria
        "http://100automoto.tv:1935/bgtv1/autotv/playlist.m3u8", #100 Auto Moto
        #"https://old.rn-tv.com/k0/stream.m3u8", #Kanal0
        "http://zagoratv.ddns.net:8080/tvzagora.m3u8", #TV Zagora

      
    ]
    with open(file_path, 'a') as f:
        for url in additional_urls:
            f.write(url + '\n')

while True:
    # Ensure a fresh log file for each scraping cycle
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s [%(levelname)s]: %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S',
        filename=log_file_path,
        filemode='w'  # Overwrite the log file
    )
    
    # Re-add the file handler to ensure the log file is opened correctly
    logger.handlers = []  # Remove existing handlers to avoid duplication
    file_handler = logging.FileHandler(log_file_path, mode='w')
    file_handler.setFormatter(logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
    logger.addHandler(file_handler)
    
    clear_screen()
    start_time = time.time()

    logger.info("Starting new scraping iteration.")  # Only log this, no print

    urls = [
        "https://www.gledaitv.live/watch-tv/64/bnt-1-online",
        #"https://www.gledaitv.live/watch-tv/63/bnt-2-online",
        "https://www.gledaitv.live/watch-tv/62/bnt-3-online",
        "https://www.gledaitv.live/watch-tv/42/bnt-4-online",
        "https://www.gledaitv.live/watch-tv/61/btv-online",
        "https://www.gledaitv.live/watch-tv/60/nova-tv-online",
        "https://www.gledaitv.live/watch-tv/56/btv-action-online",
        "https://www.gledaitv.live/watch-tv/55/btv-cinema-online",
        "https://www.gledaitv.live/watch-tv/54/btv-comedy-online",
        "https://www.gledaitv.live/watch-tv/53/btv-lady-online",
        "https://www.gledaitv.live/watch-tv/52/diema-online",
        "https://www.gledaitv.live/watch-tv/51/diema-family-online",
        "https://www.gledaitv.live/watch-tv/50/film-box-online",
        "https://www.gledaitv.live/watch-tv/49/film-box-extra-online",
        "https://www.gledaitv.live/watch-tv/48/film-box-plus-online",
        "https://www.gledaitv.live/watch-tv/47/fox-online",
        "https://www.gledaitv.live/watch-tv/46/fox-life-online",
        "https://www.gledaitv.live/watch-tv/45/fox-crime-online",
        "https://www.gledaitv.live/watch-tv/44/kino-nova-online",
        "https://www.gledaitv.live/watch-tv/75/epic-drama-online",
        "https://www.gledaitv.live/watch-tv/43/movie-star-online",
        "https://www.gledaitv.live/watch-tv/59/axn-online",
        "https://www.gledaitv.live/watch-tv/66/amc-online",
        "https://www.gledaitv.live/watch-tv/13/diema-sport-online",
        "https://www.gledaitv.live/watch-tv/12/diema-sport-2-online",
        "https://www.gledaitv.live/watch-tv/38/diema-sport-3-online",
        "https://www.gledaitv.live/watch-tv/32/nova-sport-online",
        "https://www.gledaitv.live/watch-tv/9/max-sport-bg-1-online",
        "https://www.gledaitv.live/watch-tv/10/max-sport-2-bg-online",
        "https://www.gledaitv.live/watch-tv/11/max-sport-3-bg-online",
        "https://www.gledaitv.live/watch-tv/65/max-sport-4-bg-online",
        "https://www.gledaitv.live/watch-tv/31/ring-bg-online",
        "https://www.gledaitv.live/watch-tv/33/eurosport-1-online",
        "https://www.gledaitv.live/watch-tv/34/eurosport-2-online",
        "https://www.gledaitv.live/watch-tv/17/animal-planet-online",
        "https://www.gledaitv.live/watch-tv/28/viasat-nature-online",
        "https://www.gledaitv.live/watch-tv/27/viasat-history-online",
        "https://www.gledaitv.live/watch-tv/24/nat-geo-wild-online",
        "https://www.gledaitv.live/watch-tv/23/national-geographic-channel",
        "https://www.gledaitv.live/watch-tv/22/investigation-discovery-online",
        "https://www.gledaitv.live/watch-tv/18/discovery-channel-online",
        "https://www.gledaitv.live/watch-tv/21/history-channel-online",
        "https://www.gledaitv.live/watch-tv/19/docubox-online",
        "https://www.gledaitv.live/watch-tv/15/disney-channel-online",
        "https://www.gledaitv.live/watch-tv/16/cartoon-network-online",
        "https://www.gledaitv.live/watch-tv/14/nickelodeon-online",
        "https://www.gledaitv.live/watch-tv/29/nick-jr-online",
        "https://www.gledaitv.live/watch-tv/30/nicktoons-online",
        "https://www.gledaitv.live/watch-tv/40/planeta-hd-online",
        "https://www.gledaitv.live/watch-tv/39/planeta-folk-online",
        "https://www.gledaitv.live/watch-tv/74/fen-tv-online",
        "https://www.gledaitv.live/watch-tv/73/balkanika-tv-online",
        "https://www.gledaitv.live/watch-tv/35/24-kitchen-online",
        "https://www.gledaitv.live/watch-tv/36/tlc-online"
    ]

    with open(temp_file_path, 'w') as f:
        for url in urls:
            retries = 0
            success = False

            while retries < 5 and not success:
                try:
                    subprocess.Popen([exe_path])  # Run exiter.exe for each retry
                    response = requests.get(f"http://localhost:8000/html?url={url}")
                    response.raise_for_status()
                    html_content = response.text

                    m3u8_url = re.search(r'https://cdn\.stgledai\.org:8082/hls/[^"\']+\.m3u8\?token=[^"\']+', html_content)

                    if m3u8_url:
                        clean_url = m3u8_url.group(0).strip('"')
                        f.write(clean_url + '\n')
                        log_and_print(f"Valid M3U8 URL found for {url}")
                        success = True
                    else:
                        logger.warning(f"No M3U8 URL found for {url}. Retrying...")  # Only log warnings
                        retries += 1
                        time.sleep(1)

                except requests.exceptions.RequestException as e:
                    logger.error(f"Error for {url}: {e}")  # Only log errors
                    retries += 1
                    time.sleep(1)

            if not success:
                logger.error(f"Failed to retrieve M3U8 URL for {url} after 5 attempts.")

    append_additional_urls(temp_file_path)
    log_and_print("Additional static URLs appended to temp.txt.")

    log_and_print("Running tezt2.py...")
    subprocess.run(['python', tezt2_script_path])

    elapsed_time = time.time() - start_time
    elapsed_minutes, elapsed_seconds = divmod(elapsed_time, 60)
    remaining_time = max(0, 3600 - elapsed_time)
    remaining_minutes, remaining_seconds = divmod(remaining_time, 60)
    next_scrape_time = datetime.now() + timedelta(seconds=remaining_time)
    next_scrape_time_formatted = next_scrape_time.strftime("%H:%M:%S")

    clear_screen()  # Move this to the end, after printing/logging

    log_and_print("")
    log_and_print(f"Scraping finished in {int(elapsed_minutes)} minutes and {int(elapsed_seconds)} seconds.")
    log_and_print(f"Next scraping cycle will start at {next_scrape_time_formatted}.")
    log_and_print("")
    log_and_print("Running ftp.py...")
    subprocess.run(['python', ftp_script_path])

    # Close and flush handlers, for scrapelog.txt to be unused prior deletion.
    for handler in logger.handlers:
        handler.close()
    logger.handlers[0].flush()

    subprocess.run(['python', "sanitizer.py"])   # Apply sanitizer 

    time.sleep(remaining_time)
