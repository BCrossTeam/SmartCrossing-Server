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
        }

        $this->user->setUserId($result[0][0]);
        $this->user->setUserAccountType($result[0][1]);

        $hasAddPermission = $result[0][1] == User::USER_ACCOUNT_TYPE_ADMIN || $result[0][1] == User::USER_ACCOUNT_TYPE_MODERATOR;
        if($hasAddPermission){
            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELVES,
                [Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE, Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME, Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR],
                "ddsi",
                [$this->bookshelfLatitude, $this->bookshelfLongitude, $this->bookshelfName, $this->user->getUserId()]);
        } else {
            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME],
                "ddsis",
                [$this->bookshelfLatitude, $this->bookshelfLongitude, $this->bookshelfName, $this->user->getUserId(),
                    date_create(null, new \DateTimeZone("UTC"))->modify(Settings::BOOKSHELF_REQUEST_VOTE_TIME)
                        ->format('Y-m-d H:i:s')]);
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $this->bookshelfId = $result;
            if($hasAddPermission){
                $data = [];
                $bookshelf = $this->getBookshelf(true);
                if($bookshelf != null && !is_int($bookshelf)) {
                    foreach ($bookshelf as $key => $value){
                        $data[] = [$key, $value];
                    }
                }
                return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_ADDED, ...$data);
            } else {
                $data = [];
                $bookshelf = $this->getBookshelfRequest(true);
                if($bookshelf != null && !is_int($bookshelf)) {
                    foreach ($bookshelf as $key => $value){
                        $data[] = [$key, $value];
                    }
                }
                return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_REQUEST_ADDED, ...$data);
            }

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
                "ii", [$this->bookshelfId, $this->book->getBookId()]);

            if($result == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null || count($result) <= 0) {
                return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF);
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
     * Function used to determine whether user can borrow book or cannot.
     * User need to have at least one book returned to selected bookshelf to be able to borrow book form it.
     *
     * Returns:
     * -2 on input error or auth error
     * -1 on mysql error
     * false if user is not allowed to borrow a book
     * true if user is allowed to borrow a book
     *
     * @return bool|int
     */
    public function canBorrowBook(){
        if($this->book == null || $this->book->getBookId() <= 0){ return -2; }
        if($this->bookshelfId <= 0){ return -2; }

        $book = $this->book->getBook(true);
        if($book == null || $book == -2){
            return -2;
        } elseif($book == -1){
            return -1;
        }

        $bookshelf = $this->getBookshelf(true);
        if($bookshelf == null || $bookshelf == -2){
            return -2;
        } elseif($bookshelf == -1){
            return -1;
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return -1;
        } else if($result === null || count($result) <= 0) {
            return -2;
        } else {
            $this->user->setUserId($result[0][0]);
            return true;
        }
    }

    /**
     * Function used to determine whether user can return book or cannot.
     * Book must not be in any bookshelf and user have to be last borrower (if book has never been borrowed this
     * condition is ignored).
     *
     * Returns:
     * -2 on input error or auth error
     * -1 on mysql error
     * false if user is not allowed to return a book
     * true if user is allowed to return a book
     *
     * @return bool|int
     */
    public function canReturnBook(){
        if($this->book == null || $this->book->getBookId() <= 0){ return -2; }
        if($this->bookshelfId <= 0){ return -2; }

        $book = $this->book->getBook(true);
        if($book == null || $book == -2){
            return -2;
        } elseif($book == -1){
            return -1;
        }

        $bookshelf = $this->getBookshelf(true);
        if($bookshelf == null || $bookshelf == -2){
            return -2;
        } elseif($bookshelf == -1){
            return -1;
        }



        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return -1;
        } else if($result === null || count($result) <= 0) {
            return -2;
        }

        $this->user->setUserId($result[0][0]);

        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BORROWED_BOOKS,
            [Settings::KEY_BORROWED_BOOKS_USER_ID],
            Settings::KEY_BORROWED_BOOKS_BOOKSHELF_ID."=? AND ".Settings::KEY_BORROWED_BOOKS_BOOK_ID."=?",
            "ii", [$this->bookshelfId, $this->book->getBookId()],
            Settings::KEY_BORROWED_BOOKS_BORROW_ID, true, 1);

        if($result === -1){
            return -1;
        } else if($result === null || count($result) <= 0) {
            return true;
        } else {
            return $result[0][0] == $this->user->getUserId();
        }
    }

    /**
     * Function used to borrow a book form bookshelf.
     * If succeeded book is removed from bookshelf and user borrowing book is logged.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function borrowBook(){
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

        $auth = $this->user->signAuth(true);
        if($auth == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($auth == null){
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        }

        $inBookshelf = $this->book->isInBookshelf();
        if($inBookshelf < 0){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($inBookshelf === false){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF);
        }

        $bookshelfBook = $this->getBookshelfBook(true);
        if($bookshelfBook === null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_IN_BOOKSHELF,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID, $this->book->getBookId()]);
        } elseif($bookshelfBook === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($bookshelfBook === -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID);
        }

        $canBorrow = $this->canBorrowBook();
        if($canBorrow == false) {
            return Settings::buildErrorMessage(Settings::ERROR_CANNOT_BORROW_BOOK,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
        } elseif($canBorrow === -1) {
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($canBorrow === -2) {
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
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

            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BORROWED_BOOKS,
                [Settings::KEY_BORROWED_BOOKS_BOOKSHELF_ID, Settings::KEY_BORROWED_BOOKS_BOOK_ID,
                    Settings::KEY_BORROWED_BOOKS_USER_ID, Settings::KEY_BORROWED_BOOKS_BORROW_TIME],
                "iiis",
                [$this->bookshelfId, $this->book->getBookId(), $this->user->getUserId(),
                    date_create(null, new \DateTimeZone("UTC"))->format('Y-m-d H:i:s')]);
            $mysqli->databaseClose();
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $this->removeBookFromBookshelf();
            return Settings::buildSuccessMessage(Settings::SUCCESS_BORROWED_BOOK,
                [Settings::JSON_KEY_BORROWED_BOOKS_BOOK_ID, $this->book->getBookId()]);
        }
    }

    /**
     * Function used to borrow a book form bookshelf.
     * If succeeded book is added back to bookshelf and returning user is logged.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function returnBook(){
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

        $auth = $this->user->signAuth(true);
        if($auth == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($auth == null){
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        }

        $isInBookshelf = $this->book->isInBookshelf();
        if($isInBookshelf === true){
            return Settings::buildErrorMessage(Settings::ERROR_BOOK_ALREADY_IN_BOOKSHELF,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID, $this->book->getBookId()]);
        } elseif($isInBookshelf === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        }

        $canBorrow = $this->canReturnBook();
        if($canBorrow == false) {
            return Settings::buildErrorMessage(Settings::ERROR_CANNOT_RETURN_BOOK,
                [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
        } elseif($canBorrow === -1) {
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($canBorrow === -2) {
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOK_ID]);
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

            $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_RETURNED_BOOKS,
                [Settings::KEY_RETURNED_BOOKS_BOOKSHELF_ID, Settings::KEY_RETURNED_BOOKS_BOOK_ID,
                    Settings::KEY_RETURNED_BOOKS_USER_ID, Settings::KEY_RETURNED_BOOKS_RETURN_TIME],
                "iiis",
                [$this->bookshelfId, $this->book->getBookId(), $this->user->getUserId(),
                    date_create(null, new \DateTimeZone("UTC"))->format('Y-m-d H:i:s')]);
            $mysqli->databaseClose();
        }

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            $this->addBookToBookshelf();
            return Settings::buildSuccessMessage(Settings::SUCCESS_RETURNED_BOOK,
                [Settings::JSON_KEY_RETURNED_BOOKS_BOOK_ID, $this->book->getBookId()]);
        }
    }

    /**
     * Function used to evaluate bookshelf requests. If request has expired it is eveluated.
     * If request has not achieved required voters threshold it is ignored and waits for further admin evaluation.
     * In other case it is evaluated. If required approval vote threshold was achieved request is transformed into
     * bookshelf otherwise it is removed from database.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function evaluateBookshelfRequests(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
            [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID],
            Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME." < NOW()");

        if($result === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_REQUESTS_EVALUATED);
        }

        foreach ($result as $item){
            $votesTotal = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELF_REQUEST_VOTES,
                Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_ID."=?",
                "i", [$item[0]]);

            $votesApproved = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELF_REQUEST_VOTES,
                Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_ID."=?".
                " AND ".Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_APPROVED."=?",
                "ii", [$item[0], 1]);

            if($votesTotal !== -1 && $votesApproved !== -1){
                if($votesTotal >= Settings::BOOKSHELF_REQUEST_VOTE_THRESHOLD){
                    if($votesApproved/$votesTotal >= Settings::BOOKSHELF_REQUEST_APPROVAL_THRESHOLD){
                        $this->bookshelfId = $item[0];
                        $bookshelfRequest = $this->getBookshelfRequest(true);

                        if($bookshelfRequest !== null && $bookshelfRequest !== -1 && $bookshelfRequest !== -2){
                            $succeeded = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELVES,
                                [Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE,
                                    Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE,
                                    Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME,
                                    Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR,
                                ],
                                "ddss",
                                [$bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE],
                                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE],
                                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME],
                                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR]
                                ]
                                );

                            if($succeeded !== -1 && $succeeded > 0){
                                $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID."=?",
                                    "i", [$item[0]]);
                            }
                        }
                    } else {
                        $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                            Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID."=?",
                            "i", [$item[0]]);
                    }
                }
            }
        }
        return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_REQUESTS_EVALUATED);
    }

    /**
     * Function used to accept bookshelf requests. May be used only by admins and moderators.
     * It basically transforms request into new bookshelf.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function acceptBookshelfRequest(){
        $bookshelfRequest = $this->getBookshelfRequest(true);

        if($bookshelfRequest == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_REQUEST_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID, $this->bookshelfId]);
        } elseif($bookshelfRequest == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($bookshelfRequest == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_REQUEST_ID]);
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_ACCOUNT_TYPE],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        }

        $this->user->setUserId($result[0][0]);
        $this->user->setUserAccountType($result[0][1]);

        if($result[0][1] == User::USER_ACCOUNT_TYPE_ADMIN || $result[0][1] == User::USER_ACCOUNT_TYPE_MODERATOR){
            $succeeded = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELVES,
                [Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR,
                ],
                "ddss",
                [$bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE],
                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE],
                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME],
                    $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR]
                ]
            );

            if($succeeded !== -1){
                $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID."=?",
                    "i", [$this->bookshelfId]);
                return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_REQUEST_ACCEPTED);
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            return Settings::buildErrorMessage(Settings::ERROR_PERMISSION_DENIED);
        }
    }

    /**
     * Function used to reject bookshelf requests. May be used only by admins and moderators.
     * It basically removes bookshelf request.
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function rejectBookshelfRequest(){
        $bookshelfRequest = $this->getBookshelfRequest(true);

        if($bookshelfRequest == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_REQUEST_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID, $this->bookshelfId]);
        } elseif($bookshelfRequest == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($bookshelfRequest == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_REQUEST_ID]);
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID, Settings::KEY_USERS_USER_ACCOUNT_TYPE],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        }

        $this->user->setUserId($result[0][0]);
        $this->user->setUserAccountType($result[0][1]);

        if($result[0][1] == User::USER_ACCOUNT_TYPE_ADMIN || $result[0][1] == User::USER_ACCOUNT_TYPE_MODERATOR){
            $succeeded = $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID."=?",
                "i", [$this->bookshelfId]);

            if($succeeded !== -1){
                return Settings::buildSuccessMessage(Settings::SUCCESS_BOOKSHELF_REQUEST_REJECTED);
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            return Settings::buildErrorMessage(Settings::ERROR_PERMISSION_DENIED);
        }
    }

    /**
     * Function used to vote on bookshelf request. Bookshelf request vote must not be closed.
     * It is not possible to vote twice.
     *
     * @param $approved
     *
     * Returns:
     * Error message on error
     * Success message on success
     *
     * @return string
     */
    public function voteOnBookshelfRequest($approved){
        $bookshelfRequest = $this->getBookshelfRequest(true);

        if($bookshelfRequest == null){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_REQUEST_NOT_EXISTS,
                [Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID, $this->bookshelfId]);
        } elseif($bookshelfRequest == -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } elseif($bookshelfRequest == -2){
            return Settings::buildErrorMessage(Settings::ERROR_INPUT_INVALID,
                [Settings::JSON_KEY_SUB_ERROR, Settings::SUB_ERROR_BOOKSHELF_REQUEST_ID]);
        }

        if(date_create(
            $bookshelfRequest[Settings::JSON_KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME],
                new \DateTimeZone("UTC")) <= date_create(null, new \DateTimeZone("UTC"))){
            return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_REQUEST_VOTE_CLOSED);
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($result === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === null || count($result) <= 0) {
            return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
        }

        $this->user->setUserId($result[0][0]);

        $result = $mysqli->databaseExists(Settings::DATABASE_TABLE_BOOKSHELF_REQUEST_VOTES,
            Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_ID.
            "=? AND ".Settings::KEY_BOOKSHELF_REQUEST_VOTES_USER_ID."=?",
            "ii",
            [$this->bookshelfId, $this->user->getUserId()]);

        if($result === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else if($result === true) {
            return Settings::buildErrorMessage(Settings::ERROR_USER_ALREADY_VOTED);
        }

        $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKSHELF_REQUEST_VOTES,
            [Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_ID, Settings::KEY_BOOKSHELF_REQUEST_VOTES_USER_ID,
                Settings::KEY_BOOKSHELF_REQUEST_VOTES_BOOKSHELF_REQUEST_APPROVED],
            "iii",
            [$this->bookshelfId, $this->user->getUserId(), $approved]);
        $mysqli->databaseClose();

        if($result === -1){
            return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
        } else {
            return Settings::buildSuccessMessage(Settings::SUCCESS_VOTED);
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
     * Function used to get bookshelf request public data (id, latitude, longitude, name, author, closing time)
     * using bookshelf request id
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if bookshelf request not exists
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getBookshelfRequest($returnRaw = false){
        if($this->bookshelfId > 0){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME],
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID."=?", "i", [$this->bookshelfId]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    return [
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $this->bookshelfId,
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $result[0][0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $result[0][1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $result[0][2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $result[0][3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $result[0][4]
                    ];
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_REQUEST_NOT_EXISTS,
                        [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID, $this->bookshelfId]);
                } else {
                    return json_encode([
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $this->bookshelfId,
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $result[0][0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $result[0][1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $result[0][2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $result[0][3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $result[0][4]
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
                Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?", "i", [$this->book->getBookId()]);
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
     * Function used to get list of books present in bookshelf.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -2 on invalid input
     * -1 on mysql error
     * null if no books are present or bookshelf not exists
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public function getBooksInBookshelf($returnRaw = false){
        $exists = $this->getBookshelf(true);
        if($exists === null || $exists === -1 || $exists === -2){
            if($returnRaw){
                return $exists;
            } else {
                if($exists === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } elseif($exists === -2){
                    return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_EXISTS,
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseRawQuery(
            "SELECT ".Settings::KEY_BOOKS_BOOK_ID.", ".Settings::KEY_BOOKS_BOOK_TITLE.
            ", ".Settings::KEY_BOOKS_BOOK_AUTHOR." FROM ".Settings::DATABASE_TABLE_BOOKS." WHERE ".
            Settings::KEY_BOOKS_BOOK_ID." IN (SELECT ".Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID." FROM ".
            Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS." WHERE ".Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=?)",
            [Settings::JSON_KEY_BOOKS_BOOK_ID, Settings::JSON_KEY_BOOKS_BOOK_TITLE,
                Settings::JSON_KEY_BOOKS_BOOK_AUTHOR], "i", [$this->bookshelfId]);
        $mysqli->databaseClose();

        if($returnRaw){
            if($result === -1 || $result === null){
                return $result;
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_STATS_BOOKSHELF_ID => $this->bookshelfId, Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS][] = [
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
                return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS,
                    [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_STATS_BOOKSHELF_ID => $this->bookshelfId, Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS][] = [
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
     * Function used to get bookshelf stats (book count, borrow count, unique borrow count, return count, unique return
     * count). If an error occurred during fetching certain data its value is replaced by null.
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
    public function getBookshelfStats($returnRaw = false){
        $exists = $this->getBookshelf(true);
        if($exists === null || $exists === -1 || $exists === -2){
            if($returnRaw){
                return $exists;
            } else {
                if($exists === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } elseif($exists === -2){
                    return Settings::buildErrorMessage(Settings::ERROR_INPUT_EMPTY);
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS,
                        [Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID, $this->bookshelfId]);
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $booksInBookshelf = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
            Settings::KEY_BOOKSHELVES_BOOKSHELF_ID."=?", "i", [$this->bookshelfId]);

        $borrowedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_BORROWED_BOOKS,
            Settings::KEY_BORROWED_BOOKS_BOOKSHELF_ID."=?", "i", [$this->bookshelfId]);

        $borrowedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            " WHERE ".Settings::KEY_BORROWED_BOOKS_BOOKSHELF_ID."=?) as a", [null], "i", [$this->bookshelfId]);

        $returnedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_RETURNED_BOOKS,
            Settings::KEY_RETURNED_BOOKS_BOOKSHELF_ID."=?", "i", [$this->bookshelfId]);

        $returnedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            " WHERE ".Settings::KEY_RETURNED_BOOKS_BOOKSHELF_ID."=?) as a", [null], "i", [$this->bookshelfId]);

        $mysqli->databaseClose();

        $output = [Settings::JSON_KEY_BOOKSHELF_STATS_BOOKSHELF_ID => $this->bookshelfId];
        $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS_COUNT] = $booksInBookshelf !== -1 ? $booksInBookshelf : null;
        $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS_BORROW_GENERAL_COUNT] = $borrowedGeneral !== -1 ? $borrowedGeneral : null;
        $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS_BORROW_UNIQUE_COUNT] = $borrowedUnique !== -1 ? $borrowedUnique[0][0] : null;
        $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS_RETURN_GENERAL_COUNT] = $returnedGeneral !== -1 ? $returnedGeneral : null;
        $output[Settings::JSON_KEY_BOOKSHELF_STATS_BOOKS_RETURN_UNIQUE_COUNT] = $returnedUnique !== -1 ? $returnedUnique[0][0] : null;

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
        }
    }

    /**
     * Function used to get global bookshelf stats (bookshelf count). If an error occurred during fetching certain data
     * its value is replaced by null.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function getGlobalBookshelfStats($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $result = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELVES);

        $mysqli->databaseClose();

        if($result === -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            $output = [];
            $output[Settings::JSON_KEY_BOOKSHELVES_STATS_GLOBAL_BOOKSHELF_COUNT] = $result !== null ? $result : null;
            if($returnRaw){
                return $output;
            } else {
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to get bookshelf list containing bookshelf id, latitude, longitude, name and list of books.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null if no bookshelves has been fetched
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function getBookshelfList($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES, 
            [Settings::KEY_BOOKSHELVES_BOOKSHELF_ID, Settings::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE,
                Settings::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE, Settings::KEY_BOOKSHELVES_BOOKSHELF_NAME]);

        if($returnRaw){
            if($result === -1 || $result === null){
                $mysqli->databaseClose();
                return $result;
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_LIST => []];
                foreach ($result as $item) {
                    $temp_books = [];
                    $books = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                        [Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID], Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=?", "i", [$item[0]]);
                    if($books !== -1 && $books !== null){
                        foreach ($books as $book){
                            $temp_books[] = [Settings::JSON_KEY_BOOKS_BOOK_ID => $book[0]];
                        }
                    }

                    $output[Settings::JSON_KEY_BOOKSHELF_LIST][] = [
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID => $item[0],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE => $item[1],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME => $item[3],
                        Settings::JSON_KEY_BOOKS_LIST => $temp_books
                    ];
                }
                return $output;
            }
        } else {
            if($result === -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null){
                return Settings::buildErrorMessage(Settings::ERROR_BOOKSHELF_NOT_EXISTS);
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_LIST => []];
                foreach ($result as $item) {
                    $temp_books = [];
                    $books = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
                        [Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID], Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID."=?", "i", [$item[0]]);
                    if($books !== -1 && $books !== null){
                        foreach ($books as $book){
                            $temp_books[] = [Settings::JSON_KEY_BOOKS_BOOK_ID => $book[0]];
                        }
                    }

                    $output[Settings::JSON_KEY_BOOKSHELF_LIST][] = [
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_ID => $item[0],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE => $item[1],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME => $item[3],
                        Settings::JSON_KEY_BOOKS_LIST => $temp_books
                    ];
                }
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to get bookshelf request list containing bookshelf request id, latitude, longitude, name and vote
     * closing time. If token is provided list is filtered to show only requests on which user has not voted yet.
     *
     * @param bool $returnRaw
     * @param string $token
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * null on auth error (if token was given)
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function getBookshelfRequestList($returnRaw = false, $token = null){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        if($token == null){
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
                [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME], Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME." > NOW()");
        } else {
            $user = new User();
            $user->setUserAuthToken($token);
            $auth = $user->signAuth(true);
            if($auth === -1){
                if($returnRaw){
                    return -1;
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                }
            } else if(!isset($auth[Settings::JSON_KEY_SUCCESS])){
                if($returnRaw){
                    return null;
                } else {
                    return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
                }
            }

            $result = $mysqli->databaseRawQuery("SELECT ".implode(", ",[Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME])." FROM "
                .Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS." WHERE ".
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME." > NOW() AND ".
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID." NOT IN (SELECT ".
                Settings::JSON_KEY_BOOKSHELF_REQUESTS_VOTES_BOOKSHELF_REQUEST_ID." FROM ".
                Settings::DATABASE_TABLE_BOOKSHELF_REQUEST_VOTES." WHERE ".
                Settings::KEY_BOOKSHELF_REQUEST_VOTES_USER_ID."=?)",
                [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                    Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME], "i",
                [$auth[Settings::JSON_KEY_USERS_USER_ID]]);
        }

        if($returnRaw){
            if($result === -1){
                $mysqli->databaseClose();
                return $result;
            } else if($result == null){
                return [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST][] = [
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $item[0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $item[1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $item[3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $item[4],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $item[5]
                    ];
                }
                return $output;
            }
        } else {
            if($result === -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null){
                return [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST][] = [
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $item[0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $item[1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $item[3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $item[4],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $item[5]
                    ];
                }
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to get bookshelf request list containing bookshelf request id, latitude, longitude, name and vote
     * closing time.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function getBookshelfRequestListAdmin($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKSHELF_REQUESTS,
            [Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR,
                Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME], Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME." < NOW()");

        if($returnRaw){
            if($result === -1){
                $mysqli->databaseClose();
                return $result;
            } else if($result == null){
                return [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST][] = [
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $item[0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $item[1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $item[3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $item[4],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $item[5]
                    ];
                }
                return $output;
            }
        } else {
            if($result === -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result == null){
                return json_encode([Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []]);
            } else {
                $output = [Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST => []];
                foreach ($result as $item) {
                    $output[Settings::JSON_KEY_BOOKSHELF_REQUEST_LIST][] = [
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_ID => $item[0],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LATITUDE => $item[1],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_LONGITUDE => $item[2],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_NAME => $item[3],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_AUTHOR => $item[4],
                        Settings::KEY_BOOKSHELF_REQUESTS_BOOKSHELF_REQUEST_CLOSING_TIME => $item[5]
                    ];
                }
                return json_encode($output);
            }
        }
    }

    /**
     * Function used to search for books in bookshelves starting from $like. If $bookshelves is null, empty or is not
     * array no filtering is used in other case function searches for books only in selected bookshelves.
     *
     * @param string $like
     * @param array $bookshelves
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return array|int|null|string
     */
    public static function searchBooksInBookshelves($like, $bookshelves = null, $returnRaw = false){
        $mysqli = new DatabaseConnection();
        $query = "SELECT ".implode(", ", [Settings::KEY_BOOKS_BOOK_ID, Settings::KEY_BOOKS_BOOK_TITLE])." FROM ".
            Settings::DATABASE_TABLE_BOOKS." WHERE ".Settings::KEY_BOOKS_BOOK_TITLE." LIKE ? AND ".
            Settings::KEY_BOOKS_BOOK_ID." IN (SELECT ".Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID." FROM ".
            Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS;
        $whereTypes = "s";
        $whereVariables = [$like."%"];

        if($bookshelves !== null && is_array($bookshelves) && count($bookshelves) > 0){
            $query .= " WHERE " .Settings::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID.
                " IN (".implode(",", array_fill(0, count($bookshelves), "?")).")";
            $whereTypes .= implode("", array_fill(0, count($bookshelves), "i"));
            $whereVariables = array_merge($whereVariables, $bookshelves);
        }
        $query .= ")";


        $result = $mysqli->databaseRawQuery($query, [Settings::KEY_BOOKS_BOOK_ID, Settings::KEY_BOOKS_BOOK_TITLE],
            $whereTypes, $whereVariables);

        $output = null;
        if($result === -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else if($result == null){
            $output = [Settings::JSON_KEY_BOOKS_LIST => []];
        } else {
            $books = [];
            foreach($result as $item){
                $books[] = [Settings::JSON_KEY_BOOKS_BOOK_ID => $item[0],
                    Settings::JSON_KEY_BOOKS_BOOK_TITLE => $item[1]];
            }
            $output = [Settings::JSON_KEY_BOOKS_LIST => $books];
        }

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
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