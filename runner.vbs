Set WshShell = CreateObject("WScript.Shell")

' Garante que o PHP seja executado dentro de C:\page
WshShell.CurrentDirectory = "C:\page"

' Registra que o runner foi iniciado
Set LogFile = WshShell.OpenTextFile("C:\page\runner.log", 8, True)
LogFile.WriteLine Now & " - runner.vbs iniciado"
LogFile.Close

' Executa exatamente o mesmo comando que funciona manualmente
WshShell.Run "cmd.exe /c cd /d C:\page && .\php\php.exe monitor.php", 0, False

Set WshShell = Nothing