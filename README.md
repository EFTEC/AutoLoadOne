# AutoLoadOne
AutoloadOne is a program that generates a single autoload class for PHP. This class is useful to autoload classes without using "include".
Contrary to other alternatives, it supports the easiest way to autoload classes using PHP without sacrifice performance.  How it works?. AutoLoadOne pre-calculates every class of a project and generates a single autoload.php file that it's ready to use.  You don't need a specific folder, structure or rule to use it. Just generate the autoload class, include and you are ready to load any class (even classes without a namespace, classes in the namespace in different folders, multiple classes defined in a single file...).

> "Autoload of classes for any project"

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

1) copy the file autoloadone.php somewhere.

2) edit the first files. Change the user, password and autoloadenter if it's required.


```php
<?php
define("_AUTOLOADUSER","autoloadone");
define("_AUTOLOADPASSWORD","autoloadone");
define("_AUTOLOADENTER",true); // if you want to autoload (no user or password) then set to true
?>
```
3) Start the browser

Enter the user and password

## Usage (via cli)

todo

## Usage (generate class)

1) include the autoinclude.php

```php
<?php
define("_AUTOLOADONEDEBUG",true); // this line is optional.
include "autoinclude.php";
?>
```
and that's it!.


## Version
2018-06-24 First version

## Todo

* CLI
* Clean the code.
