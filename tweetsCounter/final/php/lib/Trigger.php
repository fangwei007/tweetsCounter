<?php

require_once 'config.php';
/*
 * Class to make multiple processes running at the a time, 
 * dealing with different queries.
 */

class Trigger {
    /* The number of queries */

    private $numOfQuery;

    /* Actual queries */
    protected $query;

    /* The path to store pid files */
    private $pidURL = PIDDIR;

    /* PHP file list, using to run processes */
    private $files = array();

    public function __construct($query) {
        /* TODO init queries below, read from config file */
        $this->query = $query;
        $this->numOfQuery = count($query);
    }

    /*
     * Based on each query, automatically 
     * generate php files for each query and 
     * prepare for processing
     */

    public function taskMaker($query, $i) {

        /* The 'id' is mapping to id in database, db_id = id + 1 */
        $id = $i;

        /* Path to each generated file */
        $file = APPLICATION_PATH . "exe/tweets-" . $id . '.php';
        $application_path = APPLICATION_PATH;

        /* generate string for wrting file */
        $exec_string = <<<EOT
        <?php
        require_once "$application_path/lib/config.php";
        /* Create twitter stream object */
        \$sc = new FilterTrackConsumer(OAUTH_TOKEN, OAUTH_SECRET, Phirehose::METHOD_FILTER);
        \$sc->setid($id);
        \$sc->setTrack($query);
        \$sc->db_connect();
        
        /* Write pid into .pid file */
        \$mypid = getmypid();
        exec('echo '. \$mypid . '> '. PIDDIR ."tweets-$id-".\$mypid.'.pid');
        \$sc->consume();
        \$sc->db_disconnect();
EOT;

        /* Write execuable php file */
        $fp = fopen($file, 'w+');
        fwrite($fp, $exec_string);
        fclose($fp);

        /* Return generated file path */
        return $file;
    }

    /*
     * Create job for current retrieving
     */

    public function jobMaker() {

        /* Put each generated file path into PHP file list */
        for ($i = 0; $i < $this->numOfQuery; $i++) {
            $this->files[] = $this->taskMaker($this->query[$i], $i);
        }
    }

    /*
     * Restarting dead process, create new task into PHP file list
     */

    public function jobRestart($id) {
        $this->files[] = $this->taskMaker($this->query[0], $id);
    }

    /*
     * Main entrance here for running 
     * multiple processes in backgroud, 
     * Redirect output into php.log file
     */

    public function runJobs() {
        try {
            foreach ($this->files as $file) {
                $pid = pcntl_fork(); //create new process for parsing
                if (-1 == $pid) {
                    die('fork failed!');
                } else if (0 == $pid) {
                    exec('nohup php ' . $file . ' >> '. APPLICATION_PATH .'php.log &');
                    sleep(2); //waiting for status changing
                    exit;
                } else {
                    // echo "This is parent process with pid: ".$parentPID.PHP_EOL;
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage() . ' at line: ' . $e->getLine();
        }
    }

}
