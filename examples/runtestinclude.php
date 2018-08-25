<?php
namespace dummy;

// This example doesn't use autoload

include '../test/folder\NaturalClass.php';
include '../test/folder/subfolder\AnotherNaturalClass.php';
include '../test/folder/subfolder\AnotherNaturalClass2.php';
include '../test/folder/subfolder\MoreNaturalClass.php';
include '../test/folder/subfolderalt/CustomClass.php';
include '../test/folder/multiplenamespace.php';
include '../test/folder/subfolderalt/ClassWithoutNameSpace.php';

use ClassWithoutNameSpace;



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
