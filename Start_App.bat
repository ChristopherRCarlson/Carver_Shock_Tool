@echo off
echo HOSTING CARVER DATABASE...
echo DO NOT CLOSE THIS WINDOW.
echo.
echo Your Host IP is: 192.168.200.157
echo Share this link: http://192.168.200.157:8000/system_files/internal_entry.php
echo.
php -S 192.168.200.157:8000 -t .
pause