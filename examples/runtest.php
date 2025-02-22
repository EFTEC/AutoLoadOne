<?php
namespace dummy;

use ClassWithoutNameSpace;
use nsfolderext\ExternalClass;

define("_AUTOLOAD_ONEDEBUG",true);

// both includes works:
// include "autoload.php";
// or
// include "folder/subfolder/autoload.php";

include "autoload.php";

echo "<h1>Testing..</h1>";

// same folders
$c1=new \nsfolder\subnamespace\AnotherNaturalClass();
echo '$c1=new \nsfolder\subnamespace\AnotherNaturalClass();<br>';
// basic
$c2=new \nsfolder\NaturalClass();
echo '$c2=new \folder\NaturalClass();<br>';
$c3=new \nsfolder\subnamespace\AnotherNaturalClass2();
echo '$c3=new \nsfolder\subnamespace\AnotherNaturalClass2();<br>';
$c4=new \nsfolder\subnamespace\MoreNaturalClass();
echo '$c4=new \nsfolder\subnamespace\MoreNaturalClass();<br>';
// same folder, again
$c4=new \nsfolder\subnamespace\MoreNaturalClass();
echo '$c4=new \nsfolder\subnamespace\MoreNaturalClass();<br>';
// same namespace, different folder
$c5=new \nsfolder\subnamespace\CustomClass();
echo '$c5=new \nsfolder\subnamespace\CustomClass();<br>';
// one file, two namespaces
$c6=new \MyProject\Connection();
echo '$c6=new \MyProject\Connection();<br>';
$c8=new \AnotherProject\Connection();
echo '$c8=new \AnotherProject\Connection();<br>';
// class without namespace
$c9=new ClassWithoutNameSpace();
echo '$c9=new ClassWithoutNameSpace();<br>';
// class compressed
$c10=new \folder_ns\subnamespace\ClassFolder1\ClassFolder1();
echo '$c10=new \folder_ns\subnamespace\ClassFolder1\ClassFolder1();<br>';
$c11=new \ClassFolder1();
echo '$c11=new \ClassFolder1();<br>';
echo "<h2>External class</h2>";
$c12=new ExternalClass();
echo '$c12=new ExternalClass();<br>';

// class external. It is loaded externally. Of course, it will fail if you are not loading (the class is not supplied in this project)
/*
$ex=new \nsexternal\External();
echo '$ex=new \nsexternal\External();<br>';
$ex2=Collection::first(array());
*/
echo "Ok<br>";
echo "<h2>Test Error</h2>";
echo "<span style='color:red'>The next command should raise an error (we test if the file doesn't exist):<br></span>";
$cE1 = new \nsfolder\subnamespace\CustomClassE();
