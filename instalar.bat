@echo off
:: C:\MeuMonitorAgent\install.bat
title Instalador - MeuMonitorAgent

cd /d "C:\MeuMonitorAgent"

echo =======================================================
echo     ABRINDO INTERFACE DE CONFIGURACAO DO AGENTE
echo =======================================================
echo.

:: 1. Inicia Servidor PHP temporario em segundo plano
start "" /B "C:\MeuMonitorAgent\php\php.exe" -S localhost:8080 -t "C:\MeuMonitorAgent"

:: 2. Aguarda 2 segundos e abre o navegador no Setup
timeout /t 2 /nobreak > nul
start http://localhost:8080/setup.php

echo.
echo Complete a configuracao no navegador que foi aberto.
echo Pressione qualquer tecla APOS clicar em SALVAR no navegador...
pause > nul

:: 3. Encerra o servidor PHP temporario do Setup
taskkill /IM php.exe /F > nul 2>&1

:: 4. Registra no Agendador de Tarefas do Windows (Rodar no Logon)
echo.
echo Registrando tarefa no Windows...
schtasks /create /tn "MeuMonitorAgent" /tr "wscript.exe \"C:\MeuMonitorAgent\runner.vbs\"" /sc onlogon /rl highest /f

echo.
echo =======================================================
echo     AGENTE INSTALADO E CONFIGURADO COM SUCESSO!
echo =======================================================
pause