<?php

require_once 'config.php';

/*
 * Moniter daemon for watching processes
 * and if one dies, restart a new one for
 * same query
 */

class Moniter extends Trigger {
    /*
     * PID list for mapping and watching
     */

    private $PID = array();

    /*
     * Info about each running process
     */
    public $status = "";

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
     * Main monitor method, every 5s,
     * callback function output running 
     * process info to error.log file
     */

    public function monit() {
        try {
            declare(ticks = 1);
            // pcntl_signal_dispatch();
            pcntl_signal(SIGALRM, array(&$this, "signalHandler"), true);
            pcntl_alarm(5);

            for (;;) {
                
            }
        } catch (Exception $e) {
            echo $e->getMessage() . ' at line: ' . $e->getLine();
        }
    }

    public function signalHandler($singal) {

        /* Sign for catching signal */
        echo date("Y-m-d H:i:s") . "Caught SIGALRM \n";

        foreach ($this->PID as $pid) {
            $this->status .= exec('ps ' . $pid); //Returns the last line of the command output on success, and FALSE on failure.//system => exec
        }
        $this->restart($this->status);
        $this->status = "";

        pcntl_alarm(5);
    }

    /*
     * When a process dies, restart it automatically
     */

    public function restart($status) {
        foreach ($this->PID as $data_id => $pid) {
            echo $pid . "\n";
            if (FALSE === strpos($status, $pid)) {
                //date_default_timezone_set('America/New_York');
                echo "dead, and restarting..." . date("Y-m-d H:i:s") . "\n";

                /* Find the query attached and 
                 * remove the old pid file 
                 */
                $newQuery = array($this->query[$data_id]);
                $task = new Trigger($newQuery);
                $task->jobRestart($data_id);
                $task->runJobs();
                sleep(2); //waiting for status changing

                /* Remove dead process .pid file */
                passthru("rm " . PIDDIR . "tweets-" . $data_id . "-" . $pid . ".pid");
                /* Reset PID list */
                unset($this->PID[$data_id]);
                $this->readPid();

                sleep(1);
            }
        }
    }

    /* When starts, 
     * clean pid dir 
     */

    public function cleanPid() {

        $path = PIDDIR;
        $handle = opendir($path);
        while (FALSE !== ($filename = readdir($handle))) {
            if (is_dir($filename)) {
                continue;
            }
            unlink($path . $filename);
        }
        closedir();
    }

}


