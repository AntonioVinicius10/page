Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd /c cd /d C:\page && .\php\php.exe monitor.php", 0, False