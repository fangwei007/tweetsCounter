<?php

require_once 'config.php';
/*
 * This is used to moniter processes 
 * running status
 */

class Top {
    /*
     * PID list ONLY belong to Top class
     */

    private $PID = array();

    public function __construct() {
        ;
    }

    /*
     * Looping to watching running 
     * processes status
     */

    public function topWatch() {
        do {
            /* Based on PID list
             * generate command "TOP"
             */
            $cmd = "top -d 1 ";
            foreach ($this->PID as $pid) {
                $cmd.= '-p ' . $pid . ' ';
            }

            $this->readPid();
            system($cmd, $return);
        } while (!$return); //automatically restart watching
    }

    /*
     * Read Pid dir and generate PID list
     * @.pid file name rules:
     * tweets- "query list key"-"running process pid".pid
     * @.pid file content:
     * "pid number"
     */

    public function readPid() {

        /* The pid dir below, may 
         * change in different platform
         */
        $path = PIDDIR;
        $handle = opendir($path);

        while (FALSE !== ($filename = readdir($handle))) {
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pid') {
                continue;
            }

            /* Using regx to generate PIDlist
             * In which key is query key, value
             * is pid
             */
            $pattern = '@-[0-9]+-@';
            $pattern_id = '@[0-9]+@';
            preg_match($pattern, $filename, $match);
            preg_match($pattern_id, $match[0], $match_id);
            $data_id = $match_id[0];
            $content = file_get_contents($path . $filename);
            preg_match($pattern_id, $content, $match_pid);
            $pid = $match_pid[0];

            /* The list */
            $this->PID[$data_id] = $pid;
        }
        closedir();
    }

    /*
     * Start the Moniter daemon
     */
    public function startMoniter() {
        //Backgound run moniter process, redirect output into error log
        system('php moniter.php >> error.log &');
        sleep(5); //waiting for system response
    }

}


