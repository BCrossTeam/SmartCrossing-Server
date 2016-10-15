<?php

namespace Futurologeek\SmartCrossing;


class Support
{
    public static function generateString($length, $characters){
        $result = "";
        $charArray = str_split($characters);
        for($i = 0; $i < $length; $i++){
            $randItem = array_rand($charArray);
            $result .= "".$charArray[$randItem];
        }
        return $result;
    }
}