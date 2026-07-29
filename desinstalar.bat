@echo off
:: C:\page\desinstalar.bat
title Desinstalador - Page Agent

echo =======================================================
echo           DESINSTALANDO O PAGE AGENT
echo =======================================================
echo.

:: 1. Remove a tarefa do Agendador de Tarefas do Windows
echo Removendo a inicializacao automatica do Windows...
schtasks /delete /tn "PageAgent" /f > nul 2>&1

:: 2. Encerra o processo do PHP que esta rodando em segundo plano
echo Encerrando o processo do agente...
taskkill /IM php.exe /F > nul 2>&1

echo.
echo =======================================================
echo     AGENTE DESATIVADO E REMOVIDO COM SUCESSO!
echo =======================================================
pause
