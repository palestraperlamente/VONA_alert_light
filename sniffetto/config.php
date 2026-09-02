<?php

//Database settings
if (array_key_exists('HTTP_HOST', $_SERVER) && str_ends_with($_SERVER['HTTP_HOST'], 'noexit.it')) {
    define("DB_HOST", "mysql.netsons.com");
    define("DB_DATABASE_NAME", "ykqtppjf_sniffetto");
    define("DB_USERNAME", "ykqtppjf_dbuser");
    define("DB_PASSWORD", '$niff3tt0!DB');
} else {
    define("DB_HOST", "localhost");
    define("DB_DATABASE_NAME", "sniffetto");
    define("DB_USERNAME", "root");
    define("DB_PASSWORD", "root");
}

//JWT settings
define("JWT_SECRET", "Sn1ff3tt0!!!");

define("MEDIA_BASE_PATH", $_SERVER["DOCUMENT_ROOT"]."\media");

//defines for Users class. Fill only if you want to use this feature. (Not implemented yet)
define("USER_LEVELS", "'ADMIN','STANDARD'"); //define the users levels

