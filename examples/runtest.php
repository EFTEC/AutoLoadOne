<?php
namespace dummy;

use ClassWithoutNameSpace;

define("_AUTOLOAD_ONEDEBUG",true);

// both includes works:
// include "autoload.php";
// or
// include "folder/subfolder/autoload.php";

include "autoload.php";

echo "<h1>Testing..</h1>";

// basic
$c2=new \folder\NaturalClass();
echo '$c2=new \folder\NaturalClass();<br>';
// same folders
$c1=new \folder\subfolder\AnotherNaturalClass();
echo '$c1=new \folder\subfolder\AnotherNaturalClass();<br>';
$c3=new \folder\subfolder\AnotherNaturalClass2();
echo '$c3=new \folder\subfolder\AnotherNaturalClass2();<br>';
$c4=new \folder\subfolder\MoreNaturalClass();
echo '$c4=new \folder\subfolder\MoreNaturalClass();<br>';
// same folder, again
$c4=new \folder\subfolder\MoreNaturalClass();
echo '$c4=new \folder\subfolder\MoreNaturalClass();<br>';
// same namespace, different folder
$c5=new \folder\subfolder\CustomClass();
echo '$c5=new \folder\subfolder\CustomClass();<br>';
// one file, two namespaces
$c6=new \MyProject\Connection();
echo '$c6=new \MyProject\Connection();<br>';
$c8=new \AnotherProject\Connection();
echo '$c8=new \AnotherProject\Connection();<br>';
// class without namespace
$c9=new ClassWithoutNameSpace();
echo '$c9=new ClassWithoutNameSpace();<br>';


echo "Ok<br>";

echo "<span style='color:red'>The next command should raise an error (we test if the file doesn't exist):<br></span>";
$cE1 = new \folder\subfolder\CustomClassE();
