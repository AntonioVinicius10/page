Set WshShell = CreateObject("WScript.Shell")

WshShell.CurrentDirectory = "C:\page"

WshShell.Run """C:\page\php\php.exe"" ""C:\page\monitor.php""", 0, False

Set WshShell = Nothing