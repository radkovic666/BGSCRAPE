import base64
import requests

# GitHub repository details
GITHUB_USER = "radkovic666"
GITHUB_REPO = "bgtv"
GITHUB_TOKEN = "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"  # Replace with your token

# GitHub API URL for repo contents
GITHUB_API_URL = f"https://api.github.com/repos/{GITHUB_USER}/{GITHUB_REPO}/contents/"

# Files to upload
FILES = ["playlist.m3u"]

def upload_file(file_path):
    """Uploads a file to GitHub repo."""
    with open(file_path, "rb") as file:
        content = base64.b64encode(file.read()).decode()

    file_name = file_path.split("/")[-1]
    url = f"{GITHUB_API_URL}{file_name}"

    # Get existing file info (to update if it already exists)
    response = requests.get(url, headers={"Authorization": f"token {GITHUB_TOKEN}"})
    sha = response.json().get("sha", "")

    # Prepare request payload
    payload = {
        "message": f"Upload {file_name}",
        "content": content,
        "branch": "main"
    }
    if sha:
        payload["sha"] = sha  # Required for updating existing files

    # Upload file
    response = requests.put(url, json=payload, headers={"Authorization": f"token {GITHUB_TOKEN}"})
    
    if response.status_code in [200, 201]:
        print(f"Successfully delivered {file_name}")
    else:
        print(f"Failed to deliver {file_name}: {response.json()}")

# Upload all files
for file in FILES:
    upload_file(file)
