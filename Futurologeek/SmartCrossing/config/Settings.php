<?php

namespace Futurologeek\SmartCrossing;


class Settings
{
    /* Debug */
    const DEBUG = false;

    /* Verification */
    const CHARACTERS_LETTERS_LOWERCASE          = "abcdefghijklmnopqrstuvwxyz";
    const CHARACTERS_LETTERS_UPPERCASE          = "ABCDEFGHIJKLMNOPQRSTUVDXYZ";
    const CHARACTERS_LETTERS_MIXED_CASE         = self::CHARACTERS_LETTERS_LOWERCASE.self::CHARACTERS_LETTERS_UPPERCASE;
    const CHARACTERS_NUMERIC                    = "0123456789";
    const CHARACTERS_ALPHANUMERIC_LOWERCASE     = self::CHARACTERS_LETTERS_LOWERCASE.self::CHARACTERS_NUMERIC;
    const CHARACTERS_ALPHANUMERIC_UPPERCASE     = self::CHARACTERS_LETTERS_UPPERCASE.self::CHARACTERS_NUMERIC;
    const CHARACTERS_ALPHANUMERIC_MIXED_CASE    = self::CHARACTERS_LETTERS_MIXED_CASE.self::CHARACTERS_NUMERIC;

    /* Error */
    const ERROR_INPUT_EMPTY             = 1;
    const ERROR_INPUT_INVALID           = 2;
    const ERROR_MYSQL_CONNECTION        = 3;
    const ERROR_USER_ALREADY_SIGNED_UP  = 4;
    const ERROR_USER_NOT_EXISTS         = 5;
    const ERROR_SIGN_IN_FAILED          = 6;
    const ERROR_AUTH_FAILED             = 7;
    const ERROR_USER_NOT_SIGNED_IN      = 8;

    const SUB_ERROR_USER_EMAIL_ADDRESS      = 1;
    const SUB_ERROR_USER_NAME               = 2;
    const SUB_ERROR_USER_PASSWORD           = 3;
    const SUB_ERROR_USER_AUTH_TOKEN         = 4;

    /* Success */
    const SUCCESS_SIGNED_UP         = 1;
    const SUCCESS_SIGNED_IN         = 2;
    const SUCCESS_AUTH              = 3;
    const SUCCESS_SIGNED_OUT        = 4;

    /* Database */
    const DATABASE_TABLE_USERS              = "users";
    const KEY_USERS_USER_ID                 = "user_id";
    const KEY_USERS_USER_EMAIL              = "user_email";
    const KEY_USERS_USER_PASSWORD           = "user_password";
    const KEY_USERS_USER_AUTH_TOKEN         = "user_auth_token";
    const KEY_USERS_USER_SIGNED_IN          = "user_signed_in";
    const KEY_USERS_USER_NAME               = "user_name";
    const KEY_USERS_USER_SCORE              = "user_score";
    const KEY_USERS_USER_CREATION_DATE      = "user_creation_date";
    const KEY_USERS_USER_ACCOUNT_TYPE       = "user_account_type";

    /* JSON */
    const JSON_KEY_ERROR        = "error";
    const JSON_KEY_SUCCESS      = "success";
    const JSON_KEY_SUB_ERROR    = "sub_error";
    const JSON_KEY_ERROR_MSG    = "error_msg";

    const JSON_KEY_USERS_USER_ID                 = self::KEY_USERS_USER_ID;
    const JSON_KEY_USERS_USER_EMAIL              = self::KEY_USERS_USER_EMAIL;
    const JSON_KEY_USERS_USER_PASSWORD           = self::KEY_USERS_USER_PASSWORD;
    const JSON_KEY_USERS_USER_AUTH_TOKEN         = self::KEY_USERS_USER_AUTH_TOKEN;
    const JSON_KEY_USERS_USER_SIGNED_IN          = self::KEY_USERS_USER_SIGNED_IN;
    const JSON_KEY_USERS_USER_NAME               = self::KEY_USERS_USER_NAME;
    const JSON_KEY_USERS_USER_SCORE              = self::KEY_USERS_USER_SCORE;
    const JSON_KEY_USERS_USER_CREATION_DATE      = self::KEY_USERS_USER_CREATION_DATE;
    const JSON_KEY_USERS_USER_ACCOUNT_TYPE       = self::KEY_USERS_USER_ACCOUNT_TYPE;

    /* Statics */
    public static function buildErrorMessage($error, ...$params){
        $array = array(self::JSON_KEY_ERROR => $error);
        foreach ($params as $param){
            $array[$param[0]] = $param[1];
        }
        return json_encode($array);
    }

    public static function buildSuccessMessage($success, ...$params){
        $array = array(self::JSON_KEY_SUCCESS => $success);
        foreach ($params as $param){
            $array[$param[0]] = $param[1];
        }
        return json_encode($array);
    }
}