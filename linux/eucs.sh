#!/bin/bash

# Enhanced Ubuntu System Cleanup Script
# More aggressive cleaning including browser caches and user junk

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}   ENHANCED UBUNTU CLEANUP SCRIPT     ${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Timestamp: $(date)"
echo ""

# Function to display disk usage before cleanup
show_disk_usage() {
    echo -e "${YELLOW}Current disk usage:${NC}"
    df -h /
    echo ""
}

# ==================== BROWSER CLEANUP ====================

clean_google_chrome() {
    echo -e "${YELLOW}[1] Cleaning Google Chrome completely...${NC}"
    
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            user=$(basename "$user_dir")
            
            # Skip if not a valid user directory
            [ ! -d "$user_dir" ] && continue
            
            echo "  Cleaning Chrome for user: $user"
            
            # Google Chrome directories
            chrome_dirs=(
                "$user_dir/.cache/google-chrome"
                "$user_dir/.config/google-chrome"
                "$user_dir/.local/share/google-chrome"
                "$user_dir/.cache/chrome-sandbox"
            )
            
            # Chromium directories (if exists)
            chromium_dirs=(
                "$user_dir/.cache/chromium"
                "$user_dir/.config/chromium"
                "$user_dir/.local/share/chromium"
            )
            
            # Specific cache locations to clean
            cache_locations=(
                "$user_dir/.cache/google-chrome/Default/Cache"
                "$user_dir/.cache/google-chrome/Default/Code Cache"
                "$user_dir/.cache/google-chrome/Default/Service Worker/CacheStorage"
                "$user_dir/.cache/google-chrome/Default/Service Worker/ScriptCache"
                "$user_dir/.cache/google-chrome/Default/Media Cache"
                "$user_dir/.cache/google-chrome/Default/GPUCache"
                "$user_dir/.config/google-chrome/Default/Local Storage"
                "$user_dir/.config/google-chrome/Default/Session Storage"
                "$user_dir/.config/google-chrome/Default/Application Cache"
                "$user_dir/.config/google-chrome/Default/IndexedDB"
                "$user_dir/.config/google-chrome/Default/File System"
            )
            
            # Clean Chrome cache locations
            for location in "${cache_locations[@]}"; do
                if [ -d "$location" ]; then
                    rm -rf "$location"/*
                    echo "    Cleared: $(basename "$location")"
                fi
            done
            
            # Remove Chrome crash reports
            find "$user_dir/.config/google-chrome" -name "*.dmp" -type f -delete 2>/dev/null
            find "$user_dir/.config/google-chrome" -name "Crash Reports" -type d -exec rm -rf {} + 2>/dev/null
            
            # Remove old Chrome logs
            find "$user_dir/.config/google-chrome" -name "*.log" -type f -delete 2>/dev/null
            find "$user_dir/.config/google-chrome" -name "*.log.*" -type f -delete 2>/dev/null
            
            # Clean Chrome extensions cache
            if [ -d "$user_dir/.config/google-chrome/Default/Extensions" ]; then
                find "$user_dir/.config/google-chrome/Default/Extensions" -name "_metadata" -type d -exec rm -rf {} + 2>/dev/null
            fi
            
            # Clean Chrome media licenses
            find "$user_dir/.config/google-chrome" -name "*.h264" -type f -delete 2>/dev/null
            find "$user_dir/.config/google-chrome" -name "*.vp9" -type f -delete 2>/dev/null
            
            # Remove Chrome Favicons database (will regenerate)
            if [ -f "$user_dir/.config/google-chrome/Default/Favicons" ]; then
                rm -f "$user_dir/.config/google-chrome/Default/Favicons"
            fi
            
            # Remove Chrome History and Top Sites (optional - comment out if you want to keep history)
            # rm -f "$user_dir/.config/google-chrome/Default/History"
            # rm -f "$user_dir/.config/google-chrome/Default/Top Sites"
            # rm -f "$user_dir/.config/google-chrome/Default/Shortcuts"
            
            # Clean Google Chrome Crashpad (crash reports)
            if [ -d "$user_dir/.config/google-chrome/Crashpad" ]; then
                rm -rf "$user_dir/.config/google-chrome/Crashpad"/*
            fi
            
            # Clean Chromium if installed
            for chromium_dir in "${chromium_dirs[@]}"; do
                if [ -d "$chromium_dir" ]; then
                    find "$chromium_dir" -type f -name "*.log" -delete
                    find "$chromium_dir" -type f -name "*.dmp" -delete
                    # Clean cache directories
                    cache_dirs=("$chromium_dir/Default/Cache" "$chromium_dir/Default/Code Cache")
                    for cache_dir in "${cache_dirs[@]}"; do
                        [ -d "$cache_dir" ] && rm -rf "$cache_dir"/*
                    done
                fi
            done
            
        done
    fi
    
    echo -e "${GREEN}✓ Google Chrome/Chromium completely cleaned${NC}"
    echo ""
}

clean_firefox() {
    echo -e "${YELLOW}[2] Cleaning Firefox completely...${NC}"
    
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            user=$(basename "$user_dir")
            echo "  Cleaning Firefox for user: $user"
            
            # Firefox profile directories
            firefox_profiles="$user_dir/.mozilla/firefox"
            
            if [ -d "$firefox_profiles" ]; then
                for profile in "$firefox_profiles"/*.default*; do
                    if [ -d "$profile" ]; then
                        # Clean cache
                        rm -rf "$profile/cache2"/*
                        rm -rf "$profile/startupCache"/*
                        rm -rf "$profile/thumbnails"/*
                        
                        # Clean storage
                        rm -rf "$profile/storage"/*
                        
                        # Remove crash reports
                        rm -rf "$profile/crashes"/*
                        rm -rf "$profile/minidumps"/*
                        
                        # Remove old logs
                        find "$profile" -name "*.log" -type f -delete
                        find "$profile" -name "*.bak" -type f -delete
                        
                        # Clean IndexedDB
                        rm -rf "$profile/indexedDB"/*
                        
                        # Clean Service Workers
                        rm -rf "$profile/serviceworker"/*
                        
                        echo "    Cleared profile: $(basename "$profile")"
                    fi
                done
            fi
        done
    fi
    
    echo -e "${GREEN}✓ Firefox completely cleaned${NC}"
    echo ""
}

# ==================== SYSTEM JUNK CLEANUP ====================

clean_system_junk() {
    echo -e "${YELLOW}[3] Cleaning system junk files...${NC}"
    
    # Clean old crash reports
    echo "  Cleaning crash reports..."
    rm -rf /var/crash/*
    
    # Clean old apt lists
    echo "  Cleaning old apt lists..."
    rm -rf /var/lib/apt/lists/partial/*
    rm -rf /var/lib/apt/lists/*_*
    rm -rf /var/lib/apt/lists/archive*
    
    # Clean old configuration files
    echo "  Cleaning orphaned config files..."
    find /etc -name "*.dpkg-old" -type f -delete
    find /etc -name "*.dpkg-dist" -type f -delete
    find /etc -name "*.ucf-old" -type f -delete
    find /etc -name "*.ucf-dist" -type f -delete
    find /etc -name "*.ucf-new" -type f -delete
    
    # Clean old kernel modules
    echo "  Cleaning old kernel modules..."
    find /lib/modules -name "*.ko" -type f | grep -v "$(uname -r)" | xargs rm -f 2>/dev/null
    
    # Clean pip cache
    echo "  Cleaning pip cache..."
    rm -rf /root/.cache/pip
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -rf "$user_dir/.cache/pip" 2>/dev/null
        done
    fi
    
    # Clean npm cache
    echo "  Cleaning npm cache..."
    rm -rf /root/.npm
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -rf "$user_dir/.npm" 2>/dev/null
        done
    fi
    
    # Clean Docker builder cache
    if command -v docker &> /dev/null; then
        echo "  Cleaning Docker builder cache..."
        docker builder prune -af
    fi
    
    # Clean Trash for all users
    echo "  Emptying trash bins..."
    rm -rf /root/.local/share/Trash/*
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -rf "$user_dir/.local/share/Trash"/* 2>/dev/null
        done
    fi
    
    # Clean old .DS_Store files (from macOS)
    echo "  Removing .DS_Store files..."
    find /home -name ".DS_Store" -type f -delete 2>/dev/null
    find /home -name "._*" -type f -delete 2>/dev/null
    
    # Clean old thumbnails
    echo "  Cleaning old thumbnails..."
    rm -rf /root/.thumbnails
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -rf "$user_dir/.thumbnails" 2>/dev/null
            rm -rf "$user_dir/.cache/thumbnails" 2>/dev/null
        done
    fi
    
    # Clean recent documents
    echo "  Cleaning recent documents..."
    rm -rf /root/.local/share/recently-used.xbel
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -f "$user_dir/.local/share/recently-used.xbel" 2>/dev/null
        done
    fi
    
    # Clean desktop entries cache
    echo "  Cleaning desktop entries cache..."
    rm -rf /root/.local/share/applications/mimeinfo.cache
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            rm -f "$user_dir/.local/share/applications/mimeinfo.cache" 2>/dev/null
        done
    fi
    
    echo -e "${GREEN}✓ System junk files cleaned${NC}"
    echo ""
}

# ==================== USER DOWNLOADS CLEANUP ====================

clean_downloads() {
    echo -e "${YELLOW}[4] Cleaning old downloads...${NC}"
    
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            user=$(basename "$user_dir")
            downloads_dir="$user_dir/Downloads"
            
            if [ -d "$downloads_dir" ]; then
                echo "  Cleaning downloads for user: $user"
                
                # Files older than 30 days
                find "$downloads_dir" -type f -mtime +30 -delete
                
                # Empty directories
                find "$downloads_dir" -type d -empty -delete
                
                # Specific junk files
                find "$downloads_dir" -name "*.tmp" -type f -delete
                find "$downloads_dir" -name "*.temp" -type f -delete
                find "$downloads_dir" -name "*.log" -type f -delete
                find "$downloads_dir" -name "*.cache" -type f -delete
                find "$downloads_dir" -name "*.part" -type f -delete
                find "$downloads_dir" -name "*.crdownload" -type f -delete
                
                # Large files check
                large_files=$(find "$downloads_dir" -type f -size +500M 2>/dev/null | head -5)
                if [ -n "$large_files" ]; then
                    echo "    Warning: Large files found (>500MB):"
                    echo "$large_files" | while read file; do
                        echo "      - $file"
                    done
                fi
            fi
        done
    fi
    
    echo -e "${GREEN}✓ Old downloads cleaned${NC}"
    echo ""
}

# ==================== APPLICATION SPECIFIC CLEANUP ====================

clean_app_specific() {
    echo -e "${YELLOW}[5] Cleaning application-specific cache...${NC}"
    
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            user=$(basename "$user_dir")
            echo "  Cleaning apps for user: $user"
            
            # Discord cache
            discord_cache="$user_dir/.config/discord/Cache"
            [ -d "$discord_cache" ] && rm -rf "$discord_cache"/*
            
            # Spotify cache
            spotify_cache="$user_dir/.cache/spotify"
            [ -d "$spotify_cache" ] && rm -rf "$spotify_cache"/*
            
            # VSCode cache
            vscode_cache="$user_dir/.config/Code/Cache"
            [ -d "$vscode_cache" ] && rm -rf "$vscode_cache"/*
            vscode_cachedata="$user_dir/.config/Code/CachedData"
            [ -d "$vscode_cachedata" ] && rm -rf "$vscode_cachedata"/*
            
            # Zoom cache
            zoom_cache="$user_dir/.zoom/cache"
            [ -d "$zoom_cache" ] && rm -rf "$zoom_cache"/*
            
            # Slack cache
            slack_cache="$user_dir/.config/Slack/Cache"
            [ -d "$slack_cache" ] && rm -rf "$slack_cache"/*
            
            # Telegram cache
            telegram_cache="$user_dir/.local/share/TelegramDesktop/tdata"
            [ -d "$telegram_cache" ] && find "$telegram_cache" -name "cache*" -type f -delete
            
            # Steam cache
            steam_cache="$user_dir/.steam/steam/appcache"
            [ -d "$steam_cache" ] && rm -rf "$steam_cache"/*
            
            # Wine cache
            wine_cache="$user_dir/.cache/wine"
            [ -d "$wine_cache" ] && rm -rf "$wine_cache"/*
            
            # LibreOffice cache
            libreoffice_cache="$user_dir/.cache/libreoffice"
            [ -d "$libreoffice_cache" ] && rm -rf "$libreoffice_cache"/*
            
            # GIMP cache
            gimp_cache="$user_dir/.cache/gimp"
            [ -d "$gimp_cache" ] && rm -rf "$gimp_cache"/*
            
            # Inkscape cache
            inkscape_cache="$user_dir/.cache/inkscape"
            [ -d "$inkscape_cache" ] && rm -rf "$inkscape_cache"/*
            
            # Minecraft cache
            minecraft_cache="$user_dir/.minecraft/cache"
            [ -d "$minecraft_cache" ] && rm -rf "$minecraft_cache"/*
            
            # Transmission cache
            transmission_cache="$user_dir/.config/transmission/resume"
            [ -d "$transmission_cache" ] && find "$transmission_cache" -name "*.resume" -type f -delete
            
        done
    fi
    
    # System-wide application caches
    echo "  Cleaning system application caches..."
    
    # Clean GNOME tracker cache
    [ -d "/var/cache/tracker" ] && rm -rf /var/cache/tracker/*
    
    # Clean systemd journal vacuum
    journalctl --vacuum-size=500M
    
    # Clean CUPS cache
    [ -d "/var/cache/cups" ] && rm -rf /var/cache/cups/*
    
    # Clean font cache
    fc-cache -f
    
    echo -e "${GREEN}✓ Application-specific cache cleaned${NC}"
    echo ""
}

# ==================== PACKAGE MANAGEMENT CLEANUP ====================

clean_packages() {
    echo -e "${YELLOW}[6] Cleaning package management files...${NC}"
    
    # Remove old kernels (keep current and one previous)
    echo "  Removing old kernels..."
    apt-get autoremove --purge -y
    
    # Remove orphaned packages
    echo "  Removing orphaned packages..."
    deborphan | xargs apt-get remove -y --purge 2>/dev/null || true
    
    # Clean APT cache aggressively
    echo "  Cleaning APT cache..."
    apt-get clean
    apt-get autoclean
    
    # Remove old configuration files
    echo "  Removing old config files..."
    dpkg -l | grep '^rc' | awk '{print $2}' | xargs dpkg --purge 2>/dev/null || true
    
    # Clean PIP cache globally
    echo "  Cleaning global pip cache..."
    pip cache purge 2>/dev/null || true
    pip3 cache purge 2>/dev/null || true
    
    # Clean Flatpak cache
    if command -v flatpak &> /dev/null; then
        echo "  Cleaning Flatpak cache..."
        flatpak uninstall --unused -y
        flatpak repair
    fi
    
    echo -e "${GREEN}✓ Package management cleaned${NC}"
    echo ""
}

# ==================== LOGS CLEANUP ====================

clean_logs_aggressive() {
    echo -e "${YELLOW}[7] Cleaning system logs aggressively...${NC}"
    
    # Clear all logs except current
    echo "  Rotating and cleaning logs..."
    logrotate -f /etc/logrotate.conf
    
    # Clean journal logs (keep only 3 days)
    journalctl --vacuum-time=3d
    
    # Remove old log files
    find /var/log -name "*.gz" -type f -delete
    find /var/log -name "*.1" -type f -delete
    find /var/log -name "*.old" -type f -delete
    find /var/log -name "*.[0-9]" -type f -delete
    find /var/log -name "*.[0-9][0-9]" -type f -delete
    
    # Clear specific application logs
    truncate -s 0 /var/log/syslog
    truncate -s 0 /var/log/kern.log
    truncate -s 0 /var/log/auth.log
    truncate -s 0 /var/log/dpkg.log
    
    # Clean user application logs
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            find "$user_dir" -name "*.log" -type f -delete 2>/dev/null
        done
    fi
    
    echo -e "${GREEN}✓ Logs cleaned aggressively${NC}"
    echo ""
}

# ==================== TEMPORARY FILES CLEANUP ====================

clean_temp_aggressive() {
    echo -e "${YELLOW}[8] Cleaning temporary files aggressively...${NC}"
    
    # Clean system temporary files
    echo "  Cleaning /tmp..."
    find /tmp -type f -atime +1 -delete
    find /tmp -type d -empty -delete
    
    # Clean /var/tmp
    echo "  Cleaning /var/tmp..."
    find /var/tmp -type f -atime +7 -delete
    find /var/tmp -type d -empty -delete
    
    # Clean browser temporary files
    echo "  Cleaning browser temp files..."
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            # Chrome temp files
            find "$user_dir" -name "*.crdownload" -type f -delete 2>/dev/null
            find "$user_dir" -name "*.part" -type f -delete 2>/dev/null
            
            # Firefox temp files
            find "$user_dir" -name "*.tmp" -type f -delete 2>/dev/null
        done
    fi
    
    # Clean .cache directory completely for files older than 30 days
    echo "  Cleaning old cache files..."
    if [ -d "/home" ]; then
        for user_dir in /home/*; do
            find "$user_dir/.cache" -type f -atime +30 -delete 2>/dev/null
            find "$user_dir/.cache" -type d -empty -delete 2>/dev/null
        done
    fi
    
    echo -e "${GREEN}✓ Temporary files cleaned aggressively${NC}"
    echo ""
}

# ==================== DISK ANALYSIS ====================

analyze_disk() {
    echo -e "${YELLOW}[9] Analyzing disk usage...${NC}"
    
    echo "  Top 10 largest directories in /home:"
    if [ -d "/home" ]; then
        du -h /home/* 2>/dev/null | sort -rh | head -10
    fi
    
    echo ""
    echo "  Top 10 largest directories in /var:"
    du -h /var/* 2>/dev/null | sort -rh | head -10
    
    echo ""
    echo "  Files larger than 1GB:"
    find / -type f -size +1G 2>/dev/null | grep -v "/proc/" | grep -v "/sys/" | grep -v "/dev/" | head -10
    
    echo ""
    echo -e "${GREEN}✓ Disk analysis complete${NC}"
    echo ""
}

# ==================== MAIN EXECUTION ====================

main() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}     STARTING ENHANCED CLEANUP        ${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    show_disk_usage
    
    # Run cleanup functions
    clean_google_chrome
    clean_firefox
    clean_system_junk
    clean_downloads
    clean_app_specific
    clean_packages
    clean_logs_aggressive
    clean_temp_aggressive
    
    # Optional: Run disk analysis (commented by default)
    # analyze_disk
    
    # Final disk usage
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}      CLEANUP COMPLETED SUCCESSFULLY   ${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Final disk usage:${NC}"
    df -h /
    echo ""
    echo -e "${GREEN}Cleanup finished at $(date)${NC}"
    echo -e "${BLUE}========================================${NC}"
}

# Run main function
main