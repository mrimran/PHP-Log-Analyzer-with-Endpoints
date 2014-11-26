<?php

/**
 * Generic functions
 *
 * @author imran
 */
class Utility {

    public static function mean(&$array) {
        return @array_sum($array) / count($array);
    }

    public static function meadian(&$array) {
        rsort($array);
        $mid = floor((count($array)+1)/2);
        //w.r.t to index it's mid-1, coz index starts from 0
        $mid = $mid-1;
        if (count($array) % 2) {//if even
            return @($array[$mid] + $array[$mid+1])/2;
        } else {//if odd
            return $array[$mid];
        }
    }

    public static function mode(&$array) {
        $arrayCount = array_count_values($array);
        arsort($arrayCount);
        //just loop for one iteration to know the largest value
        //we will use the value to find out if there are multiple mode values
        $maxVal = -1;
        foreach($arrayCount as $key => $val) {
            $maxVal = $key;
            break;//Only execute the loop for single iteration
        }
        
        unset($arrayCount);
        
        return $maxVal;
    }

}
