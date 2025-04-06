from ftplib import FTP

# FTP server details
ftp_server = '185.27.134.11'  # Direct FTP IP address
ftp_port = 21
ftp_user = 'if0_38223125'
ftp_pass = 'PZei3Ju4KnGv'  # Replace with actual password

# File details
local_txt_file = 'scrapelog.txt'
local_html_file = 'scrapelog.html'
remote_directory = "/htdocs"

# HTML Template
html_template = """
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BGSCRAPE Latest Report</title>
    <style>
        :root {
            --dark-bg: #0d1117;
            --darker-bg: #010409;
            --dark-border: #30363d;
            --dark-text: #e6edf3;
            --accent-blue: #2f81f7;
            --github-gray: #8b949e;
        }

        body { 
            font-family: 'SF Pro Text', -apple-system, BlinkMacSystemFont, sans-serif; 
            background-color: var(--darker-bg); 
            color: var(--dark-text);
            margin: 2rem;
        }
        
        h2 {
            color: #ffffff;
            border-bottom: 1px solid var(--dark-border);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .info { color: #2ea043; }  /* GitHub green */
        .warning { color: #d29922; }  /* GitHub orange */
        .error { color: #da3633; }  /* GitHub red */
        .datetime { color: var(--github-gray); }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: var(--dark-bg);
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid var(--dark-border);
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            line-height: 1.5;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <h2>📄 BGSCRAPE Operational Report</h2>
    <pre>
"""

def convert_log_to_html(txt_file, html_file):
    with open(txt_file, 'r', encoding='utf-8') as infile, open(html_file, 'w', encoding='utf-8') as outfile:
        outfile.write(html_template)
        
        for line in infile:
            if "[INFO]" in line:
                formatted_line = f'<span class="info">{line.strip()}</span>'
            elif "[WARNING]" in line:
                formatted_line = f'<span class="warning">{line.strip()}</span>'
            elif "[ERROR]" in line:
                formatted_line = f'<span class="error">{line.strip()}</span>'
            else:
                formatted_line = line.strip()
            
            outfile.write(formatted_line + "\n")
        
        outfile.write("""</pre></body></html>""")

# Convert log to HTML
convert_log_to_html(local_txt_file, local_html_file)

# Upload HTML file to FTP
#ftp = FTP()
#ftp.connect(ftp_server, ftp_port)
#ftp.login(ftp_user, ftp_pass)
#ftp.set_pasv(True)

# Change to the correct directory
#$try:
#    ftp.cwd(remote_directory)
#except:
#    print(f"Error: Directory {remote_directory} does not exist.")
#    ftp.quit()
#    exit()

# Upload the converted HTML file
#with open(local_html_file, 'rb') as f:
#    ftp.storbinary(f'STOR {local_html_file}', f)
#    print(f"Delivered {local_html_file}")

#ftp.quit()
