<?php

include_once('./Utility.php');

/**
 * Parses the log files based on endpoints and log file.
 *
 * @author imran
 */
class Parser {

    public $patternUserIdReplacement = '(\/api\/users\/)([0-9]+)(\/?[a-zA-Z0-9_\-]*)'; //will be used for replacing actual user id to {user_id}
    public $parsePattern = array(
        'look_for' => '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}\+\d{2}:\d{2})\sheroku\[router]:\sat=info\smethod=(GET|POST)\spath=(\/api\/users\/[0-9]+\/?[a-zA-Z0-9_\-]*)\shost=([a-zA-Z0-9\-\._:]+)\sfwd="([0-9\.]+)"\sdyno=([a-zA-Z0-9\-\.]+)\sconnect=(\d+)ms\sservice=(\d+)ms\sstatus=(\d+)\sbytes=(\d+)$/',
    );
    //to manage indexes of matched groups
    public $parseIndexMap = array(
        'timestamp' => 0, 'method' => 1, 'path' => 2, 'host' => 3,
        'fwd_client' => 4, 'dyno' => 5, 'connect' => 6, 'service' => 7,
        'status' => 8, 'bytes' => 9
    );
    //endpoints that will be used to process the analysis w.r.t the log file
    public $endpoints = array(
        'GET /api/users/{user_id}/count_pending_messages',
        'GET /api/users/{user_id}/get_messages',
        'GET /api/users/{user_id}/get_friends_progress',
        'GET /api/users/{user_id}/get_friends_score',
        'POST /api/users/{user_id}',
        'GET /api/users/{user_id}'
    );

    /**
     * Method reads log file, it makes use of parse pattern and end-points. 
     * At the end it return an associative array w.r.t to end-points with calculations e.g.
     * array([GET /api/users/{user_id}] => array(#count url was called, time_taken => array(response time + service time), dynos => array(dyno1 => #count dyno1, dyno2 => #count dyno2...)))
     * @param String $path
     * @return boolean False in case of file not found
     * @return array Array with log stats
     */
    function readLog($path) {
        if (is_file($path)) {
            $logFile = fopen($path, "r");
            if ($logFile) {
                $stats = array();
                while (!feof($logFile)) {
                    $line = (string) fgets($logFile);
                    preg_match($this->parsePattern['look_for'], $line, $matches);
                    if (!empty($matches) && is_array($matches)) {
                        //if total indexes for matches and defined pattern are not same move to next line
                        //remember matches are always n+1 due to one extra complete match string :)
                        if (count($this->parseIndexMap) === count($matches) - 1) {
                            //parseIndexMap shall be parseIndexMap+1 in matches array, coz matches array is N+1
                            $path = $matches[$this->parseIndexMap['path'] + 1];
                            if (isset($path) && !empty($path)) {
                                //replace orignal user id with text {user_id}
                                $replacedPath = $this->getVal(preg_replace('/' . $this->patternUserIdReplacement . '/i', "$1{user_id}$3", $path));
                                $replacedPath = $matches[$this->parseIndexMap['method'] + 1] . ' ' . $replacedPath;
                                //now we have part of endpoint which is concerned with path part, its time to concat method with path to get actual endpoint
                                if (in_array($replacedPath, $this->endpoints)) {
                                    //endpoint found, now we can add this to returning array
                                    //calcualting call count
                                    if (!isset($stats[$replacedPath]['call_count'])) {
                                        $stats[$replacedPath]['call_count'] = null;
                                    }
                                    $stats[$replacedPath]['call_count'] = $this->counter($stats[$replacedPath]['call_count']);

                                    //storing connect time + service time
                                    $stats[$replacedPath]['process_time'][] = $matches[$this->parseIndexMap['connect'] + 1] + $matches[$this->parseIndexMap['service'] + 1];

                                    //now it's time to process dyno
                                    $dyno = $matches[$this->parseIndexMap['dyno'] + 1];
                                    if (!isset($stats[$replacedPath]['dyno'][$dyno])) {
                                        $stats[$replacedPath]['dyno'][$dyno] = null;
                                    }
                                    $stats[$replacedPath]['dyno'][$dyno] = $this->counter($stats[$replacedPath]['dyno'][$dyno]);

                                    //unset variables to release the memory
                                    unset($matches);
                                    unset($replacedPath);
                                    unset($dyno);
                                    unset($path);
                                }
                            }
                        }
                        //median
                        //1 2 3 4 [5 6] 7 8 9 10 (10+1)/2 = //for even count
                        //for odd values 1 2 3 4 5 = (5+1)/2 = 3rd location
                    }
                }
                fclose($logFile);
                return $stats;
            }
        } else {
            return false;
        }
    }

    /**
     * Uses associative array generated by readLog and write the required data to file in following way.
     * Endpoint\n\r endpoint count, average, median, mode, dyno with max count\n\r\n\r
     * @param String $path
     * @param Array $stats
     * @return boolean True on success otherwise False
     */
    function generateStatReport($path, &$stats, $releaseMemory = false) {
        $output = "Generated on: " . date("Y-m-d h:i:s") . "\n\r\n\r";

        foreach ($stats as $key => $val) {
            $output .= "-" . $key . "\n\r";
            $output .= "\t# URL was called: " . $stats[$key]['call_count'];
            //$output .= " debug: ".print_r($stats[$key]['process_time'], true);
            $output .= ", Response time Mean/Avg: " . Utility::mean($stats[$key]['process_time'])."ms";
            $output .= ", Response time Median: " . Utility::meadian($stats[$key]['process_time'])."ms";
            $output .= ", Response time Mode: " . Utility::mode($stats[$key]['process_time'])."ms";

            //mode is the better option to look for largest dyno, coz there can be multipe with same value :)
            $output .= ", Dyno(s) that responded the most: " . implode(",", array_keys($stats[$key]['dyno'], max($stats[$key]['dyno']))) . "\n\r\n\r";
        }

        file_put_contents($path, $output);
        //report generated, its time to release the memory
        unset($output);
        unset($path);
        if ($releaseMemory) {
            unset($stats);
        }
    }

    /**
     * Increments value on each call, if called first time
     * @param int $val
     * @return int
     */
    function counter($val) {
        return (empty($val)) ? 1 : ++$val;
    }

    /**
     * Returns string value, if the value is array returns value at 0 index
     * @param array/string $val
     * @return string
     */
    function getVal($val) {
        return (is_array($val) && isset($val[0])) ? $val[0] : $val;
    }

}
