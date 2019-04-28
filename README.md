# Account Portal

A PHP application to allow users to create their own accounts and change their passwords. It's the source code for my implementation on my private server https://coh.westus2.cloudapp.azure.com/

# Instructions

Install XAMPP for Windows (https://www.apachefriends.org/index.html). You only need the Apache module, unless you're doing other stuff with your server too. 

Install the Microsoft PHP drivers for SQL Server (https://www.microsoft.com/en-us/download/details.aspx?id=20098). If you installed XAMPP into the default location, then the place you want to unpack the PHP drivers is C:\xampp\php\ext

Modify your C:\xampp\php.ini, add the following lines to your Dynamic Extensions section. XAMPP PHP is version 7.3, thread-safe.
Example:
```
extension=php_sqlsrv_73_ts_x64
extension=php_pdo_sqlsrv_73_ts_x64
```
I strongly recommend you customize index.php; you don't want your players creating an account on your server and trying to log into mine!

I also recommend you set up HTTPS on your Apache server. I used Win-Acme (https://github.com/PKISharp/win-acme). The instructions for Apache is https://github.com/PKISharp/win-acme/wiki/Apache-2.4-basic-usage.

Have fun :)
