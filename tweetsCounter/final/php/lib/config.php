<?php
define("APPLICATION_PATH", "/var/www/final/php/");
function __autoload($name) {    
    require_once APPLICATION_PATH . "lib/". $name . '.php';
}

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "");
define("TWITTER_CONSUMER_SECRET", "");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "");
define("OAUTH_SECRET", "");

// Database settings
define("HOST", '127.0.0.1');
define("USER", '');
define("PASSWORD", '');
define("DATABASE", 'tweets');

// Dir to store pid files
define("PIDDIR","/var/run/tweets/");

// Application path
date_default_timezone_set("America/New_York");
define("START_TIME", date("Y-m-d")." 00:00:00");

