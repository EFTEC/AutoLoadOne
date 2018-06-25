# AutoLoadOne
AutoloadOne is a program that generates a single autoload class for PHP. This class is useful to autoload classes without using "include".
Contrary to other alternatives, it supports the easiest way to autoload classes using PHP without sacrifice performance.  How it works?. AutoLoadOne pre-calculates every class of a project and generates a single autoload.php file that it's ready to use.  You don't need a specific folder, structure or rule to use it. Just generate the autoload class, include and you are ready to load any class (even classes without a namespace, classes in the namespace in different folders, multiple classes defined in a single file...).

> "Autoload of classes for any project, any case"

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

