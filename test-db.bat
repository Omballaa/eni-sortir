@echo off
echo ===========================================
echo Test de connexion PostgreSQL pour ENI Sortir
echo ===========================================
echo.

echo 1. Verification de la configuration Doctrine...
php bin/console doctrine:schema:validate

echo.
echo 2. Test de connexion a la base de donnees...
echo    Serveur: home.sarkaa.fr:54321
echo    Base: eni-sortir
echo    User: eni-sortir
echo.

REM Test de connexion avec vos vraies informations dans .env
php bin/console doctrine:query:sql "SELECT version(), current_database(), current_user;" 2>nul

if %ERRORLEVEL% EQU 0 (
    echo ✅ CONNEXION POSTGRESQL REUSSIE !
    echo.
    echo 3. Informations sur la base de donnees:
    php bin/console doctrine:query:sql "SELECT version();"
    echo.
    echo 4. Database et utilisateur actuels:
    php bin/console doctrine:query:sql "SELECT current_database(), current_user;"
) else (
    echo ❌ ECHEC DE CONNEXION
    echo.
    echo Verifiez:
    echo - Le mot de passe dans .env
    echo - La connectivite vers home.sarkaa.fr:54321
    echo - Les extensions PHP PostgreSQL (pdo_pgsql, pgsql)
)

echo.
echo ===========================================
echo ✅ Configuration PostgreSQL terminee !
echo.
echo Commandes disponibles:
echo   php bin/console make:entity          ^(creer entite^)
echo   php bin/console doctrine:migrations:migrate
echo   php bin/console doctrine:schema:update --dump-sql
echo ===========================================
pause