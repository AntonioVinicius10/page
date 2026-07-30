@echo off
title Desinstalador - Page Agent

echo =======================================================
echo           DESINSTALANDO O PAGE AGENT
echo =======================================================
echo.

:: =======================================================
:: 1. Remove a tarefa do Agendador
:: =======================================================

echo Removendo inicializacao automatica...

schtasks /delete /tn "PageAgent" /f

:: =======================================================
:: 2. Encerra somente o monitor.php
:: =======================================================

echo.
echo Encerrando o agente...

powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -Filter 'Name = ''php.exe''' | Where-Object { $_.CommandLine -like '*C:\page\monitor.php*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }"

echo.
echo =======================================================
echo     PAGE AGENT DESATIVADO COM SUCESSO!
echo =======================================================
echo.
echo A inicializacao automatica foi removida.
echo.
pause