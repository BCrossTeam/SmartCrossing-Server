<?php

namespace Futurologeek\SmartCrossing;

include_once("../config/Settings.php");
include_once("../support/Support.php");
include_once("../support/DatabaseConnection.php");
include_once("../classes/User.php");
include_once("../classes/Book.php");

class Bookshelf
{
    /** @var int */
    private $bookshelfId;
    /** @var float */
    private $bookshelfLatitude;
    /** @var float */
    private $bookshelfLongitude;
    /** @var string */
    private $bookshelfName;
    /** @var int */
    private $bookshelfAuthor;

    /** @var User */
    private $user;
    /** @var Book */
    private $book;


    /**
     * @param User $user
     * @param Book $book
     */
    public function __construct(&$user, &$book)
    {
        $this->user = $user;
        $this->book = $book;
    }

    /** @return int */
    public function getBookshelfId()
    {
        return $this->bookshelfId;
    }

    /** @param int $bookshelfId */
    public function setBookshelfId($bookshelfId)
    {
        $this->bookshelfId = $bookshelfId;
    }

    /** @return float */
    public function getBookshelfLatitude()
    {
        return $this->bookshelfLatitude;
    }

    /** @param float $bookshelfLatitude */
    public function setBookshelfLatitude($bookshelfLatitude)
    {
        $this->bookshelfLatitude = $bookshelfLatitude;
    }

    /** @return float */
    public function getBookshelfLongitude()
    {
        return $this->bookshelfLongitude;
    }

    /** @param float $bookshelfLongitude */
    public function setBookshelfLongitude($bookshelfLongitude)
    {
        $this->bookshelfLongitude = $bookshelfLongitude;
    }

    /** @return string */
    public function getBookshelfName()
    {
        return $this->bookshelfName;
    }

    /** @param string $bookshelfName */
    public function setBookshelfName($bookshelfName)
    {
        $this->bookshelfName = $bookshelfName;
    }

    /** @return int */
    public function getBookshelfAuthor()
    {
        return $this->bookshelfAuthor;
    }

    /** @param int $bookshelfAuthor */
    public function setBookshelfAuthor($bookshelfAuthor)
    {
        $this->bookshelfAuthor = $bookshelfAuthor;
    }


