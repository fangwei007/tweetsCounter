<?php
define("APPLICATION_PATH", "/var/www/final/php/");
function __autoload($name) {    
    require_once APPLICATION_PATH . "lib/". $name . '.php';
}

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "L6QB3zCvNAcJJuAoD0nndA");
define("TWITTER_CONSUMER_SECRET", "ORhjKlmch8VxjOfmfhk5ez7uN44bI7wR8sdH1UKwIo");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "1675544592-YNnA0FPSwi8DNHwl7ou7YIX27UfvhOUISYBApeY");
define("OAUTH_SECRET", "HqemGMh7HzJuPd53jWFFMZIvrnGuMg0Idszr6U6jhGGRD");

// Database settings
define("HOST", '127.0.0.1');
define("USER", 'root');
define("PASSWORD", 'root');
define("DATABASE", 'tweets');

// Dir to store pid files
define("PIDDIR","/var/run/tweets/");

// Application path

