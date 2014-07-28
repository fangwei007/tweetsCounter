<?php

require_once 'config.php';

/**
 * Example of using Phirehose to display a live filtered stream using track words
 */
class FilterTrackConsumer extends OauthPhirehose {

    private $mysqli = NULL;
    private $id;
    private $track_id;
    private $current_count;
    private $date;
    private $found_flag = 0;

    public function setid($id) {
        $this->id = $id + 1;
    }

    /**
     * Enqueue each status
     *
     * @param string $status
     */
    public function enqueueStatus($status) {
        /*
         * In this simple example, we will just display to STDOUT rather than enqueue.
         * NOTE: You should NOT be processing tweets at this point in a real application, instead they should be being
         *       enqueued and processed asyncronously from the collection process.
         */
        $data = json_decode($status, true);
        if (is_array($data) && isset($data['user']['screen_name'])) {
            $textData = str_replace(PHP_EOL, " ", urldecode($data['text']));
            print date("Y-m-d H:i:s") . ':  ' . $data['user']['screen_name'] . ': ' . $textData . "\n";
            $this->found_flag = 1;
        }
    }

    public function db_disconnect() {
        if ($this->mysqli !== NULL) {
            return $this->mysqli->close();
        }
    }

    public function db_connect() {
        if ($this->mysqli == NULL) {
            $this->mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

            if ($this->mysqli->connect_error) {
                die('Connect Error (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error);
            } else {
                echo date("Y-m-d H:i:s") . "Database Connected!!\n";
                $this->db_start_insert();
            }
            return $this->mysqli;
        } else {
            return $this->mysqli;
        }
    }

    public function db_start_insert() {
        $stmt = $this->mysqli->stmt_init();
        if ($stmt->prepare("SELECT id FROM tweetCounts ORDER BY date DESC")) {
            $stmt->execute();
            $stmt->bind_result($this->track_id);
            $stmt->fetch();
            $stmt->close();
        }

        if ($this->track_id) {
            $this->id = $this->track_id;
        } else {
            $date = START_TIME;
            $count = 0;
            $stmt = $this->mysqli->stmt_init();
            if ($stmt->prepare("INSERT INTO tweetCounts(date, count) VALUES(?, ?)")) {
                $stmt->bind_param("si", $date, $count);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    public function db_update() {
        //Loop until database connected!
        while ($this->mysqli->connect_error !== NULL) {
            $this->db_connect();
        }
        $this->db_select();
        if ((time() - strtotime($this->date)) < 60 * 60 * 24) {//still within one day
            $stmt = $this->mysqli->prepare("UPDATE tweetCounts SET count = count + 1 WHERE id = $this->id");
            if (!$stmt->execute()) {
                error_log($this->mysqli->error());
            }
            $stmt->close();
        } else {//another day comes
            $days = floor((time() - strtotime($this->date)) / (60 * 60 * 24)); //how many days after
            if ($days === 1) {
                $this->date = date("Y-m-d H:i:s", (strtotime($this->date) + 60 * 60 * 24));
                $this->db_insert();
                $this->id = $this->mysqli->insert_id;
            } else {
                while ($days > 0) {                   
                    if ($days > 1) {
                        $this->current_count--;
                    }
                    $this->date = date("Y-m-d H:i:s", (strtotime($this->date) + 60 * 60 * 24));
                    $this->db_insert();
                    $this->id = $this->mysqli->insert_id;
                    $this->db_select();
                    $days--;
                }
            }
        }
    }

    public function db_select() {
        $stmt = $this->mysqli->stmt_init();
        if ($stmt->prepare("SELECT count, date FROM tweetCounts WHERE id = $this->id")) {
            $stmt->execute();
            $stmt->bind_result($this->current_count, $this->date);
            $stmt->fetch();
            $stmt->close();
        }
    }

    public function db_insert() {
        $stmt = $this->mysqli->stmt_init();
        if ($stmt->prepare("INSERT INTO tweetCounts(date, count) VALUES(?, ?)")) {
            $stmt->bind_param("si", $this->date, ++$this->current_count);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function consume($reconnect = TRUE) {

        /* TODO establish persistent connection with database here */
        $this->reconnect = $reconnect;

        // Loop indefinitely based on reconnect
        do {

            // (Re)connect
            $this->reconnect();

            // Init state
            $lastAverage = $lastFilterCheck = $lastFilterUpd = $lastStreamActivity = time();
            $fdw = $fde = NULL; // Placeholder write/error file descriptors for stream_select
            // We use a blocking-select with timeout, to allow us to continue processing on idle streams
            //TODO: there is a bug lurking here. If $this->conn is fine, but $numChanged returns zero, because readTimeout was
            //    reached, then we should consider we still need to call statusUpdate() every 60 seconds, etc.
            //     ($this->readTimeout is 5 seconds.) This can be quite annoying. E.g. Been getting data regularly for 55 seconds,
            //     then it goes quiet for just 10 or so seconds. It is now 65 seconds since last call to statusUpdate() has been
            //     called, which might mean a monitoring system kills the script assuming it has died.
            while ($this->conn !== NULL && !feof($this->conn) &&
            ($numChanged = stream_select($this->fdrPool, $fdw, $fde, $this->readTimeout)) !== FALSE) {
                /* Unfortunately, we need to do a safety check for dead twitter streams - This seems to be able to happen where
                 * you end up with a valid connection, but NO tweets coming along the wire (or keep alives). The below guards
                 * against this.
                 */
                if ((time() - $lastStreamActivity) > $this->idleReconnectTimeout) {
                    $this->log('Idle timeout: No stream activity for > ' . $this->idleReconnectTimeout . ' seconds. ' .
                            ' Reconnecting.', 'info');
                    $this->reconnect();
                    $lastStreamActivity = time();
                    continue;
                }
                // Process stream/buffer
                $this->fdrPool = array($this->conn); // Must reassign for stream_select()
                //Get a full HTTP chunk.
                //NB. This is a tight loop, not using stream_select.
                //NB. If that causes problems, then perhaps put something to give up after say trying for 10 seconds? (but
                //   the stream will be all messed up, so will need to do a reconnect).
                $chunk_info = trim(fgets($this->conn)); //First line is hex digits giving us the length
                if ($chunk_info == '')
                    continue;    //Usually indicates a time-out. If we wanted to be sure,

























                    
//then stream_get_meta_data($this->conn)['timed_out']==1.  (We could instead
                //   look at the 'eof' member, which appears to be boolean false if just a time-out.)
                //TODO: need to consider calling statusUpdate() every 60 seconds, etc.
                // Track maximum idle period
                // (We got start of an HTTP chunk, this is stream activity)
                $this->idlePeriod = (time() - $lastStreamActivity);
                $this->maxIdlePeriod = ($this->idlePeriod > $this->maxIdlePeriod) ? $this->idlePeriod : $this->maxIdlePeriod;
                $lastStreamActivity = time();

                //Append one HTTP chunk to $this->buff
                $len = hexdec($chunk_info);   //$len includes the \r\n at the end of the chunk (despite what wikipedia says)
                //TODO: could do a check for data corruption here. E.g. if($len>100000){...}
                $s = '';
                $len+=2;    //For the \r\n at the end of the chunk
                while (!feof($this->conn)) {
                    $s.=fread($this->conn, $len - strlen($s));
                    if (strlen($s) >= $len)
                        break;  //TODO: Can never be >$len, only ==$len??
                }
                $this->buff.=substr($s, 0, -2);   //This is our HTTP chunk
                //Process each full tweet inside $this->buff
                while (1) {
                    $eol = strpos($this->buff, "\r\n");  //Find next line ending
                    if ($eol === 0) {  // if 0, then buffer starts with "\r\n", so trim it and loop again
                        $this->buff = substr($this->buff, $eol + 2);  // remove the "\r\n" from line start
                        continue; // loop again
                    }
                    if ($eol === false)
                        break; //Time to get more data

                    $enqueueStart = microtime(TRUE);
                    $this->enqueueStatus(substr($this->buff, 0, $eol));
                    $this->enqueueSpent += (microtime(TRUE) - $enqueueStart);
                    $this->statusCount++;
                    $this->buff = substr($this->buff, $eol + 2);    //+2 to allow for the \r\n
                }

                //NOTE: if $this->buff is not empty, it is tempting to go round and get the next HTTP chunk, as
                //  we know there is data on the incoming stream. However, this could mean the below functions (heartbeat
                //  and statusUpdate) *never* get called, which would be bad.
                // Calc counter averages
                $this->avgElapsed = time() - $lastAverage;
                if ($this->avgElapsed >= $this->avgPeriod) {
                    $this->statusRate = round($this->statusCount / $this->avgElapsed, 0);          // Calc tweets-per-second
                    // Calc time spent per enqueue in ms
                    $this->enqueueTimeMS = ($this->statusCount > 0) ?
                            round($this->enqueueSpent / $this->statusCount * 1000, 2) : 0;
                    // Calc time spent total in filter predicate checking
                    $this->filterCheckTimeMS = ($this->filterCheckCount > 0) ?
                            round($this->filterCheckSpent / $this->filterCheckCount * 1000, 2) : 0;

                    $this->heartbeat();
                    $this->statusUpdate();
                    // print date("Y-m-d H:i:s") . "\n";
                    $lastAverage = time();
                }
                // Check if we're ready to check filter predicates
                if ($this->method == self::METHOD_FILTER && (time() - $lastFilterCheck) >= $this->filterCheckMin) {
                    $this->filterCheckCount++;
                    if ($this->found_flag == 1) {
                        /* Below update the count in database */
                        $this->db_update();
                        $this->found_flag = 0;
                    }

//                    echo "---------------" . $this->filterCheckCount . "---------------\n";

                    $lastFilterCheck = time();
                    $filterCheckStart = microtime(TRUE);
                    $this->checkFilterPredicates(); // This should be implemented in subclass if required
                    $this->filterCheckSpent += (microtime(TRUE) - $filterCheckStart);
                }
                // Check if filter is ready + allowed to be updated (reconnect)
                if ($this->filterChanged == TRUE && (time() - $lastFilterUpd) >= $this->filterUpdMin) {
                    $this->log('Reconnecting due to changed filter predicates.', 'info');
                    $this->reconnect();
                    $lastFilterUpd = time();
                }
            } // End while-stream-activity

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // Some sort of socket error has occured
            $this->lastErrorNo = is_resource($this->conn) ? @socket_last_error($this->conn) : NULL;
            $this->lastErrorMsg = ($this->lastErrorNo > 0) ? @socket_strerror($this->lastErrorNo) : 'Socket disconnected';
            $this->log('Phirehose connection error occured: ' . $this->lastErrorMsg, 'error');

            // Reconnect
        } while ($this->reconnect);

        // Exit
        $this->log('Exiting.');
    }

}
