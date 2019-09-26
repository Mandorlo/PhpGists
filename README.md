# PhpGists

This is a set of PHP functions and tools to help build my PHP projects.

# run a phpunit test

You can install phpunit globally with `composer global require phpunit`.

Once it's installed, run something like : `phpunit .\tests\ArrayTest.php` to run the ArrayTest tests for example.

# Configure debugging with vscode and xdebug

## On Windows

* download [xdebug](https://xdebug.org/download.php) and place in your php ext directory (where there are a lot of php_xxx.dll files)
* in your php.ini add the following lines (you can change the port if you want)
```
zend_extension=[absolute path to your php_xdebug.dll]
xdebug.remote_enable=1
xdebug.remote_autostart = 1
xdebug.remote_port=9900
xdebug.remote_log=[choose an absolute path to xdebug.log]
```
* check that xdebug is ok by running `php -i | grep xdebug`
* in vscode configure xdebug on port 9900 (the one you put in php.ini)

To debug something with xdebug (typically a phpunit test):
* place a breakpoint somewhere
* run the xdebug listener in vscode
* run the command you want like `phpunit .\tests\ArrayTest.php`
* if you want to run only some functions, in the test file, add a `--filter pattern` argument in the phpunit command line

To debug a script, no need to use xdebug, just use the default vscode script config

# Documentation

## Install phpDocumentor on Windows

* download the phpdoc.phar from the [phpDocumentor website](https://www.phpdoc.org/) and add it to your PATH
* create in the same folder as phpdoc.phar, a phpdoc.cmd with this line : `@php "%~dp0phpdoc.phar" %* `)

## Generate the HTML doc

* cd in the project and run `phpdoc.cmd -d ./src -t ./docs`