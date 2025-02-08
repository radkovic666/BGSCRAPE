@echo off
REM Get the current directory
set SCRIPT_DIR=%~dp0

REM Change to the script directory
cd /d "%SCRIPT_DIR%"

REM Check if virtual environment already exists
if exist venv (
    echo Virtual environment already exists.
) else (
    REM Create virtual environment
    python -m venv venv
    echo Virtual environment created.
)

REM Activate the virtual environment
call venv\Scripts\activate.bat

REM Install requirements
if exist server_requirements.txt (
    pip install -r server_requirements.txt
    echo Requirements installed.
) else (
    echo server_requirements.txt not found. Skipping requirements installation.
)

REM Create the TVWoodpecker.bat file with admin elevation check
echo @echo off > TVWoodpecker.bat
echo :: Check for Administrator privileges >> TVWoodpecker.bat
echo net session ^>nul 2^>^&1 >> TVWoodpecker.bat
echo if %%errorlevel%% neq 0 ^( >> TVWoodpecker.bat
echo     :: Relaunch as Administrator >> TVWoodpecker.bat
echo     echo Requesting Administrator privileges... >> TVWoodpecker.bat
echo     powershell -Command "Start-Process '%%~f0' -Verb runAs" >> TVWoodpecker.bat
echo     exit /b >> TVWoodpecker.bat
echo ^) >> TVWoodpecker.bat

REM Change to the script directory and activate virtual environment in TVWoodpecker.bat
echo REM Get the current directory >> TVWoodpecker.bat
echo set SCRIPT_DIR=%%~dp0 >> TVWoodpecker.bat
echo REM Change to the script directory >> TVWoodpecker.bat
echo cd /d "%%SCRIPT_DIR%%" >> TVWoodpecker.bat
echo call venv\Scripts\activate.bat >> TVWoodpecker.bat

REM Run the Python server in TVWoodpecker.bat
echo python server.py >> TVWoodpecker.bat

REM Add pause to keep the window open in TVWoodpecker.bat
echo pause >> TVWoodpecker.bat
echo TVWoodpecker.bat created with Administrator elevation.

REM Create a shortcut to TVWoodpecker.bat
set STARTUP_FOLDER=%appdata%\Microsoft\Windows\Start Menu\Programs\Startup
set SHORTCUT_PATH=%STARTUP_FOLDER%\TVWoodpecker.lnk

REM Use PowerShell to create the shortcut
powershell -Command ^
    $WScriptShell = New-Object -ComObject WScript.Shell; ^
    $Shortcut = $WScriptShell.CreateShortcut('%SHORTCUT_PATH%'); ^
    $Shortcut.TargetPath = '%SCRIPT_DIR%TVWoodpecker.bat'; ^
    $Shortcut.Save()

echo Shortcut to TVWoodpecker.bat added to startup successfully.

REM Schedule daily restart at midnight
echo Scheduling daily restart at midnight...
powershell -Command ^
    $action = New-ScheduledTaskAction -Execute 'shutdown.exe' -Argument '/r /f /t 0'; ^
    $trigger = New-ScheduledTaskTrigger -Daily -At '00:00AM'; ^
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries; ^
    Register-ScheduledTask -TaskName 'DailyRestart' -Action $action -Trigger $trigger -Settings $settings -Description 'Restart the computer daily at midnight';
echo Daily restart scheduled successfully.

echo Installation of BGSCRAPE successful.

::del "%SCRIPT_DIR%setup.bat"
del "%SCRIPT_DIR%exiter.zip"

pause
