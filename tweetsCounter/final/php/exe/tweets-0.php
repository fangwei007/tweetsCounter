        <?php
        require_once "/var/www/final/php//lib/config.php";
        /* Create twitter stream object */
        $sc = new FilterTrackConsumer(OAUTH_TOKEN, OAUTH_SECRET, Phirehose::METHOD_FILTER);
        $sc->setid(0);
        $sc->setTrack(array('#LiveDieRetweet,#LiveDieRepeat,#EdgeofTomorrow,#TomCruise,#EmilyBlunt,#AskEOT'));
        $sc->db_connect();
        
        /* Write pid into .pid file */
        $mypid = getmypid();
        exec('echo '. $mypid . '> '. PIDDIR ."tweets-0-".$mypid.'.pid');
        $sc->consume();
        $sc->db_disconnect();