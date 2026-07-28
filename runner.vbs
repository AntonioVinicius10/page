' C:\MeuMonitorAgent\runner.vbs
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "C:\MeuMonitorAgent\php\php.exe C:\MeuMonitorAgent\monitor.php", 0, False