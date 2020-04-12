# Running tests of VPL

## Installing
	composer.phar install
	
* Adds to server config.php
    add $CFG->phpunit_prefix = 'phpu_'; to your config.php file
    and $CFG->phpunit_dataroot = '/path/to/phpunitdataroot'; to your config.php file
    
* Initialise the test environment
    php admin/tool/phpunit/cli/init.php
    
* Running tests of VPL
At the vpl directory run
 	../../vendor/bin/phpunit or ..\..\vendor\bin\phpunit
If the file vpltests.xml exists
 	moodle/vendor/bin/phpunit -c vpltests.xml
 
## Windows development enviroment
### Batch command file using Windows install of Moodle. Copy at server subdirectory
```batch
rem Author Juan Carlos Rodriguez del Pino jcrodriguez at dis.ulpgc.es
@ECHO OFF
echo Configuring Windows Moodle Server for PHPUNIT
set CURDIR=%cd%
set WSERVERPATH=%~dp0
echo Windows server at %WSERVERPATH%
set WSERVERPHPPATH=%WSERVERPATH%php\
set WSERVERMOODLEPATH=%WSERVERPATH%moodle\
set WSERVERMOODLECONFIG=%WSERVERMOODLEPATH%config.php
@find /I "phpunit_prefix" %WSERVERMOODLECONFIG%
@if errorlevel 1 (
    echo "Updating config.php file to add $CFG phpunit data"
    echo //>>%WSERVERMOODLECONFIG%
    echo $CFG-^>phpunit_prefix = 'phpu_'; >>%WSERVERMOODLECONFIG%
    echo $CFG-^>phpunit_dataroot = '%WSERVERPATH%phpunitdataroot'; >>%WSERVERMOODLECONFIG%
)
set SAVEDPATH=%PATH%
set PATH=%WSERVERPHPPATH%;%PATH%
echo Installing PHP composer
cd %WSERVERPATH%
md composer.php
cd composer.php
REM if not work reload installer code from https://getcomposer.org/download/
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e0012edf3e80b6978849f5eff0d4b4e4c79ff1609dd1e613307e16318854d24ae64f26d17af3ef0bf7cfb710ca74755a') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
cd %WSERVERMOODLEPATH%

echo Installing development tools with composer
php %WSERVERPATH%composer.php\composer.phar install

echo Initialising the test environment
php %WSERVERMOODLEPATH%admin\tool\phpunit\cli\init.php
set PATH=%SAVEDPATH%
cd %CURDIR%
```

### Batch command file to run VPL tests. Copy at server subdirectory
```batch
@ECHO OFF
echo Running PHPUNIT VPL Tests
set WSERVERPATH=%~dp0
echo Windows server at %WSERVERPATH%
set WSERVERPHPPATH=%WSERVERPATH%php\
set WSERVERMOODLEPATH=%WSERVERPATH%moodle\
set SAVEDPATH=%PATH%
set PATH=%WSERVERPHPPATH%;%PATH%
set CURDIR=%cd%
cd %WSERVERPATH%moodle\mod\vpl
%WSERVERMOODLEPATH%vendor/bin/phpunit
set PATH=%SAVEDPATH%
cd %CURDIR%
```

