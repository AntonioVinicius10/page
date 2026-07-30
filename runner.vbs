Set WshShell = CreateObject("WScript.Shell")

' Define o diretorio de trabalho
WshShell.CurrentDirectory = "C:\page"

' Executa exatamente o mesmo comando que funciona manualmente
WshShell.Run "cmd.exe /c cd /d C:\page && .\php\php.exe monitor.php", 0, False

Set WshShell = Nothing