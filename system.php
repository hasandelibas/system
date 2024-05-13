<?php

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);



require_once __DIR__."/lib/df.php";
require_once __DIR__."/lib/request.php";
require_once __DIR__."/lib/functions.php";
require_once __DIR__."/lib/db.php";



$__LOGIN_HASH__ = 'RAND0M-T€XT-F0R-5€5510N'.$_SERVER['DOCUMENT_ROOT']; // Random Text
$__LOGIN_NAME__ = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_SERVER['SERVER_NAME'].'-session' )));
$__LOGIN_TIME__ = 3600 * 24 * 360; // 1 Year


// $df = new DF(__DIR__."/.htdatabase.json");
// $db = new DB("localhost", "db_name","db_user","db_pass");
