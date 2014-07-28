<?php
require_once 'lib/config.php';
//instantiate top, and start watching
$top = new Top();
$top->startMoniter();
$top->readPid();
$top->topWatch();