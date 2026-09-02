@echo off
setlocal

rem Avvia il server PHP built-in con document root la cartella corrente,
rem cosi' http://localhost/sniffetto apre la rotta "/" del progetto sniffetto.
rem Nota: la porta 80 richiede privilegi di Amministratore e che nessun altro
rem servizio (IIS, Apache, ecc.) la stia gia' usando.

set "DOCROOT=%~dp0"
if "%DOCROOT:~-1%"=="\" set "DOCROOT=%DOCROOT:~0,-1%"

where php >nul 2>nul
if errorlevel 1 (
    echo ERRORE: 'php' non trovato nel PATH. Installa PHP o aggiungilo al PATH.
    pause
    exit /b 1
)

rem 'php -S' usa la SAPI cli-server, che su questa installazione NON carica
rem automaticamente lo stesso php.ini della CLI normale (niente pdo_mysql
rem configurato correttamente -> "Could not connect to database"). Recuperiamo
rem l'ini effettivamente usato da 'php' e lo passiamo esplicitamente con -c.
set "PHP_INI="
for /f "usebackq delims=" %%L in (`php --ini 2^>nul ^| findstr /C:"Loaded Configuration File"`) do set "PHP_INI_LINE=%%L"
for /f "tokens=1* delims=:" %%A in ("%PHP_INI_LINE%") do set "PHP_INI=%%B"
:trimPhpIni
if "%PHP_INI:~0,1%"==" " (
    set "PHP_INI=%PHP_INI:~1%"
    goto trimPhpIni
)

set "PHP_INI_FLAG="
if not "%PHP_INI%"=="" if not "%PHP_INI%"=="(none)" set "PHP_INI_FLAG=-c "%PHP_INI%""

echo Document root: %DOCROOT%
echo php.ini usato: %PHP_INI%
echo Apri http://localhost/sniffetto nel browser.
echo Premi CTRL+C per terminare il server.
echo.

rem display_errors=0 evita che warning/deprecation notice del framework
rem (Micron, scritto per PHP piu' vecchi) sporchino il body della risposta JSON.
php %PHP_INI_FLAG% -d display_errors=0 -d log_errors=1 -d error_log="%DOCROOT%\php_errors.log" -S localhost:80 -t "%DOCROOT%" "%DOCROOT%\router.php"

endlocal
