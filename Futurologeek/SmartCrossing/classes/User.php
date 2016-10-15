<?php

namespace Futurologeek\SmartCrossing;

include_once("../config/Settings.php");
include_once("../support/Support.php");
include_once("../support/DatabaseConnection.php");

class User
{
    const USER_ACCOUNT_TYPE_USER        = "u";
    const USER_ACCOUNT_TYPE_MODERATOR   = "m";
    const USER_ACCOUNT_TYPE_ADMIN       = "a";

    private $userId = 0;
    private $userEmail = "";
    private $userPassword = "";
    private $userAuthToken = "";
    private $userName = "";
    private $userScore = 0;
    private $userAccountType;

    /**
     * @return int
     */
    public function getUserId(){
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId){
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUserEmail(){
        return $this->userEmail;
    }

    /**
     * @param string $userEmail
     */
    public function setUserEmail($userEmail){
        $this->userEmail = $userEmail;
    }

    /**
     * @return string
     */
    public function getUserPassword(){
        return $this->userPassword;
    }

    /**
     * @param string $userPassword
     */
    public function setUserPassword($userPassword){
        $this->userPassword = $userPassword;
    }

    /**
     * @return string
     */
    public function getUserAuthToken(){
        return $this->userAuthToken;
    }

    /**
     * @param string $userAuthToken
     */
    public function setUserAuthToken($userAuthToken){
        $this->userAuthToken = $userAuthToken;
    }

    /**
     * @return string
     */
    public function getUserName(){
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName){
        $this->userName = $userName;
    }

    /**
     * @return int
     */
    public function getUserScore(){
        return $this->userScore;
    }

    /**
     * @param int $userScore
     */
    public function setUserScore($userScore){
        $this->userScore = $userScore;
    }

    /**
     * @return string
     */
    public function getUserAccountType()
    {
        return $this->userAccountType;
    }

    /**
     * @param string $userAccountType
     */
    public function setUserAccountType($userAccountType)
    {
        switch ($userAccountType){
            case self::USER_ACCOUNT_TYPE_USER:
            case self::USER_ACCOUNT_TYPE_MODERATOR:
            case self::USER_ACCOUNT_TYPE_ADMIN:
                $this->userAccountType = $userAccountType;
                break;

            default:
                $this->userAccountType = null;
        }
    }


    /**
     * @return int|string
     */
    private function generateAuthToken(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        do{
            $token = Support::generateString(20, Settings::CHARACTERS_ALPHANUMERIC_LOWERCASE);
            $exists = $mysqli->databaseExists(Settings::DATABASE_TABLE_USERS, Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$token]);
        } while($exists >= 1);
        $mysqli->databaseClose();
        return $exists < 0 ? -1 : $this->userAuthToken = $token;
    }

