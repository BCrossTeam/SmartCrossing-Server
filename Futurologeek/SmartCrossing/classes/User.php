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

    /** @var int */
    private $userId;
    /** @var string */
    private $userEmail;
    /** @var string */
    private $userPassword;
    /** @var string */
    private $userAuthToken;
    /** @var string */
    private $userName;
    /** @var int */
    private $userScore;
    /**
     * Requires USER_ACCOUNT_TYPE_... constant as value.
     * @var string
     */
    private $userAccountType;

    /** @return int */
    public function getUserId(){
        return $this->userId;
    }

    /** @param int $userId */
    public function setUserId($userId){
        $this->userId = $userId;
    }

    /** @return string */
    public function getUserEmail(){
        return $this->userEmail;
    }

    /** @param string $userEmail */
    public function setUserEmail($userEmail){
        $this->userEmail = $userEmail;
    }

    /** @return string */
    public function getUserPassword(){
        return $this->userPassword;
    }

    /** @param string $userPassword */
    public function setUserPassword($userPassword){
        $this->userPassword = $userPassword;
    }

    /** @return string */
    public function getUserAuthToken(){
        return $this->userAuthToken;
    }

    /** @param string $userAuthToken */
    public function setUserAuthToken($userAuthToken){
        $this->userAuthToken = $userAuthToken;
    }

    /** @return string */
    public function getUserName(){
        return $this->userName;
    }

    /** @param string $userName */
    public function setUserName($userName){
        $this->userName = $userName;
    }

    /** @return int */
    public function getUserScore(){
        return $this->userScore;
    }

    /** @param int $userScore */
    public function setUserScore($userScore){
        $this->userScore = $userScore;
    }

    /** @return string */
    public function getUserAccountType()
    {
        return $this->userAccountType;
    }

    /**
     * Requires USER_ACCOUNT_TYPE_... constant as value.
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
     * Function used to generate unique AuthToken
     *
     * Returns:
     * -1 on mysqli error
     * unique AuthToken on success
     *
     * @return int|string
     */
    private function generateAuthToken(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        do{
            $token = Support::generateString(20, Settings::CHARACTERS_ALPHANUMERIC_LOWERCASE);
            $exists = $mysqli->databaseExists(Settings::DATABASE_TABLE_USERS, Settings::KEY_USERS_USER_AUTH_TOKEN."=?",
                "s", [$token]);
        } while($exists >= 1);
        $mysqli->databaseClose();
        return $exists < 0 ? -1 : $this->userAuthToken = $token;
    }

    /**
     * Function used to add user to database.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
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
            [Settings::KEY_USERS_USER_EMAIL, Settings::KEY_USERS_USER_PASSWORD, Settings::KEY_USERS_USER_NAME,
                Settings::KEY_USERS_USER_CREATION_DATE], "ssss", [$this->userEmail, $password, $this->userName,
                date_create(null, new \DateTimeZone("UTC"))->format('Y-m-d H:i:s')]);
        $mysqli->databaseClose();
        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $data = [];
            $user = $this->getUser(true);
            if($user != null && !is_int($user)) {
                foreach ($user as $key => $value){
                    $data[] = [$key, $value];
                }
            }
            return Settings::buildSuccessMessage(Settings::SUCCESS_SIGNED_UP, ...$data);
        }
    }

    /**
     * Function used to sign in user and generate valid AuthToken.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function signIn(){
        if(($temp = self::validateEmailAddress($this->userEmail)) !== true){ return $temp; }
        if(($temp = self::validatePassword($this->userPassword)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $password = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_PASSWORD],
            Settings::KEY_USERS_USER_EMAIL . "=?", "s", [$this->userEmail]);
        if($password == -1) {
            $mysqli->databaseClose();
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($password === null || count($password) <= 0) {
            $mysqli->databaseClose();
            return Settings::buildErrorMessage(Settings::ERROR_SIGN_IN_FAILED);
        }

        $token = $this->generateAuthToken();
        if(password_verify($this->userPassword, $password[0][0])){
            $result = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS,
                [Settings::KEY_USERS_USER_SIGNED_IN, Settings::KEY_USERS_USER_AUTH_TOKEN],
                "is", [1, $token], Settings::JSON_KEY_USERS_USER_EMAIL . "=?", "s", [$this->userEmail]);
            $mysqli->databaseClose();

            if($result == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } if($result == 0){
                return Settings::buildErrorMessage(Settings::ERROR_SIGN_IN_FAILED);
            } else {
                $data = [[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN, $this->userAuthToken]];
                $user = $this->getUser(true);
                if($user != null && !is_int($user)) {
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
     * Function used to check if AuthToken is active.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null on failure
     * Array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function signAuth($returnRaw = false){
        if(($temp = self::validateAuthToken($this->userAuthToken)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $id = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN . "=? AND " . Settings::KEY_USERS_USER_SIGNED_IN . "=?",
            "si", [$this->userAuthToken, 1]);
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
                return Settings::buildSuccessMessage(Settings::SUCCESS_AUTH,
                    [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
            }
        }
    }

    /**
     * Function used to sign out user and invalidate AuthToken.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null on failure
     * Array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function signOut($returnRaw = false){
        if(($temp = self::validateAuthToken($this->userAuthToken)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_SIGNED_IN, Settings::KEY_USERS_USER_AUTH_TOKEN], "is", [0, null],
            Settings::KEY_USERS_USER_AUTH_TOKEN . "=?", "s", [$this->userAuthToken]);
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
     * Function used to check if user account exists.
     *
     * Returns:
     * -1 on mysql error
     * false if user is not signed up
     * true if user is signed up
     *
     * @return bool|int
     */
    public function isSignedUp(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseExists(Settings::DATABASE_TABLE_USERS, Settings::KEY_USERS_USER_EMAIL."=?",
            "s", [$this->userEmail]);
        $mysqli->databaseClose();
        return $result;
    }

    /**
     * Function used to get user public data (id, name, score) using user id or user email address.
     *
     * @param bool $returnRaw
     * @param bool $detailed
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null on failure
     * Array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getUser($returnRaw = false, $detailed = false){
        if($this->userId > 0){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
                [Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_SCORE,
                    Settings::KEY_USERS_BADGE_ADDED_BOOKS_TIER, Settings::KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER,
                    Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER,
                    Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER, Settings::KEY_USERS_BADGE_SCORE_TIER],
                Settings::KEY_USERS_USER_ID."=?", "i", [$this->userId]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    $score = $this->countScore(true, true);
                    if($detailed){
                        $checkedBadges = $this->checkBadges(true, true);
                        return [
                            Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                            Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                            Settings::JSON_KEY_USERS_USER_SCORE => $score !== -1 ? $score : $result[0][1],
                            Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKS_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKS_TIER] : $result[0][2],
                            Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKSHELVES_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER] : $result[0][3],
                            Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_USER_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER] : $result[0][4],
                            Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER => $checkedBadges !== -1 ?
                            $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER] : $result[0][5],
                            Settings::JSON_KEY_USER_STATS_BADGE_SCORE_TIER => $checkedBadges !== -1 ?
                            $checkedBadges[Settings::JSON_KEY_USERS_BADGE_SCORE_TIER] : $result[0][6]
                        ];
                    } else{
                        return [
                            Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                            Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                            Settings::JSON_KEY_USERS_USER_SCORE => $score !== -1 ? $score : $result[0][1]
                        ];
                    }
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                        [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                } else {
                    $score = $this->countScore(true, true);
                    if($detailed){
                        $checkedBadges = $this->checkBadges(true, true);
                        return json_encode([
                            Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                            Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                            Settings::JSON_KEY_USERS_USER_SCORE => $score !== -1 ? $score : $result[0][1],
                            Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKS_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKS_TIER] : $result[0][2],
                            Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKSHELVES_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER] : $result[0][3],
                            Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_USER_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER] : $result[0][4],
                            Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER] : $result[0][5],
                            Settings::JSON_KEY_USER_STATS_BADGE_SCORE_TIER => $checkedBadges !== -1 ?
                                $checkedBadges[Settings::JSON_KEY_USERS_BADGE_SCORE_TIER] : $result[0][6]
                        ]);
                    } else{
                        return json_encode([
                            Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                            Settings::JSON_KEY_USERS_USER_NAME => $result[0][0],
                            Settings::JSON_KEY_USERS_USER_SCORE => $score !== -1 ? $score : $result[0][1]
                        ]);
                    }
                }
            }
        } else if($this->userEmail != null){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
                [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_SCORE],
                Settings::KEY_USERS_USER_EMAIL."=?", "s", [$this->userEmail]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
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
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                        [Settings::JSON_KEY_USERS_USER_EMAIL, $this->userEmail]);
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
                return -2;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
            }
        }
    }

    /**
     * Function used to count user score.
     *
     * @param bool $returnRaw
     * @param bool $omitUserCheck - omits checking if user exists (to not start infinite loop in getUser)
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null if user not exists
     * int on success (score)
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return int|null|string
     */
    public function countScore($returnRaw = false, $omitUserCheck = false){
        if(!$omitUserCheck){
            $exists = $this->getUser(true);
            if($exists === null || $exists === -1 || $exists === -2){
                if($returnRaw){
                    return $exists;
                } else {
                    if($exists === -1){
                        return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                    } elseif($exists === -2){
                        return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                    } else {
                        return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                            [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                    }
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $addedBooksCount = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKS,
            Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?", "i", [$this->userId]);

        $addedBookshelvesCount = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELVES,
            Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR."=?", "i", [$this->userId]);

        $borrowedByUserCount = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            " WHERE ".Settings::KEY_BORROWED_BOOKS_USER_ID."=?) as a", [null], "i", [$this->userId]);

        $borrowedByOtherCount = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            " WHERE ".Settings::KEY_RETURNED_BOOKS_BOOK_ID.
            " IN (SELECT ".Settings::KEY_BOOKS_BOOK_ID." FROM ".Settings::DATABASE_TABLE_BOOKS.
            " WHERE ".Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?) AND ".
            Settings::KEY_RETURNED_BOOKS_USER_ID."!=?) as a", [null], "ii", [$this->userId, $this->userId]);

        if($addedBooksCount === -1 || $addedBookshelvesCount === -1 || $borrowedByUserCount === -1
            || $borrowedByOtherCount === -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        }

        $score = $addedBooksCount * Settings::USER_SCORE_MULTIPLIER_ADDED_BOOKS
            + $addedBookshelvesCount * Settings::USER_SCORE_MULTIPLIER_ADDED_BOOKSHELVES
            + $borrowedByUserCount[0][0] * Settings::USER_SCORE_MULTIPLIER_BOOKS_BORROWED_BY_USER
            + $borrowedByOtherCount[0][0] * Settings::USER_SCORE_MULTIPLIER_BOOKS_BORROWED_BY_OTHERS;
        $updated = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_USER_SCORE],
            "i", [$score], Settings::KEY_USERS_USER_ID."=?", "i", [$this->userId]);
        $mysqli->databaseClose();

        if($updated === -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            if($returnRaw){
                return $score;
            } else {
                return Settings::buildSuccessMessage(Settings::SUCCESS_USER_SCORE_UPDATED,
                    [Settings::JSON_KEY_USERS_USER_SCORE, $score]);
            }
        }
    }

    /**
     * Function used to evaluate achieved badge tiers.
     *
     * @param bool $returnRaw
     * @param bool $omitUserCheck - omits checking if user exists (to not start infinite loop in getUser)
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null if user not exists
     * Array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function checkBadges($returnRaw = false, $omitUserCheck = false){
        if(!$omitUserCheck){
            $exists = $this->getUser(true);
            if($exists === null || $exists === -1 || $exists === -2){
                if($returnRaw){
                    return $exists;
                } else {
                    if($exists === -1){
                        return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                    } elseif($exists === -2){
                        return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                    } else {
                        return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                            [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                    }
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $addedBooks = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKS,
            Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?", "i", [$this->userId]);
        $addedBookshelves = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELVES,
            Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR."=?", "i", [$this->userId]);
        $booksBorrowedByUser = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            " WHERE ".Settings::KEY_BORROWED_BOOKS_USER_ID."=?) as a", [null], "i", [$this->userId]);
        $booksBorrowedByOthers = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            " WHERE ".Settings::KEY_RETURNED_BOOKS_BOOK_ID.
            " IN (SELECT ".Settings::KEY_BOOKS_BOOK_ID." FROM ".Settings::DATABASE_TABLE_BOOKS.
            " WHERE ".Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?) AND ".
            Settings::KEY_RETURNED_BOOKS_USER_ID."!=?) as a", [null], "ii", [$this->userId, $this->userId]);
        $score = $this->countScore(true, true);

        if($addedBooks === -1 || $addedBookshelves === -1 || $booksBorrowedByUser === -1
            || $booksBorrowedByOthers === -1 || $score === -1){
            $mysqli->databaseClose();
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        }

        $addedBooksTier = 0;
        foreach (Settings::BADGES_ADDED_BOOKS_TIER_REQUIREMENTS as $requirements){
            if($addedBooks >= $requirements){
                $addedBooksTier++;
            } else {
                break;
            }
        }

        $addedBookshelvesTier = 0;
        foreach (Settings::BADGES_ADDED_BOOKSHELVES_TIER_REQUIREMENTS as $requirements){
            if($addedBookshelves >= $requirements){
                $addedBookshelvesTier++;
            } else {
                break;
            }
        }

        $booksBorrowedByUserTier = 0;
        foreach (Settings::BADGES_BOOKS_BORROWED_BY_USER_TIER_REQUIREMENTS as $requirements){
            if($booksBorrowedByUser[0][0] >= $requirements){
                $booksBorrowedByUserTier++;
            } else {
                break;
            }
        }

        $booksBorrowedByOthersTier = 0;
        foreach (Settings::BADGES_BOOKS_BORROWED_BY_OTHER_TIER_REQUIREMENTS as $requirements){
            if($booksBorrowedByOthers[0][0] >= $requirements){
                $booksBorrowedByOthersTier++;
            } else {
                break;
            }
        }

        $scoreTier = 0;
        foreach (Settings::BADGES_SCORE_TIER_REQUIREMENTS as $requirements){
            if($score >= $requirements){
                $scoreTier++;
            } else {
                break;
            }
        }

        $update = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_BADGE_ADDED_BOOKS_TIER, Settings::KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER,
            Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER, Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER,
            Settings::KEY_USERS_BADGE_SCORE_TIER], "iiiii", [$addedBooksTier, $addedBookshelvesTier,
                $booksBorrowedByUserTier, $booksBorrowedByOthersTier, $scoreTier],
            Settings::KEY_USERS_USER_ID."=?", "i", [$this->userId]
        );
        $mysqli->databaseClose();

        if($update === -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            if($returnRaw){
                return [
                    Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKS_TIER => $addedBooksTier,
                    Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER => $addedBookshelvesTier,
                    Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER => $booksBorrowedByUserTier,
                    Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER => $booksBorrowedByOthersTier,
                    Settings::JSON_KEY_USERS_BADGE_SCORE_TIER => $scoreTier
                ];
            } else {
                return json_encode([
                    Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKS_TIER => $addedBooksTier,
                    Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER => $addedBookshelvesTier,
                    Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER => $booksBorrowedByUserTier,
                    Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER => $booksBorrowedByOthersTier,
                    Settings::JSON_KEY_USERS_BADGE_SCORE_TIER => $scoreTier
                ]);
            }
        }
    }

    /**
     * Function used to get user stats (score, added books, borrowed books, borrowed unique books, returned books,
     * returned unique books, score, badges). If an error occurred during fetching certain data its value is
     * replaced by null.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if book not exists
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getUserStats($returnRaw = false){
        $exists = $this->getUser(true);
        if($exists === null || $exists === -1 || $exists === -2){
            if($returnRaw){
                return $exists;
            } else {
                if($exists === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } elseif($exists === -2){
                    return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                        [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $userScore = $this->countScore(true);

        $addedBooks = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKS,
            Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?", "i", [$this->userId]);

        $borrowedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_BORROWED_BOOKS,
            Settings::KEY_BORROWED_BOOKS_USER_ID."=?", "i", [$this->userId]);

        $borrowedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            " WHERE ".Settings::KEY_BORROWED_BOOKS_USER_ID."=?) as a", [null], "i", [$this->userId]);

        $returnedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_RETURNED_BOOKS,
            Settings::KEY_RETURNED_BOOKS_USER_ID."=?", "i", [$this->userId]);

        $returnedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            " WHERE ".Settings::KEY_RETURNED_BOOKS_USER_ID."=?) as a", [null], "i", [$this->userId]);

        $badges = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS, [Settings::KEY_USERS_BADGE_ADDED_BOOKS_TIER,
            Settings::KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER, Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER,
            Settings::KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER, Settings::KEY_USERS_BADGE_SCORE_TIER],
            Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=?", "i", [$this->userId]);

        $mysqli->databaseClose();

        $output = [Settings::JSON_KEY_USER_STATS_USER_ID => $this->userId];
        $output[Settings::JSON_KEY_USER_STATS_USER_SCORE] = $userScore !== -1 ? $userScore : null;
        $output[Settings::JSON_KEY_USER_STATS_BORROW_GENERAL_COUNT] = $addedBooks !== -1 ? $addedBooks : null;
        $output[Settings::JSON_KEY_USER_STATS_BORROW_GENERAL_COUNT] = $borrowedGeneral !== -1 ? $borrowedGeneral : null;
        $output[Settings::JSON_KEY_USER_STATS_BORROW_UNIQUE_COUNT] = $borrowedUnique !== -1 ? $borrowedUnique[0][0] : null;
        $output[Settings::JSON_KEY_USER_STATS_RETURN_GENERAL_COUNT] = $returnedGeneral !== -1 ? $returnedGeneral : null;
        $output[Settings::JSON_KEY_USER_STATS_RETURN_UNIQUE_COUNT] = $returnedUnique !== -1 ? $returnedUnique[0][0] : null;

        $checkedBadges = $this->checkBadges(true, true);
        $output[Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKS_TIER] =
            $checkedBadges !== -1 ? $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKS_TIER] :
                ($badges !== -1 && $badges !== null ? $badges[0][0] : null);
        $output[Settings::JSON_KEY_USER_STATS_BADGE_ADDED_BOOKSHELVES_TIER] =
            $checkedBadges !== -1 ? $checkedBadges[Settings::JSON_KEY_USERS_BADGE_ADDED_BOOKSHELVES_TIER] :
                ($badges !== -1 && $badges !== null ? $badges[0][1] : null);
        $output[Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_USER_TIER] =
            $checkedBadges !== -1 ? $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_USER_TIER] :
                ($badges !== -1 && $badges !== null ? $badges[0][2] : null);
        $output[Settings::JSON_KEY_USER_STATS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER] =
            $checkedBadges !== -1 ? $checkedBadges[Settings::JSON_KEY_USERS_BADGE_BOOKS_BORROWED_BY_OTHERS_TIER] :
                ($badges !== -1 && $badges !== null ? $badges[0][3] : null);
        $output[Settings::JSON_KEY_USER_STATS_BADGE_SCORE_TIER] =
            $checkedBadges !== -1 ? $checkedBadges[Settings::JSON_KEY_USERS_BADGE_SCORE_TIER] :
                ($badges !== -1 && $badges !== null ? $badges[0][4] : null);

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
        }
    }

    /**
     * Function used to get list of books borrowed by user.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if user not exists
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getBorrowedBooks($returnRaw = false){
        $exists = $this->getUser(true);
        if($exists === null || $exists === -1 || $exists === -2){
            if($returnRaw){
                return $exists;
            } else {
                if($exists === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } elseif($exists === -2){
                    return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                        [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseRawQuery(
            "SELECT ".Settings::KEY_BOOKS_BOOK_ID.", ".Settings::KEY_BOOKS_BOOK_TITLE.
            ", ".Settings::KEY_BOOKS_BOOK_AUTHOR." FROM ".Settings::DATABASE_TABLE_BOOKS." WHERE ".
            Settings::KEY_BOOKS_BOOK_ID." IN (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID." FROM ".
            Settings::DATABASE_TABLE_BORROWED_BOOKS." LEFT JOIN (SELECT ".Settings::DATABASE_TABLE_BORROWED_BOOKS.".".
            Settings::KEY_BORROWED_BOOKS_BOOK_ID." as borrowed_book, COUNT(".Settings::DATABASE_TABLE_BORROWED_BOOKS.".".
            Settings::KEY_BORROWED_BOOKS_BORROW_ID.") as borrow_count, ".Settings::KEY_BORROWED_BOOKS_BORROW_TIME." FROM "
            .Settings::DATABASE_TABLE_BORROWED_BOOKS. " WHERE ".Settings::DATABASE_TABLE_BORROWED_BOOKS.".".
            Settings::KEY_BORROWED_BOOKS_USER_ID."=? GROUP BY ". Settings::DATABASE_TABLE_BORROWED_BOOKS.".".
            Settings::KEY_BORROWED_BOOKS_BOOK_ID.") AS A ON A.borrowed_book=". Settings::KEY_BOOKS_BOOK_ID.
            " LEFT JOIN (SELECT ".Settings::DATABASE_TABLE_RETURNED_BOOKS.".". Settings::KEY_BORROWED_BOOKS_BOOK_ID.
            " as returned_book, COUNT(".Settings::DATABASE_TABLE_RETURNED_BOOKS.".".
            Settings::KEY_RETURNED_BOOKS_RETURN_ID.") as return_count, ".Settings::KEY_RETURNED_BOOKS_RETURN_TIME.
            " FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS. " WHERE ".Settings::DATABASE_TABLE_RETURNED_BOOKS."."
            .Settings::KEY_RETURNED_BOOKS_USER_ID."=? GROUP BY ". Settings::DATABASE_TABLE_RETURNED_BOOKS.".".
            Settings::JSON_KEY_RETURNED_BOOKS_BOOK_ID.") AS B ON B.returned_book=".Settings::KEY_BOOKS_BOOK_ID.
            " WHERE (borrow_count > return_count) OR (borrow_count=return_count AND A.".
            Settings::KEY_BORROWED_BOOKS_BORROW_TIME.">B.".Settings::KEY_RETURNED_BOOKS_RETURN_TIME.
            ") OR (borrow_count IS NOT NULL AND return_count IS NULL)) UNION SELECT " .Settings::KEY_BOOKS_BOOK_ID.
            ", ".Settings::KEY_BOOKS_BOOK_TITLE.", ".Settings::KEY_BOOKS_BOOK_AUTHOR. " FROM ".
            Settings::DATABASE_TABLE_BOOKS." WHERE ".Settings::KEY_BOOKS_BOOK_USER_AUTHOR."=? AND " .
            Settings::KEY_BOOKS_BOOK_ID." NOT IN (SELECT ".Settings::JSON_KEY_RETURNED_BOOKS_BOOK_ID." FROM ".
            Settings::DATABASE_TABLE_RETURNED_BOOKS." WHERE ".Settings::JSON_KEY_RETURNED_BOOKS_USER_ID."=?)",
            [Settings::JSON_KEY_BOOKS_BOOK_ID, Settings::JSON_KEY_BOOKS_BOOK_TITLE,
                Settings::JSON_KEY_BOOKS_BOOK_AUTHOR], "iiii", [$this->userId, $this->userId, $this->userId, $this->userId]);
        $mysqli->databaseClose();

        if($returnRaw){
            if($result === -1 || $result === null){
                return $result;
            } else if($result === null){
                return [Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                    Settings::JSON_KEY_USER_STATS_BORROWED_BOOKS  => []];
            } else {
                $output = [Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                    Settings::JSON_KEY_USER_STATS_BORROWED_BOOKS  => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_USER_STATS_BORROWED_BOOKS][] = [
                        Settings::JSON_KEY_BOOKS_BOOK_ID => $item[0],
                        Settings::JSON_KEY_BOOKS_BOOK_TITLE => $item[1],
                        Settings::JSON_KEY_BOOKS_BOOK_AUTHOR => $item[2]
                    ];
                }
                return $output;
            }
        } else {
            if($result === -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null){
                return Settings::buildErrorMessage(Settings::ERROR_USER_NOT_EXISTS,
                    [Settings::JSON_KEY_USERS_USER_ID, $this->userId]);
            } else {
                $output = [Settings::JSON_KEY_USERS_USER_ID => $this->userId,
                    Settings::JSON_KEY_USER_STATS_BORROWED_BOOKS  => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_USER_STATS_BORROWED_BOOKS][] = [
                        Settings::JSON_KEY_BOOKS_BOOK_ID => $item[0],
                        Settings::JSON_KEY_BOOKS_BOOK_TITLE => $item[1],
                        Settings::JSON_KEY_BOOKS_BOOK_AUTHOR => $item[2]
                    ];
                }
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to update global ranking (count score of every user in for loop).
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public static function updateGlobalRanking(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID]);

        if($result === -1 || $result === null){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $user = new User();
            foreach ($result as $item) {
                $user->setUserId($item[0]);
                $user->countScore();
            }
            return Settings::buildSuccessMessage(Settings::SUCCESS_USER_SCORE_UPDATED);
        }
    }

    /**
     * Function used to get global ranking. If buffer is different than 0 function returns only top $buffer users.
     *
     * @param int $buffer
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null if no users are present in database
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function getGlobalRanking($buffer = 0, $returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_NAME, Settings::KEY_USERS_USER_SCORE],
            null, null, null, Settings::KEY_USERS_USER_SCORE, true, $buffer);
        $mysqli->databaseClose();

        if($returnRaw){
            if($result === -1 || $result === null){
                return $result;
            } else {
                $output = [];
                foreach ($result as $item) {
                    $output[] = [
                        Settings::JSON_KEY_USERS_USER_ID => $item[0],
                        Settings::JSON_KEY_USERS_USER_NAME => $item[1],
                        Settings::JSON_KEY_USERS_USER_SCORE => $item[2]
                    ];
                }
                return $output;
            }
        } else {
            if($result === -1 || $result === null){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else {
                $output = [];
                foreach ($result as $item) {
                    $output[] = [
                        Settings::JSON_KEY_USERS_USER_ID => $item[0],
                        Settings::JSON_KEY_USERS_USER_NAME => $item[1],
                        Settings::JSON_KEY_USERS_USER_SCORE => $item[2]
                    ];
                }
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to get global user stats (user count). If an error occurred during fetching certain data its value is
     * replaced by null.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * array
     *
     * If $returnRaw is false:
     * string
     *
     * @return array|string
     */
    public static function getGlobalUserStats($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $usersCount = $mysqli->databaseCount(Settings::DATABASE_TABLE_USERS);

        $mysqli->databaseClose();

        $output = [];
        $output[Settings::JSON_KEY_USER_STATS_GLOBAL_USERS_COUNT] = $usersCount !== -1 ? $usersCount : null;

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
        }
    }

    /* Statics */
    /**
     * Function used to check if provided email address format is valid.
     *
     * @param $value
     *
     * Returns:
     * Error message on error
     * false if value is not valid
     * true if value is valid
     *
     * @return bool|string
     */
    public static function validateEmailAddress($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_EMAIL_ADDRESS]);
        } else if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_EMAIL_ADDRESS]);
        } else {
            return true;
        }
    }

    /**
     * Function used to check if provided password format is in valid.
     *
     * @param $value
     *
     * Returns:
     * Error message on error
     * false if value is not valid
     * true if value is valid
     *
     * @return bool|string
     */
    public static function validatePassword($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_PASSWORD]);
        } else if(preg_match("/\\s+/", $value)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_PASSWORD]);
        } else {
            return true;
        }
    }

    /**
     * Function used to check if provided AuthToken format is valid.
     *
     * @param $value
     *
     * Returns:
     * Error message on error
     * false if value is not valid
     * true if value is valid
     *
     * @return bool|string
     */
    public static function validateAuthToken($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_AUTH_TOKEN]);
        } else if((strlen($value) != 20)
            || (preg_match("/[^".Settings::CHARACTERS_ALPHANUMERIC_LOWERCASE."]+/", $value) != 0)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_USER_AUTH_TOKEN]);
        } else {
            return true;
        }
    }

    /**
     * Function used to check if provided user name format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function validateUserName($value){
        return true;
    }
}