@echo off
:: C:\page\instalar.bat
title Instalador - Page Agent

:: Garante execução no diretório do script
cd /d "%~dp0"

echo =======================================================
echo     ABRINDO INTERFACE DE CONFIGURACAO DO AGENTE
echo =======================================================
echo.

:: 1. Inicia Servidor PHP temporario em segundo plano
start "" /B "%~dp0php\php.exe" -S localhost:8080 -t "%~dp0"

:: 2. Aguarda 2 segundos e abre o navegador no Setup
timeout /t 2 /nobreak > nul
start http://localhost:8080/index.php

echo.
echo Complete a configuracao no navegador que foi aberto.
echo Pressione qualquer tecla APOS clicar em SALVAR no navegador...
pause > nul

:: 3. Encerra o servidor PHP temporario do Setup
taskkill /IM php.exe /F > nul 2>&1

:: 4. Registra no Agendador de Tarefas do Windows (Roda no Logon com privilegios altos)
echo.
echo Registrando tarefa no Windows...
schtasks /create /tn "PageAgent" /tr "wscript.exe \"C:\page\runner.vbs\"" /sc onlogon /delay 0002:00 /rl highest /f

:: 5. Executa o agente imediatamente para comecar o monitoramento agora
echo Iniciando o agente em segundo plano...
start wscript.exe "%~dp0runner.vbs"

echo.
echo =======================================================
echo     AGENTE INSTALADO E CONFIGURADO COM SUCESSO!
echo =======================================================
pause