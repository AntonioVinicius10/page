@echo off
title Instalacao do Agente de Monitoramento - Helpdesk
color 0A

echo ========================================================
echo   Instalando Agente de Monitoramento de Hardware
echo ========================================================
echo.

:: Verifica se a pasta C:\AgenteHelpdesk existe
if not exist "C:\AgenteHelpdesk\runner.vbs" (
    echo [ERRO] O arquivo C:\AgenteHelpdesk\runner.vbs nao foi encontrado!
    echo Garanta que extraiu a pasta exatamente no caminho C:\AgenteHelpdesk\
    echo.
    pause
    exit
)

:: Cria a tarefa agendada no Windows
schtasks /create /tn "AgenteMonitorHelpdesk" /tr "wscript.exe \"C:\AgenteHelpdesk\runner.vbs\"" /sc onlogon /rl highest /f

echo.
if %errorlevel% equ 0 (
    echo [SUCESSO] Agente instalado e registrado para iniciar com o Windows!
    echo Inicializando o agente agora...
    wscript.exe "C:\AgenteHelpdesk\runner.vbs"
) else (
    echo [ERRO] Falha ao registrar tarefa. Execute este arquivo como ADMINISTRADOR.
)

echo.
pause