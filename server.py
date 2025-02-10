import json
import re
import os
import subprocess
from urllib.parse import urlparse
from CloudflareBypasser import CloudflareBypasser
from DrissionPage import ChromiumPage, ChromiumOptions
from fastapi import FastAPI, HTTPException, Response
from pydantic import BaseModel
from typing import Dict
import argparse

app = FastAPI()

# Path to tezt3.py in the same directory as the current script
script_dir = os.path.dirname(os.path.abspath(__file__))
tezt3_script_path = os.path.join(script_dir, 'tezt3.py')

# Chromium options arguments
arguments = [
    "-no-first-run",
    "-force-color-profile=srgb",
    "-metrics-recording-only",
    "-password-store=basic",
    "-use-mock-keychain",
    "-export-tagged-pdf",
    "-no-default-browser-check",
    "-disable-background-mode",
    "-enable-features=NetworkService,NetworkServiceInProcess,LoadCryptoTokenExtension,PermuteTLSExtensions",
    "-disable-features=FlashDeprecationWarning,EnablePasswordsAccountStorage",
    "-deny-permission-prompts",
    "-disable-gpu",
    "-accept-lang=en-US",
]

browser_path = "/usr/bin/google-chrome"

# Pydantic model for the response
class CookieResponse(BaseModel):
    cookies: Dict[str, str]
    user_agent: str

# Function to check if the URL is safe
def is_safe_url(url: str) -> bool:
    parsed_url = urlparse(url)
    ip_pattern = re.compile(
        r"^(127\.0\.0\.1|localhost|0\.0\.0\.0|::1|10\.\d+\.\d+\.\d+|172\.1[6-9]\.\d+\.\d+|172\.2[0-9]\.\d+\.\d+|172\.3[0-1]\.\d+\.\d+|192\.168\.\d+\.\d+)$"
    )
    hostname = parsed_url.hostname
    if (hostname and ip_pattern.match(hostname)) or parsed_url.scheme == "file":
        return False
    return True

# Function to bypass Cloudflare protection
def bypass_cloudflare(url: str, retries: int, log: bool) -> ChromiumPage:
    from pyvirtualdisplay import Display

    options = ChromiumOptions()
    #options.set_argument("--auto-open-devtools-for-tabs", "true")
    options.set_paths(browser_path=browser_path).headless(False)
    options.set_argument("--window-size=100,100")

    driver = ChromiumPage(addr_or_opts=options)
    try:
        driver.get(url)
        cf_bypasser = CloudflareBypasser(driver, retries, log)
        cf_bypasser.bypass()
        return driver
    except Exception as e:
        driver.quit()
        raise e

# Endpoint to get cookies
@app.get("/cookies", response_model=CookieResponse)
async def get_cookies(url: str, retries: int = 5):
    if not is_safe_url(url):
        raise HTTPException(status_code=400, detail="Invalid URL")
    try:
        driver = bypass_cloudflare(url, retries, log)
        cookies = driver.cookies(as_dict=True)
        user_agent = driver.user_agent
        driver.quit()
        return CookieResponse(cookies=cookies, user_agent=user_agent)
    except Exception as e:
        #raise HTTPException(status_code=500)
        #raise HTTPException(status_code=500, detail=str(e))
        print ("500 Internal Server Error")

# Endpoint to get HTML content and cookies
@app.get("/html")
async def get_html(url: str, retries: int = 5):
    if not is_safe_url(url):
        raise HTTPException(status_code=400, detail="Invalid URL")
    try:
        driver = bypass_cloudflare(url, retries, log)
        html = driver.html
        cookies_json = json.dumps(driver.cookies(as_dict=True))

        response = Response(content=html, media_type="text/html")
        response.headers["cookies"] = cookies_json
        response.headers["user_agent"] = driver.user_agent
        driver.quit()
        return response
    except Exception as e:
        #raise HTTPException(status_code=500)
        print ("500 Internal Server Error")

# Run tezt3.py on application startup
@app.on_event("startup")
async def startup_event():
    try:
        # Run tezt3.py in a subprocess
        subprocess.Popen(['python', tezt3_script_path], cwd=script_dir)
        print(f"tezt3.py started successfully from {tezt3_script_path}")
    except Exception as e:
        print(f"Failed to start tezt3.py: {e}")

# Main entry point
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Cloudflare bypass API")

    parser.add_argument("--nolog", action="store_true", help="Disable logging")
    parser.add_argument("--headless", action="store_true", help="Run in headless mode")

    args = parser.parse_args()
    if args.nolog:
        log = False
    else:
        log = True

    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
