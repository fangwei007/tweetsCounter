<?php
define("APPLICATION_PATH", "/var/www/final/php/");
function __autoload($name) {    
    require_once APPLICATION_PATH . "lib/". $name . '.php';
}

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "LDxLThcqVXlUQ1YLRROmrNC7l");
define("TWITTER_CONSUMER_SECRET", "aNRbtvLUIxOSPgULF6HjPfIjJT7KcwRLOKdPNDpwLp9gv2FqL7");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "6865742-m78yK8wKPQoW00gBZTL1u1PcJ4pFAi6J2FkWqR10y2");
define("OAUTH_SECRET", "e9jIQvBkeHSfDWsNGXklmQXm9Qq3fYUspBBExRUQy2RN7");

// Database settings
define("HOST", '127.0.0.1');
define("USER", 'root');
define("PASSWORD", 'RkE8a}j/8B}7t8G6');
define("DATABASE", 'tweets');

// Dir to store pid files
define("PIDDIR","/var/run/tweets/");

// Application path
date_default_timezone_set("America/New_York");
define("START_TIME", date("Y-m-d")." 00:00:00");

