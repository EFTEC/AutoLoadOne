<?php
namespace dummy;

// This example doesn't use autoload

include '../examples/folder\NaturalClass.php';
include '../examples/folder/subfolder\AnotherNaturalClass.php';
include '../examples/folder/subfolder\AnotherNaturalClass2.php';
include '../examples/folder/subfolder\MoreNaturalClass.php';
include '../examples/folder/subfolderalt/CustomClass.php';
include '../examples/folder/multiplenamespace.php';
include '../examples/folder/subfolderalt/ClassWithoutNameSpace.php';

use ClassWithoutNameSpace;
use nsfolder\NaturalClass;
use nsfolder\subnamespace\AnotherNaturalClass;
use nsfolder\subnamespace\AnotherNaturalClass2;
use nsfolder\subnamespace\CustomClass;
use nsfolder\subnamespace\MoreNaturalClass;

// basic
$c2=new NaturalClass();
// same folders
$c1=new AnotherNaturalClass();
$c3=new AnotherNaturalClass2();
$c4=new MoreNaturalClass();
// same folder, again
$c4=new MoreNaturalClass();
// same namespace, different folder
$c5=new CustomClass();

// one file, two namespaces
$c6=new \MyProject\Connection();
$c8=new \AnotherProject\Connection();

// class without namespace
$c9=new ClassWithoutNameSpace();



echo "Ok<br>";

echo "the next command should raise an error:<br>";
$cE1 = new CustomClassE();
