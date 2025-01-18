from ftplib import FTP
import os

# Function to clear the terminal screen
#def clear_screen():
    #cls.system("cls")


#clear_screen()

# FTP server details
ftp_server = '178.16.128.165'
ftp_port = 21
ftp_user = 'u897539158'  # Replace with your actual FTP username
ftp_pass = 'H0rnbow12!'  # Replace with your actual FTP password


# File details
local_file = 'playlist.m3u'  # Playlist File is in the same directory as the script
remote_file = '/domains/hajanddebono.com/public_html/images/playlist.m3u'  # Path on the server where you want to upload the playlist file
log_file = 'scrapelog.txt'  # Log File is in the same directory as the script
remote_log_file = '/domains/hajanddebono.com/public_html/images/scrapelog.txt'  # Path on the server where you want to upload the log file


# Connect to the FTP server
ftp = FTP()
ftp.connect(ftp_server, ftp_port)
ftp.login(ftp_user, ftp_pass)

# Open the local file and upload it
with open(local_file, 'rb') as file:
    ftp.storbinary(f'STOR {remote_file}', file)
with open(log_file, 'rb') as file:
    ftp.storbinary(f'STOR {remote_log_file}', file)

# Close the FTP connection
ftp.quit()

print(f"File {local_file} has been uploaded to {ftp_server}/{remote_file}")
print(f"File {log_file} has been uploaded to {ftp_server}/{remote_log_file}")
