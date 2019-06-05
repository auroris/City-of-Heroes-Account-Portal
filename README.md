# Account Portal

A PHP application to allow users to create their own accounts and change their passwords. It's the source code for my implementation on my private server https://coh.westus2.cloudapp.azure.com/

# Operational Requirements

* This application requires PHP 7.3, a webserver, and the Microsoft PHP drivers for SQL Server.
* This application additionally requires access to the dbquery.exe binary, found in your City of Heroes server\bin folder.

# Setup Guide

* Install [XAMPP 7.3.4](https://www.apachefriends.org/index.html) (or greater). Install it into the default suggested directory `C:\xampp`. De-select all options except for Apache (unless you need them for other things you need the server to do).

* Install the [Microsoft PHP drivers for SQL Server](https://www.microsoft.com/en-us/download/details.aspx?id=57916). If you installed XAMPP into the default location, the path you want to give the installer is `C:\xampp\php\ext`.

* Modify your `c:\xampp\php.ini` file and add the following lines to your Dynamic Extensions section.
```
extension=php_sqlsrv_73_ts_x64
extension=php_pdo_sqlsrv_73_ts_x64
```

* Download and extract this repository's files into `C:\xampp\htdocs`.

* Open the command line, navigate to the root directory of this website and run `php composer.phar install`. This will download required library files.

* Configuration is stored in \App\Config. Rename config.env.example to config.env and put in your database credentials, your command to start dbquery, and your portal's name and crypto keys. Just put some random typing as your key and iv values.

# Recommendations

* I recommend you set up HTTPS on your Apache server. I use Win-Acme (https://github.com/PKISharp/win-acme), an Acme client for Let's Encrypt. The instructions for Apache are https://github.com/PKISharp/win-acme/wiki/Apache-2.4-basic-usage.

* Add a unique index to column uid on cohauth.dbo.user_account to reduce the possibility of account uid collisions. To do that, run this SQL statement: ```CREATE UNIQUE INDEX AccountUID ON cohauth.dbo.user_account (uid);```

# Customization

Website content can be found in the `\templates` directory. Rename the .example files by removing the extension '.example'. Then customize to taste.

* create.phtml is displayed as text above the create your account form. Use this for EULA, rules, etc.
* index.phtml lets you customize your main index page. If you do not have this file, a default server status message will be displayed instead.
* menuitems.phtml allows you to add additional menu items to the bottom of the main menu.

# Upgrade Guide

I've changed a lot of things in the portal. Here's some major things that might trip you up:
* I moved \App\Config to \Config.
* I've added a lot of items to config.env, check config.env.example to see what's new.
* Remember to run `php composer.phar install` to get new required libraries.

# Support

Support is available from Aleena on Discord. https://discord.gg/G5tRFFX in the #portal-discussion channel.
