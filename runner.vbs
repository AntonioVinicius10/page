Set WshShell = CreateObject("WScript.Shell")

' O primeiro parametro especifica o executavel PHP e o arquivo PHP
' O valor 0 oculta totalmente a janela
' O ultimo valor False indica para nao aguardar o termino da execucao
WshShell.Run """" & WshShell.CurrentDirectory & "\php\php.exe"" """ & WshShell.CurrentDirectory & "\monitor.php""", 0, False