    /**
     * Function used to add bookshelf usable by moderators and admins.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function addBookshelf(){
        if(($temp = self::verifyBookshelfCoordinates($this->bookshelfLatitude)) !== true){ return $temp; }
        if(($temp = self::verifyBookshelfCoordinates($this->bookshelfLongitude)) !== true){ return $temp; }
        if(($temp = self::verifyBookshelfName($this->bookshelfName)) !== true){ return $temp; }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_ACCOUNT_TYPE],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        } else {
            $this->user->setUserId($result[0][0]);
            $this->user->setUserAccountType($result[0][1]);

            if($result[0][1] != User::USER_ACCOUNT_TYPE_ADMIN && $result[0][1] != User::USER_ACCOUNT_TYPE_MODERATOR){
                return Settings::buildErrorMessage(Settings::ERROR_PERMISSION_DENIED);
            }

            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELVES,
                [Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE, Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME, Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR],
                "ddsi",
                [$this->bookshelfLatitude, $this->bookshelfLongitude, $this->bookshelfName, $this->user->getUserId()]);
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $this->bookshelfId = $result;
            $data = [];
            $bookshelf = $this->getBookshelf(true);
            if($bookshelf != null && !is_int($bookshelf)) {
                foreach ($bookshelf as $key => $value){
                    $data[] = [$key, $value];
                }
            }
            return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_ADDED, ...$data);
        }
    }

    /**
     * Function used to add book to bookshelf.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function addBookToBookshelf(){
        if($this->book == null || $this->book->getBookId() <= 0){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
        }
        if($this->bookshelfId <= 0){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_ID]);
        }

        $book = $this->book->getBook(true);
        if($book == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKS_BOOK_ID, $this->book->getBookId()]);
        } elseif($book == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($book == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
        }

        $bookshelf = $this->getBookshelf(true);
        if($bookshelf == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
        } elseif($bookshelf == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($bookshelf == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_ID]);
        }

        $inBookshelf = $this->book->isInBookshelf();
        if($inBookshelf < 0){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($inBookshelf == true){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_ALREADY_IN_BOOKSHELF);
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        } else {
            $this->user->setUserId($result[0][0]);

            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                [Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID, Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID,
                    Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER],
                "iii",
                [$this->bookshelfId, $this->book->getBookId(), $this->user->getUserId()]);
            $mysqli->databaseClose();
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $data = [];
            $bookshelfBook = $this->getBookshelfBook(true);
            if($bookshelfBook != null && !is_int($bookshelfBook)) {
                foreach ($bookshelfBook as $key => $value){
                    $data[] = [$key, $value];
                }
            }
            return Settings::buildSuccessMessage(Settings::SUCCESS_BOOK_ADDED_TO_BOOKSHELF, ...$data);
        }
    }

    /**
     * Function used to remove book form bookshelf.
     *
     * Return:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function removeBookFromBookshelf(){
        if($this->book == null || $this->book->getBookId() <= 0){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
        }
        if($this->bookshelfId <= 0){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_ID]);
        }

        $book = $this->book->getBook(true);
        if($book == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKS_BOOK_ID, $this->book->getBookId()]);
        } elseif($book == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($book == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
        }

        $bookshelf = $this->getBookshelf(true);
        if($bookshelf == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
        } elseif($bookshelf == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($bookshelf == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_ID]);
        }

        $inBookshelf = $this->book->isInBookshelf();
        if($inBookshelf < 0){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($inBookshelf == false){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF);
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        } else {
            $this->user->setUserId($result[0][0]);

            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER],
                Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=? AND ".
                Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?",
                "ii", [$this->bookshelfId, $this->user->getUserId()]);

            if($result == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null || count($result) <= 0) {
                return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF);
            }

            if($result[0][0] != $this->user->getUserId()){
                return Settings::buildErrorMessage(Settings::ERROR_PERMISSION_DENIED);
            }

            $result = $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=? AND ".
                Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?",
                "ii", [$this->bookshelfId, $this->book->getBookId()]);
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            return Settings::buildSuccessMessage(Settings::SUCCESS_BOOK_REMOVED_FORM_BOOKSHELF);
        }
    }


    /**
     * Function used to get bookshelf public data (id, latitude, longitude, name, author) using bookshelf id
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if bookshelf not exists
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getBookshelf($returnRaw = false){
        if($this->bookshelfId > 0){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES,
                [Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE, Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME, Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR],
                Settings::KEY_BOOKSHELVES_BOOKSHELF_ID."=?", "i", [$this->bookshelfId]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    return [
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID => $this->bookshelfId,
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE => $result[0][0],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE => $result[0][1],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME => $result[0][2],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_AUTHOR => $result[0][3]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS,
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
                } else {
                    return json_encode([
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID => $this->bookshelfId,
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE => $result[0][0],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE => $result[0][1],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME => $result[0][2],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_AUTHOR => $result[0][3]
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
     * Function used to get book - bookshelf match data (bookshelf id, book id, user who has deposited the book) using
     * bookshelf id and book id or just book id
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if book is not in bookshelf
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getBookshelfBook($returnRaw = false){
        if($this->bookshelfId > 0 && $this->book->getBookId() > 0){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                [Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ADDER],
                Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=? AND ".Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?",
                "ii", [$this->bookshelfId, $this->book->getBookId()]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    return [
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID => $this->bookshelfId,
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID => $this->book->getBookId(),
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER => $result[0][0]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF,
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID, $this->bookshelfId],
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID, $this->book->getBookId()]);
                } else {
                    return json_encode([
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID => $this->bookshelfId,
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID => $this->book->getBookId(),
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER => $result[0][0]
                    ]);
                }
            }
        } elseif($this->bookshelfId <= 0 && $this->book->getBookId()) {
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                [Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID, Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ADDER],
                Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?", "i", [$this->bookshelfId, $this->book->getBookId()]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    return [
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID => $result[0][0],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID => $this->book->getBookId(),
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER => $result[0][1]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF,
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID, $this->book->getBookId()]);
                } else {
                    return json_encode([
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID => $this->bookshelfId,
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID => $this->book->getBookId(),
                        Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER => $result[0][0]
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
     * Function used to check if provided bookshelf coordinates format is valid.
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
    public static function verifyBookshelfCoordinates($value){
        if($value == null){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_COORDINATES]);
        } else if(!is_float($value)){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_COORDINATES]);
        } else {
            return true;
        }
    }

    /**
     * Function used to check if provided bookshelf name format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function verifyBookshelfName($value){
        return true;
    }
}