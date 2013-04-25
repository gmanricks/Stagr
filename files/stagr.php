#!/usr/bin/php
<?php

/*
* Script to setup a staging server for Fortrabbit
* Author: Gabriel Manricks
*
* Created to automate the proccess shown in my article written for NetTuts+
*/

set_include_path(get_include_path(). PATH_SEPARATOR. '/opt/stagr/lib'. PATH_SEPARATOR. 'phar:///opt/stagr/lib/symfony-console.phar');
include_once 'vendor/autoload.php';
spl_autoload_register(function ($className) {
    $classFile = preg_replace('~\\\\~', '/', $className). '.php';
    require_once $classFile;
});

$stagr = new \Stagr\Stagr();
$stagr->run();

?>
