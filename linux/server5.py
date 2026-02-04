import json
import time
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

# Path to tezt5.py in the same directory as the current script
script_dir = os.path.dirname(os.path.abspath(__file__))
tezt5_script_path = os.path.join(script_dir, 'tezt5.py')

# Chromium options arguments
arguments = [
    "-no-first-run",
   # "-incognito",
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

# Enhanced Cloudflare bypass function with better waiting
def bypass_cloudflare(url: str, retries: int = 5, log: bool = True) -> ChromiumPage:
    options = ChromiumOptions()
    
    # Apply all arguments
    for arg in arguments:
        options.set_argument(arg)
    
    # Set browser path and headless mode
    if browser_path and os.path.exists(browser_path):
        options.set_paths(browser_path=browser_path)
    
    options.headless(False)
    options.set_argument("--window-size=100,100")
    options.set_argument("--disable-blink-features=AutomationControlled")
    options.set_argument("--disable-dev-shm-usage")
    options.set_argument("--no-sandbox")
    
    driver = ChromiumPage(addr_or_opts=options)
    try:
        driver.get(url)
        
        # Wait initial 3 seconds for page to load
        time.sleep(3)
        
        # Check if we're on a Cloudflare page
        current_title = driver.title.lower()
        current_html = driver.html.lower()
        
        is_cloudflare = ("just a moment" in current_title or 
                        "checking your browser" in current_html or
                        "cloudflare" in current_html)
        
        if is_cloudflare:
            print("🛡️ Cloudflare detected, attempting to bypass...")
            cf_bypasser = CloudflareBypasser(driver, retries, log)
            cf_bypasser.bypass()
            
            # Wait longer after bypass attempt (5+ seconds)
            time.sleep(5)
            
            # Check again if bypass was successful
            new_title = driver.title.lower()
            if "just a moment" in new_title:
                print("⚠️ Cloudflare still present, waiting longer...")
                time.sleep(8)
                
                # Try to detect and click if there's a checkbox
                try:
                    iframes = driver.eles('tag:iframe')
                    for iframe in iframes:
                        try:
                            if "challenge" in iframe.src.lower() or "cloudflare" in iframe.src.lower():
                                driver.switch_to.frame(iframe)
                                checkboxes = driver.eles('tag:input[type="checkbox"]')
                                for checkbox in checkboxes:
                                    checkbox.click()
                                    print("✅ Clicked Cloudflare checkbox")
                                    time.sleep(3)
                                    break
                                driver.switch_to.default_frame()
                        except:
                            driver.switch_to.default_frame()
                            continue
                except Exception as e:
                    print(f"⚠️ Could not interact with iframe: {e}")
        
        # Additional wait to ensure page is fully loaded
        time.sleep(2)
        
        return driver
    except Exception as e:
        print(f"❌ Error in bypass_cloudflare: {e}")
        try:
            driver.quit()
        except:
            pass
        raise e

# Endpoint to get cookies
@app.get("/cookies", response_model=CookieResponse)
async def get_cookies(url: str, retries: int = 5):
    if not is_safe_url(url):
        raise HTTPException(status_code=400, detail="Invalid URL")
    try:
        driver = bypass_cloudflare(url, retries)
        time.sleep(1)
        cookies = driver.cookies(as_dict=True)
        user_agent = driver.user_agent
        driver.quit()
        return CookieResponse(cookies=cookies, user_agent=user_agent)
    except Exception as e:
        print(f"500 Internal Server Error: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# Endpoint to get HTML content and cookies
@app.get("/html")
async def get_html(url: str, retries: int = 5):
    if not is_safe_url(url):
        raise HTTPException(status_code=400, detail="Invalid URL")
    
    try:
        driver = bypass_cloudflare(url, retries)
        
        # Get the final URL (in case of redirects)
        final_url = driver.url
        
        # Extract HTML
        html = driver.html
        
        # Check for Cloudflare in the final HTML
        if "just a moment" in driver.title.lower() or "checking your browser" in html.lower():
            print("⚠️ Warning: Cloudflare may still be present in response")
        
        cookies_json = json.dumps(driver.cookies(as_dict=True))

        response = Response(content=html, media_type="text/html")
        response.headers["cookies"] = cookies_json
        response.headers["user_agent"] = driver.user_agent
        response.headers["final-url"] = final_url
        
        driver.quit()
        
        return response
    except Exception as e:
        print(f"❌ Error processing {url}: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# Function to get updated password
def get_updated_password():
    """Get the latest dynamic password from the source"""
    password_url = "https://www.seir-sanduk.com/linkzagledane.php?parola=FaeagaDs3AdKaAf9"
    
    print(f"🔑 Fetching updated password from: {password_url}")
    
    try:
        driver = bypass_cloudflare(password_url, retries=3)
        
        # Wait for redirects
        time.sleep(1)
        
        final_url = driver.url
        print(f"🔄 Final redirected URL: {final_url}")
        
        # Extract pass parameter
        match = re.search(r'pass=([a-zA-Z0-9]+)', final_url)
        if match:
            new_pass = match.group(1)
            print(f"✅ Found updated pass: {new_pass}")
        else:
            # Try to extract from page content
            html = driver.html
            pass_matches = re.findall(r'pass=([a-zA-Z0-9]{10,})', html)
            if pass_matches:
                new_pass = pass_matches[0]
                print(f"✅ Found pass in page: {new_pass}")
            else:
                print("⚠️ Could not find pass parameter, using default")
                new_pass = "22kalAdKaAf2l22"
        
        driver.quit()
        return new_pass
        
    except Exception as e:
        print(f"❌ Failed to get updated password: {e}")
        return "22kalAdKaAf2l22"  # Default fallback

# Update tezt5.py with new password
def update_tezt5_password(new_password):
    """Update the password in tezt5.py if it's using the default"""
    try:
        with open(tezt5_script_path, 'r') as f:
            content = f.read()
        
        # Check if using default pass
        if 'dynamic_pass = "22kalAdKaAf2l22"' in content:
            # Update the default pass
            updated_content = content.replace(
                'dynamic_pass = "22kalAdKaAf2l22"',
                f'dynamic_pass = "{new_password}"'
            )
        elif 'dynamic_pass = "11kalAdKaAde11sF8F02020404020402"' in content:
            # Update the fallback pass
            updated_content = content.replace(
                'dynamic_pass = "11kalAdKaAde11sF8F02020404020402"',
                f'dynamic_pass = "{new_password}"'
            )
        else:
            # Try to find any dynamic_pass assignment
            import re
            pattern = r'(dynamic_pass\s*=\s*"[^"]+")'
            if re.search(pattern, content):
                updated_content = re.sub(pattern, f'dynamic_pass = "{new_password}"', content)
            else:
                print("ℹ️ No dynamic_pass found in tezt5.py")
                return
        
        with open(tezt5_script_path, 'w') as f:
            f.write(updated_content)
        
        print(f"✅ Updated tezt5.py with new password: {new_password}")
            
    except Exception as e:
        print(f"⚠️ Could not update tezt5.py: {e}")

# Run on application startup
@app.on_event("startup")
async def startup_event():
    try:
        print("🚀 Starting Cloudflare Bypass Server...")
        print("🔄 Step 1: Getting latest dynamic password...")
        
        # Get the latest password
        updated_pass = get_updated_password()
        print(f"✅ Step 1 Complete: Dynamic password = {updated_pass}")
        
        # Update tezt5.py with new password
        print("🔄 Step 2: Updating tezt5.py with new password...")
        update_tezt5_password(updated_pass)
        print("✅ Step 2 Complete: tezt5.py updated")
        
        # Start tezt5.py in background
        print("🔄 Step 3: Starting scraping process...")
        subprocess.Popen(['python3', tezt5_script_path], cwd=script_dir)
        print(f"✅ Step 3 Complete: tezt5.py started from {tezt5_script_path}")
        
        print("✅ All startup steps completed successfully!")
        
    except Exception as e:
        print(f"❌ Startup failed: {e}")

# Health check endpoint
@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "cloudflare-bypass-api"}

# Password endpoint
@app.get("/get-pass")
async def get_pass():
    """Endpoint to get the latest dynamic password"""
    try:
        password = get_updated_password()
        return {"pass": password, "timestamp": time.time()}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# Main entry point
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Cloudflare bypass API")

    parser.add_argument("--nolog", action="store_true", help="Disable logging")
    parser.add_argument("--headless", action="store_true", help="Run in headless mode")
    parser.add_argument("--port", type=int, default=8000, help="Port to run on")

    args = parser.parse_args()
    
    print(f"🚀 Starting Cloudflare Bypass API on port {args.port}")
    print(f"📁 Script directory: {script_dir}")
    print(f"🔧 Arguments: headless={args.headless}, log={not args.nolog}")
    
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=args.port)