    /**
     * @return bool|string
     */
    public function signUp(){
        if(($temp = self::validateEmailAddress($this->userEmail)) !== true){ return $temp; }
        if(($temp = self::validatePassword($this->userPassword)) !== true){ return $temp; }
        if(($temp = self::validateUserName($this->userName)) !== true){ return $temp; }

        switch($this->isSignedUp()){
            case -1:
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                break;

            case false:
                break;

            case true:
                return Settings::buildErrorMessage(Settings::ERROR_USER_ALREADY_SIGNED_UP);
                break;
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $password = password_hash($this->userPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_EMAIL, Settings::KEY_USERS_USER_PASSWORD, Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_CREATION_DATE],
            "ssss", [$this->userEmail, $password, $this->userName, date_create(null, new \DateTimeZone("UTC"))->format('Y-m-d H:i:s')]);
        $mysqli->databaseClose();
        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $data = [];
            $user = $this->getUser(true);
            if($user != null) {
                foreach ($user as $key => $value){
                    $data[] = [$key, $value];
                }
            }
            return Settings::buildSuccessMessage(Settings::SUCCESS_SIGNED_UP, ...$data);
        }
    }

    /**
     * @return bool|string
     */
    public function signIn(){
        if(($temp = self::validateEmailAddress($this->userEmail)) !== true){ return $temp; }
        if(($temp = self::validatePassword($this->userPassword)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $password = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_PASSWORD], Settings::KEY_USERS_USER_EMAIL . "=?", "s", [$this->userEmail]);
        if($password == -1) {
            $mysqli->databaseClose();
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($password === null || count($password) <= 0) {
            $mysqli->databaseClose();
            return Settings::buildErrorMessage(Settings::ERROR_SIGN_IN_FAILED);
        }

        $token = $this->generateAuthToken();
        if(password_verify($this->userPassword, $password[0][0])){
            $result = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_SIGNED_IN, Settings::KEY_USERS_USER_AUTH_TOKEN],
                "is", [1, $token], Settings::JSON_KEY_USERS_USER_EMAIL . "=?", "s", [$this->userEmail]);
            $mysqli->databaseClose();

            if($result == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } if($result == 0){
                return Settings::buildErrorMessage(Settings::ERROR_SIGN_IN_FAILED);
            } else {
                $data = array([Settings::JSON_KEY_USERS_USER_AUTH_TOKEN, $this->userAuthToken]);
                $user = $this->getUser(true);
                if($user != null) {
                    foreach ($user as $key => $value){
                        $data[] = [$key, $value];
                    }
                }
                return Settings::buildSuccessMessage(Settings::SUCCESS_SIGNED_IN, ...$data);
            }
        } else {
            return Settings::buildErrorMessage(Settings::ERROR_SIGN_IN_FAILED);
        }
    }

    /**
     * @param bool $returnRaw
     * @return array|bool|int|null|string
     */
    public function signAuth($returnRaw = false){
        if(($temp = self::validateAuthToken($this->userAuthToken)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $id = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_ID], Settings::KEY_USERS_USER_AUTH_TOKEN . "=? AND " . Settings::KEY_USERS_USER_SIGNED_IN . "=?" , "si", [$this->userAuthToken, 1]);
        $mysqli->databaseClose();
        if($id == -1) {
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else if($id === null || count($id) <= 0) {
            if($returnRaw){
                return null;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
            }
        } else {
            $this->userId = $id[0][0];
            if($returnRaw){
                return [
                    Settings::JSON_KEY_SUCCESS => Settings::SUCCESS_AUTH,
                    Settings::JSON_KEY_USERS_USER_ID => $this->userId
                ];
            } else {
                return Settings::buildSuccessMessage(Settings::SUCCESS_AUTH, [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);

            }
        }
    }

    /**
     * @param bool $returnRaw
     * @return array|bool|int|null|string
     */
    public function signOut($returnRaw = false){
        if(($temp = self::validateAuthToken($this->userAuthToken)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_SIGNED_IN, Settings::KEY_USERS_USER_AUTH_TOKEN], "is", [0, null], Settings::KEY_USERS_USER_AUTH_TOKEN . "=?", "s", [$this->userAuthToken]);
        $mysqli->databaseClose();
        if($result == -1) {
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else if($result === null || $result <= 0) {
            if($returnRaw){
                return null;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_SIGNED_IN);
            }
        } else {
            if($returnRaw){
                return [
                    Settings::JSON_KEY_SUCCESS => Settings::SUCCESS_SIGNED_OUT
                ];
            } else {
                return Settings::buildSuccessMessage(Settings::SUCCESS_SIGNED_OUT);
            }
        }
    }

    /**
     * @return bool|int
     */
    public function isSignedUp(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseExists(Settings::DATABASE_TABLE_USERS, Settings::KEY_USERS_USER_EMAIL."=?", "s", [$this->userEmail]);
        $mysqli->databaseClose();
        return $result;
    }

    /**
     * @param bool $returnRaw
     * @return null|string
     */
    public function getUser($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        if($this->userId != null){
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_SCORE],
                Settings::KEY_USERS_USER_ID."=?", "i", [$this->userId]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return null;
                } else {
                    return [
                        Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                        Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                        Settings::JSON_KEY_USERS_USER_SCORE => $result[0][1]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS, [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                } else {
                    return json_encode([
                        Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                        Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                        Settings::JSON_KEY_USERS_USER_SCORE => $result[0][1]
                    ]);
                }
            }
        } else if($this->userEmail != null){
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_SCORE],
                Settings::KEY_USERS_USER_EMAIL."=?", "s", [$this->userEmail]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return null;
                } else {
                    return [
                        Settings::JSON_KEY_USERS_USER_ID => $result[0][0],
                        Settings::JSON_KEY_USERS_USER_NAME => $result[0][1],
                        Settings::JSON_KEY_USERS_USER_SCORE => $result[0][2]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS, [Settings::JSON_KEY_USERS_USER_EMAIL, $this->userEmail]);
                } else {
                    return json_encode([
                        Settings::JSON_KEY_USERS_USER_ID => $result[0][0],
                        Settings::JSON_KEY_USERS_USER_NAME => $result[0][1],
                        Settings::JSON_KEY_USERS_USER_SCORE => $result[0][2]
                    ]);
                }
            }
        } else {
            if($returnRaw){
                return null;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
            }
        }
    }

    /* Statics */
    /**
     * @param $value
     * @return bool|string
     */
    public static function validateEmailAddress($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_EMAIL_ADDRESS]);
        } else if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_EMAIL_ADDRESS]);
        } else {
            return true;
        }
    }

    /**
     * @param $value
     * @return bool|string
     */
    public static function validatePassword($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_PASSWORD]);
        } else if(preg_match("/\\s+/", $value)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_PASSWORD]);
        } else {
            return true;
        }
    }

    /**
     * @param $value
     * @return bool|string
     */
    public static function validateAuthToken($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_AUTH_TOKEN]);
        } else if((strlen($value) != 20) || (preg_match("/[^".Settings::CHARACTERS_ALPHANUMERIC_LOWERCASE."]+/", $value) != 0)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID, [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_AUTH_TOKEN]);
        } else {
            return true;
        }
    }

    /**
     * @param $value
     * @return bool
     */
    public static function validateUserName($value){
        return true;
    }
}