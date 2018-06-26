# AutoLoadOne
AutoloadOne is a program that generates an autoload class for PHP that is project specific. This class is useful to use classes on code without calling each "include" manually.
Contrary to other alternatives, it supports the easiest way to autoload classes using PHP without sacrifice performance.  How it works?. AutoLoadOne pre-calculates every class of a project and generates a single autoload.php file that it's ready to use.  You don't need a specific folder, structure or rule to use it. Just generate the autoload class, include and you are ready to load any class (even classes without a namespace, classes in the namespace in different folders, multiple classes defined in a single file...).

> "Universal Autoloading classes in PHP, any class, any time!"

## Composer Autoload features:
* One class per file
* One Namespace per file.
* The file must have a namespace.
* It requires composer.
* It validates the file each file the class is loaded per user.
* The structure of the folders should be pre-defined.
* Support CLI

## AutoLoadOne extended features:
* One or Many classes per file.
* One or many namespaces per file.
* The file could contain optionally a namespace.
* It only requires PHP
* The folder structure and classes are validated once.
* If you add a class that shares a previous folder and uses the previous namespace, then you don't need to run the generator.
* You could use any structure of folder. It's recommended to use the same folder for the same namespace but it's not a requisite.
* Support CLI and Web-UI.
* It doesn't require APCU, lock files or cache.
* It´s compatible with practically any project, including a project that uses Composer's autoload.
* PSR-0, PSR-4, and practically  any specification, since you don't need to use any special configuration or standard.

## Usage (generate code via Web)

1) copy the file **autoloadone.php** somewhere.

2) For security, you couldedit the first lines of the class **autoloadone.php**. Change the user, password and autoloadenter if it's required.

```php
<?php
define("_AUTOLOADUSER","autoloadone");
define("_AUTOLOADPASSWORD","autoloadone");
define("_AUTOLOADENTER",true); // if you want to autoload (no user or password) then set to true
?>
```
3) Start the browser

Enter your user and password.  If _AUTOLOADENTER is true then you are logged automatically.

![autoloadone login](https://github.com/EFTEC/AutoLoadOne/blob/master/doc/login.jpg "Autoloadone logon")


4) Select the folder to scan, then select the file to generate and finally press the button GENERATE.

![autoloadone screen](https://github.com/EFTEC/AutoLoadOne/blob/master/doc/screen.jpg "Autoloadone screen")

* Root Folder : The folder to scan.
* Generated File: The full path (local) of the autoinclude.php.  Even if you are not using the generation of file, you must specify it, because the program uses for determine the relative path.
* Save File: If you check it, then generate file will be generated.  If PHP doesn't have access to save the file, then you could copy the code manually (screen Result)
* Excluded Namespace : Namespace excluded of mapping.
* Excluded Map : Paths excluded to scan (they are not recursives)



5) The result should looks this:

![autoloadone screen2](https://github.com/EFTEC/AutoLoadOne/blob/master/doc/screen2.jpg "Autoloadone screen2")

## Usage (via cli)

In the shell, browser to the folder where you want to generate the code and run the next command

```
php folder/located/autoloadone.php -current
```



## Usage (generate class)

1) include the generated file by the previous step. ex: autoinclude.php

```php
<?php
define("_AUTOLOADONEDEBUG",true); // this line is optional.
include "autoinclude.php";
?>
```
and that's it!.

In the /test folder you could find some example to try.

> Note:Now, you could delete autoloadone.php

## Note

> If you want to exclude a class, you could add the namespace to the exclude list, or you could skip a folder.  
> Also, if a class has the next comment, it's excluded automatically:

```php
<?php
// @noautoload
?>
```

## Benchmark

PHP 7.1.18 + Windows 10 + SSD.

![autoloadone benchmark](https://github.com/EFTEC/AutoLoadOne/blob/master/doc/speed.jpg "Autoloadone benchmarj")  

_More is better._


> I did a synthetic benchmark by loading different classes and reading the performance of it. Since my machine has a SSD disk then, the impact of the disk is minimum in comparison with a mechanical hard disk.
> This chart compares the performance against INCLUDE.

## Version
2018-06-24 First version

## Todo

* CLI (more commands)
* Clean the code.
* Convert to a single class.
* External folder/library (relative or absolute path)
* The generation fails if a php file has an error.
* Specify the extensions. By default it scans only .php files.

## Security

> While the program has a build-in-security measure, however I suggest to protect adding new layers of security such as locating the AutoLoadOne.php file outside of the public/web folder.

> AutoLoadOne.php is not safe (because it writes a generate file), it doesn't have access to the database, neither it allows to write any content to the file.   But, it could overwrite an exist code and put down a system.

> However, the generate file is safe (autoinclude.php) and you could expose to the net.

* Change the user and password and set _AUTOLOADENTER to false.
* Or, Don't put this file in your public website.
* Or, change the filename.
* Or, you could block the access to the file using .htaccess or similar.  

```
RewriteEngine On
RewriteBase /

<Files "AutoLoadOne.php">
Order Allow,Deny
Deny from all
</Files>
```

* Or you could restrict the access to PHP and it's the behaviour by default on Linux (it runs under Apache's account, most of the time as user NOBODY)


