@echo off
title Instalador - Page Agent

cd /d "%~dp0"

echo =======================================================
echo          PAGE AGENT - INSTALACAO
echo =======================================================
echo.

:: Verifica se estamos em C:\page
if not exist "%~dp0monitor.php" (
    echo ERRO: monitor.php nao encontrado.
    echo.
    pause
    exit /b 1
)

if not exist "%~dp0php\php.exe" (
    echo ERRO: php.exe nao encontrado.
    echo.
    pause
    exit /b 1
)

echo Arquivos encontrados.
echo.

:: =======================================================
:: CONFIGURACAO
:: =======================================================

echo Iniciando servidor de configuracao...

start "" /B "%~dp0php\php.exe" -S 127.0.0.1:8080 -t "%~dp0"

timeout /t 2 /nobreak >nul

start "" "http://127.0.0.1:8080/index.php"

echo.
echo =======================================================
echo Configure o agente no navegador.
echo.
echo Depois de clicar em SALVAR, volte aqui.
echo =======================================================
echo.

pause >nul

:: =======================================================
:: ENCERRA SERVIDOR DE CONFIGURACAO
:: =======================================================

echo.
echo Encerrando servidor de configuracao...

taskkill /IM php.exe /F >nul 2>&1

:: =======================================================
:: REMOVE TAREFA ANTIGA
:: =======================================================

echo.
echo Removendo tarefa anterior...

schtasks /delete /tn "PageAgent" /f >nul 2>&1

:: =======================================================
:: CRIA TAREFA NO LOGIN DO WINDOWS
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
echo.

:: =======================================================
:: INICIA AGORA
:: =======================================================

echo Iniciando PageAgent...

start "" wscript.exe "C:\page\runner.vbs"

echo.
echo =======================================================
echo       PAGE AGENT INSTALADO COM SUCESSO
echo =======================================================
echo.
echo O agente sera iniciado automaticamente no login.
echo.
echo =======================================================

pause