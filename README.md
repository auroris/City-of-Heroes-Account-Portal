# Account Portal

A PHP application to allow users to create their own accounts and change their passwords. It's the source code for my implementation on my private server https://coh.westus2.cloudapp.azure.com/

# Operational Requirements

* This application requires PHP 7.3, a webserver, and the Microsoft PHP drivers for SQL Server.
* This application additionally requires access to the dbquery.exe binary, found in your City of Heroes server\bin folder.

# Setup Guide

* Install [XAMPP 7.3.4](https://www.apachefriends.org/index.html). Install it into the default suggested directory `C:\xampp`. De-select all options except for Apache (unless you need them for other things you need the server to do).

* Install the [Microsoft PHP drivers for SQL Server](https://www.microsoft.com/en-us/download/details.aspx?id=57916). If you installed XAMPP into the default location, the path you want to give the installer is `C:\xampp\php\ext`.

* Modify your PHP.ini file, found at c:\xampp\php.ini and add the following lines to your Dynamic Extensions seciton.
```
extension=php_sqlsrv_73_ts_x64
extension=php_pdo_sqlsrv_73_ts_x64
```

* Download and extract this repository's files into `C:\xampp\htdocs`.

* Open the command line, navigate to the root directory of this website and run `php composer.phar -install`. This will download required library files.

* Finally, open `\src\settings.php` and configure your database credentials, path to dbquery.exe and other settings.

# Customization

Website content can be found in the `\templates` directory. You will need to customize 

* `page-index.phtml`
* `template-menu.phtml` and
* `block-client-download.phtml`
