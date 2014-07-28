<?php
require_once 'lib/config.php';
/* Define query and run */
$query = array(
    "array('#LiveDieRetweet,#LiveDieRepeat,#EdgeofTomorrow,#TomCruise,#EmilyBlunt,#AskEOT')"
);
$moniter = new Moniter($query);
$moniter->cleanPid();
$moniter->jobMaker();
$moniter->runJobs();
sleep(5);
$moniter->readPid();
sleep(2);
$moniter->monit();
