@echo off
title Instalador - Page Agent

cd /d "%~dp0"

echo =======================================================
echo        ABRINDO CONFIGURACAO DO PAGE AGENT
echo =======================================================
echo.

:: =======================================================
:: 1. Inicia servidor PHP temporario
:: =======================================================

echo Iniciando servidor PHP...

start "" /B "%~dp0php\php.exe" -S 127.0.0.1:8080 -t "%~dp0"

timeout /t 2 /nobreak > nul

start "" "http://127.0.0.1:8080/index.php"

echo.
echo =======================================================
echo Configure o agente no navegador.
echo.
echo Depois de clicar em SALVAR, volte para esta janela
echo e pressione qualquer tecla.
echo =======================================================
echo.

pause > nul

:: =======================================================
:: 2. Encerra PHP usado pela configuracao
:: =======================================================

echo.
echo Encerrando servidor de configuracao...

taskkill /IM php.exe /F > nul 2>&1

:: =======================================================
:: 3. Remove tarefa anterior
:: =======================================================

echo.
echo Removendo tarefa antiga...

schtasks /delete /tn "PageAgent" /f > nul 2>&1

:: =======================================================
:: 4. Registra PageAgent para iniciar no LOGIN
:: =======================================================

echo.
echo Registrando PageAgent no Windows...
echo.

schtasks /create ^
 /tn "PageAgent" ^
 /tr "wscript.exe C:\page\runner.vbs" ^
 /sc onlogon ^
 /rl HIGHEST ^
 /f

if errorlevel 1 (
    echo.
    echo =======================================================
    echo ERRO AO REGISTRAR A TAREFA!
    echo =======================================================
    echo.
    pause
    exit /b 1
)

echo.
echo PageAgent registrado com sucesso!

:: =======================================================
:: 5. Inicia agora
:: =======================================================

echo.
echo Iniciando PageAgent...

start "" wscript.exe "C:\page\runner.vbs"

echo.
echo =======================================================
echo       PAGE AGENT INSTALADO COM SUCESSO!
echo =======================================================
echo.
echo O agente sera iniciado automaticamente quando
echo voce entrar no Windows.
echo.
echo =======================================================
echo.

pause