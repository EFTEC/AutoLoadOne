<?php
namespace dummy;

use ClassWithoutNameSpace;

define("_AUTOLOADONEDEBUG",true);

// both includes works:
// include "autoload.php";
// or
// include "folder/subfolder/autoload.php";

include "autoload.php";



// basic
$c2=new \folder\NaturalClass();
// same folders
$c1=new \folder\subfolder\AnotherNaturalClass();
$c3=new \folder\subfolder\AnotherNaturalClass2();
$c4=new \folder\subfolder\MoreNaturalClass();
// same folder, again
$c4=new \folder\subfolder\MoreNaturalClass();
// same namespace, different folder
$c5=new \folder\subfolder\CustomClass();

// one file, two namespaces
$c6=new \MyProject\Connection();
$c8=new \AnotherProject\Connection();

// class without namespace
$c9=new ClassWithoutNameSpace();



echo "Ok<br>";

echo "the next command should raise an error:<br>";
$cE1 = new \folder\subfolder\CustomClassE();
