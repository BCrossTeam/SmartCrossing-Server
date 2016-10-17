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

    public static function verifyFileValidity($file){
        if($file === null || !is_array($file) || !isset($file['error'])){
            return false;
        } else {
            return $file['error'] == UPLOAD_ERR_OK;
        }
    }

    public static function generateCoverFileName($bookId, $file){
        $i = 0;
        do {
            if($i == 0){
                $name = "book_cover_".$bookId.".".pathinfo($file["name"], PATHINFO_EXTENSION);
            } else {
                $name = "book_cover_".$bookId."_".$i.".".pathinfo($file["name"], PATHINFO_EXTENSION);
            }
            $i++;
        } while (file_exists(Settings::COVER_DIRECTORY_PATH.$name));
        return $name;
    }
}