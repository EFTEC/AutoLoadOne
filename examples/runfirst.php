<?php
// This code will generate the file autoload.php

use eftec\AutoLoadOne\AutoLoadOne;



define("_AUTOLOAD_SELFRUN",false);
define("_AUTOLOAD_ONLYCLI",false);
define("_AUTOLOAD_ENTER",false);
echo "<h1>Test</h1>It's an interactive test.";
echo "It will generate the file autoload.php of this folder<br>";
echo "And, when you are ready, you could test the result on <a href='runtest.php'>runtest.php</a><br>";
echo "The user and password is autoloadone/autoloadone<hr>";
include "../AutoLoadOne.php";
$auto=new AutoLoadOne();
$auto->extension='.php'; // it's not required. By default it's .php
$auto->rooturl=__DIR__; // this default value is optional, it's only for the example
$auto->fileGen=__DIR__; // this default value is optional, it's only for the example
$auto->savefile=1; // this default value is optional, it's only for the example
$auto->init();
$auto->process();
$auto->render();
