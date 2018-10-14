<?php
namespace dummy;

use ClassWithoutNameSpace;
use mapache_commons\Collection;

define("_AUTOLOAD_ONEDEBUG",true);

// both includes works:
// include "autoload.php";
// or
// include "folder/subfolder/autoload.php";

include "autoload.php";

echo "<h1>Testing..</h1>";

// basic
$c2=new \nsfolder\NaturalClass();
echo '$c2=new \folder\NaturalClass();<br>';
// same folders
$c1=new \nsfolder\subnamespace\AnotherNaturalClass();
echo '$c1=new \nsfolder\subnamespace\AnotherNaturalClass();<br>';
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
// class external
$ex=new \nsexternal\External();
echo '$ex=new \nsexternal\External();<br>';
$ex2=Collection::first(array());

echo "Ok<br>";

echo "<span style='color:red'>The next command should raise an error (we test if the file doesn't exist):<br></span>";
$cE1 = new \nsfolder\subnamespace\CustomClassE();
