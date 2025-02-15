from ftplib import FTP

# FTP server details
ftp_server = '185.27.134.11'  # Direct FTP IP address
ftp_port = 21
ftp_user = 'if0_38223125'
ftp_pass = 'PZei3Ju4KnGv'  # Replace with actual password

# File details
local_files = ['scrapelog.txt']  # Files to upload
remote_directory = "/htdocs"  # Correct remote directory

# Connect to the FTP server
ftp = FTP()
ftp.connect(ftp_server, ftp_port)
ftp.login(ftp_user, ftp_pass)

# Enable Passive Mode (Recommended)
ftp.set_pasv(True)

# Change to the correct directory
try:
    ftp.cwd(remote_directory)  # Try changing to /htdocs/images
except:
    print(f"Error: Directory {remote_directory} does not exist.")
    ftp.quit()
    exit()

# Upload files
for file in local_files:
    with open(file, 'rb') as f:
        ftp.storbinary(f'STOR {file}', f)
        print(f"Delivered {file}")

ftp.quit()

#print("\n scrapelog uploaded successfully!")
