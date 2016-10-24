<?php

namespace Futurologeek\SmartCrossing;

include_once("../config/Settings.php");
include_once("../support/Support.php");
include_once("../support/DatabaseConnection.php");
include_once("../classes/User.php");

class Book
{
    const BOOK_CATEGORY_FICTION                     = "fic";
    const BOOK_CATEGORY_BIOGRAPHIES                 = "bio";
    const BOOK_CATEGORY_BUSINESS_AND_INVESTMENTS    = "bai";
    const BOOK_CATEGORY_COOKING                     = "ckg";
    const BOOK_CATEGORY_HISTORY                     = "his";
    const BOOK_CATEGORY_COMPUTERS                   = "com";
    const BOOK_CATEGORY_CRIME_STORIES               = "cst";
    const BOOK_CATEGORY_KIDS                        = "kds";
    const BOOK_CATEGORY_POLITICS                    = "pls";
    const BOOK_CATEGORY_LAW                         = "law";
    const BOOK_CATEGORY_RELIGION                    = "rel";
    const BOOK_CATEGORY_ROMANCE                     = "rom";
    const BOOK_CATEGORY_SCI_FI                      = "sfi";
    const BOOK_CATEGORY_HEALTH                      = "hlt";

    /** @var int */
    private $bookId;
    /** @var string */
    private $bookTitle;
    /** @var string */
    private $bookAuthor;
    /** @var string */
    private $bookIsbn;
    /** @var string */
    private $bookPublicationDate;
    /**
     * Requires BOOK_CATEGORY_... constant as value.
     * @var string
     */
    private $bookCategory;
    /** @var string */
    private $bookCover;
    /** @var int */
    private $bookUserAuthor;

    /** @var User */
    private $user;
    private $coverFile;


    /**
     * @param User $user
     * @param $coverFile
     */
    public function __construct(&$user, &$coverFile = null)
    {
        $this->user = $user;
        $this->coverFile = $coverFile;
    }

    /** @return int */
    public function getBookId()
    {
        return $this->bookId;
    }

    /** @param int $bookId */
    public function setBookId($bookId)
    {
        $this->bookId = $bookId;
    }

    /** @return string */
    public function getBookTitle()
    {
        return $this->bookTitle;
    }

    /** @param string $bookTitle */
    public function setBookTitle($bookTitle)
    {
        $this->bookTitle = $bookTitle;
    }

    /** @return string */
    public function getBookAuthor()
    {
        return $this->bookAuthor;
    }

    /** @param string $bookAuthor */
    public function setBookAuthor($bookAuthor)
    {
        $this->bookAuthor = $bookAuthor;
    }

    /** @return string */
    public function getBookIsbn()
    {
        return $this->bookIsbn;
    }

    /** @param string $bookIsbn */
    public function setBookIsbn($bookIsbn)
    {
        $this->bookIsbn = $bookIsbn;
    }

    /** @return string */
    public function getBookPublicationDate()
    {
        return $this->bookPublicationDate;
    }

    /** @param string $bookPublicationDate */
    public function setBookPublicationDate($bookPublicationDate)
    {
        $this->bookPublicationDate = $bookPublicationDate;
    }

    /** @return string */
    public function getBookCategory()
    {
        return $this->bookCategory;
    }

    /**
     * Requires BOOK_CATEGORY_... constant as value.
     * @param string $bookCategory
     */
    public function setBookCategory($bookCategory)
    {
        switch ($bookCategory){
            case self::BOOK_CATEGORY_FICTION:
            case self::BOOK_CATEGORY_BIOGRAPHIES:
            case self::BOOK_CATEGORY_BUSINESS_AND_INVESTMENTS:
            case self::BOOK_CATEGORY_COOKING:
            case self::BOOK_CATEGORY_HISTORY:
            case self::BOOK_CATEGORY_COMPUTERS:
            case self::BOOK_CATEGORY_CRIME_STORIES:
            case self::BOOK_CATEGORY_KIDS:
            case self::BOOK_CATEGORY_POLITICS:
            case self::BOOK_CATEGORY_LAW:
            case self::BOOK_CATEGORY_RELIGION:
            case self::BOOK_CATEGORY_ROMANCE:
            case self::BOOK_CATEGORY_SCI_FI:
            case self::BOOK_CATEGORY_HEALTH:
                $this->bookCategory = $bookCategory;
                break;

            default:
                $this->bookCategory = null;
        }
    }

    /** @return string */
    public function getBookCover()
    {
        return $this->bookCover;
    }

    /** @param string $bookCover */
    public function setBookCover($bookCover)
    {
        $this->bookCover = $bookCover;
    }

    /** @return int */
    public function getBookUserAuthor()
    {
        return $this->bookUserAuthor;
    }

    /** @param int $bookUserAuthor */
    public function setBookUserAuthor($bookUserAuthor)
    {
        $this->bookUserAuthor = $bookUserAuthor;
    }


    /**
     * Function used to add book to database.
     *
     * @param bool $returnRaw
     *
     * Returns:
     *
     * If $returnRaw is true:
     * -1 on mysql error
     * -2 on authentication error or invalid input
     * -3 on file upload error
     * array on success
     *
     * If $returnRaw is false:
     * Error message on error
     * Success message on success
     *
     * @return int|array|string
     */
    public function addBook($returnRaw = false){
        if(($temp = self::verifyBookTitle($this->bookTitle)) !== true){ return $temp; }
        if(($temp = self::verifyBookAuthor($this->bookAuthor)) !== true){ return $temp; }
        if(($temp = self::verifyBookIsbn($this->bookIsbn)) !== true){ return $temp; }
        if(($temp = self::verifyBookCategory($this->bookCategory)) !== true){ return $temp; }

        if($this->coverFile != null && !Support::verifyFileValidity($this->coverFile)){
            if($returnRaw){
                return -3;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_UPLOAD_ERROR);
            }
        }

        $auth = $this->user->signAuth(true);
        if($returnRaw){
            if($auth == -1){
                return -1;
            } else if($auth == null){
                return -2;
            }
        } else {
            if($auth == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($auth == null){
                return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_USERS,
            [Settings::KEY_USERS_USER_ID],
            Settings::KEY_USERS_USER_AUTH_TOKEN."=?", "s", [$this->user->getUserAuthToken()]);

        if($returnRaw){
            if($result == -1){
                return -1;
            } else if($result === null || count($result) <= 0) {
                return -2;
            }
        } else {
            if($result == -1){
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            } else if($result === null || count($result) <= 0) {
                return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
            }
        }

        $this->user->setUserId($result[0][0]);
        $result = $mysqli->databaseInsertRow(Settings::DATABASE_TABLE_BOOKS,
            [Settings::KEY_BOOKS_BOOK_TITLE, Settings::KEY_BOOKS_BOOK_AUTHOR, Settings::KEY_BOOKS_BOOK_ISBN,
                Settings::KEY_BOOKS_BOOK_PUBLICATION_DATE, Settings::KEY_BOOKS_BOOK_CATEGORY,
                Settings::KEY_BOOKS_BOOK_USER_AUTHOR], "sssisi", [$this->bookTitle, $this->bookAuthor, $this->bookIsbn,
                $this->bookPublicationDate, $this->bookCategory, $this->user->getUserId()]);

        if($result == -1){
            if($returnRaw){
                return -1;
            } else {
                return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
            }
        } else {
            $this->bookId = $result;
            $data = [[Settings::JSON_KEY_SUCCESS, Settings::SUCCESS_BOOK_ADDED]];

            if($this->coverFile != null){
                $this->bookCover = Support::generateCoverFileName($this->bookId, $this->coverFile);
                if(!move_uploaded_file($this->coverFile['tmp_name'], Settings::COVER_DIRECTORY_PATH.$this->bookCover)){
                    $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKS, Settings::KEY_BOOKS_BOOK_ID."=?", "i",
                        [$this->bookId]);
                    if($returnRaw){
                        return -3;
                    } else {
                        return Settings::buildErrorMessage(Settings::ERROR_UPLOAD_ERROR);
                    }
                }

                $result = $mysqli->databaseUpdate(Settings::DATABASE_TABLE_BOOKS, [Settings::KEY_BOOKS_BOOK_COVER], "s",
                    [$this->bookCover], Settings::KEY_BOOKS_BOOK_ID."=?", "i", [$this->bookId]);

                if($result < 0){
                    unlink(Settings::COVER_DIRECTORY_PATH.$this->bookCover);
                    $mysqli->databaseDeleteRow(Settings::DATABASE_TABLE_BOOKS, Settings::KEY_BOOKS_BOOK_ID."=?", "i",
                        [$this->bookId]);
                    if($returnRaw){
                        return -3;
                    } else {
                        return Settings::buildErrorMessage(Settings::ERROR_UPLOAD_ERROR);
                    }
                }
            }

            $book = $this->getBook(true);
            if($book != null) {
                foreach ($book as $key => $value){
                    $data[] = [$key, $value];
                }
            }

            if($returnRaw){
                return $data;
            } else {
                return Settings::buildSuccessMessage(Settings::SUCCESS_BOOK_ADDED, ...$data);
            }
        }
    }

    /**
     * Function used to check if book is in bookshelf.
     *
     * Returns:
     * -1 on mysql error
     * false if book is not located in bookshelf
     * true if book is located in bookshelf
     *
     * @return bool|int
     */
    public function isInBookshelf(){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();
        $result = $mysqli->databaseExists(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS,
            Settings::KEY_BOOKSHELVES_BOOKS_BOOK_ID."=?", "i", [$this->bookId]);
        $mysqli->databaseClose();
        return $result;
    }

    /**
     * Function used to get book public data (id, title, author, isbn, category, cover,
     * user who has added it to database) using book id
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
    public function getBook($returnRaw = false){
        if($this->bookId > 0){
            $mysqli = new DatabaseConnection();
            $mysqli->databaseConnect();
            $result = $mysqli->databaseFetch(Settings::DATABASE_TABLE_BOOKS,
                [Settings::KEY_BOOKS_BOOK_TITLE, Settings::KEY_BOOKS_BOOK_AUTHOR, Settings::KEY_BOOKS_BOOK_ISBN,
                    Settings::KEY_BOOKS_BOOK_PUBLICATION_DATE, Settings::KEY_BOOKS_BOOK_CATEGORY,
                    Settings::KEY_BOOKS_BOOK_COVER, Settings::KEY_BOOKS_BOOK_USER_AUTHOR],
                Settings::KEY_BOOKS_BOOK_ID."=?", "i", [$this->bookId]);
            $mysqli->databaseClose();

            if($returnRaw){
                if($result === -1 || $result === null){
                    return $result;
                } else {
                    $output = [
                        Settings::JSON_KEY_BOOKS_BOOK_ID => $this->bookId,
                        Settings::JSON_KEY_BOOKS_BOOK_TITLE => $result[0][0],
                        Settings::JSON_KEY_BOOKS_BOOK_AUTHOR => $result[0][1],
                        Settings::JSON_KEY_BOOKS_BOOK_ISBN => $result[0][2],
                        Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE => $result[0][3],
                        Settings::JSON_KEY_BOOKS_BOOK_CATEGORY => $result[0][4],
                        Settings::JSON_KEY_BOOKS_BOOK_COVER => $result[0][5],
                        Settings::JSON_KEY_BOOKS_BOOK_USER_AUTHOR => $result[0][6]
                    ];

                    if($result[0][5]){
                        $output[Settings::JSON_KEY_BOOKS_BOOK_COVER_HTTP] = Settings::COVER_HTTP_PATH.$result[0][5];
                        $output[Settings::JSON_KEY_BOOKS_BOOK_COVER_HTTPS] = Settings::COVER_HTTPS_PATH.$result[0][5];
                    }

                    return $output;
                }
            } else {
                if($result === -1){
                    return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                } else if($result === null){
                    return Settings::buildErrorMessage(Settings::ERROR_BOOK_NOT_EXISTS,
                        [Settings::JSON_KEY_BOOKS_BOOK_ID, $this->bookId]);
                } else {
                    $output = [
                        Settings::JSON_KEY_BOOKS_BOOK_ID => $this->bookId,
                        Settings::JSON_KEY_BOOKS_BOOK_TITLE => $result[0][0],
                        Settings::JSON_KEY_BOOKS_BOOK_AUTHOR => $result[0][1],
                        Settings::JSON_KEY_BOOKS_BOOK_ISBN => $result[0][2],
                        Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE => $result[0][3],
                        Settings::JSON_KEY_BOOKS_BOOK_CATEGORY => $result[0][4],
                        Settings::JSON_KEY_BOOKS_BOOK_COVER => $result[0][5],
                        Settings::JSON_KEY_BOOKS_BOOK_USER_AUTHOR => $result[0][6]
                    ];

                    if($result[0][5]){
                        $output[Settings::JSON_KEY_BOOKS_BOOK_COVER_HTTP] = Settings::COVER_HTTP_PATH.$result[0][5];
                        $output[Settings::JSON_KEY_BOOKS_BOOK_COVER_HTTPS] = Settings::COVER_HTTPS_PATH.$result[0][5];
                    }

                    return json_encode($output);
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
     * Function used to get books stats (borrow count, unique borrow count, return count, unique return count, is in
     * bookshelf (if true bookshelf id is given)). If an error occurred during fetching certain data its value is
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
    public function getBookStats($returnRaw = false){
        $exists = $this->getBook(true);
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
                        [Settings::JSON_KEY_BOOKS_BOOK_ID, $this->bookId]);
                }
            }
        }

        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $borrowedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_BORROWED_BOOKS,
            Settings::KEY_BORROWED_BOOKS_BOOK_ID."=?", "i", [$this->bookId]);

        $borrowedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            " WHERE ".Settings::KEY_BORROWED_BOOKS_BOOK_ID."=?) as a", [null], "i", [$this->bookId]);

        $returnedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_RETURNED_BOOKS,
            Settings::KEY_RETURNED_BOOKS_BOOK_ID."=?", "i", [$this->bookId]);

        $returnedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            " WHERE ".Settings::KEY_RETURNED_BOOKS_BOOK_ID."=?) as a", [null], "i", [$this->bookId]);

        $isInBookshelf = $this->isInBookshelf();

        $bookshelf = new Bookshelf($this->user, $this);
        if($isInBookshelf){
            $bookshelfId = $bookshelf->getBookshelfBook(true);
            if($bookshelfId === -1 || $bookshelfId === -2 || $bookshelfId === null){
                $bookshelf = null;
            } else {
                $bookshelfId = $bookshelfId[Settings::JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID];
            }
        } else {
            $bookshelfId = null;
        }

        $mysqli->databaseClose();

        $output = [Settings::JSON_KEY_BOOK_STATS_BOOK_ID => $this->bookId];
        $output[Settings::JSON_KEY_BOOK_STATS_BORROW_GENERAL_COUNT] = $borrowedUnique !== -1 ? $borrowedGeneral : null;
        $output[Settings::JSON_KEY_BOOK_STATS_BORROW_UNIQUE_COUNT] = $borrowedUnique !== -1 ? $borrowedUnique[0][0] : null;
        $output[Settings::JSON_KEY_BOOK_STATS_RETURN_GENERAL_COUNT] = $returnedGeneral !== -1 ? $returnedGeneral : null;
        $output[Settings::JSON_KEY_BOOK_STATS_RETURN_UNIQUE_COUNT] = $returnedUnique !== -1 ? $returnedUnique[0][0] : null;
        $output[Settings::JSON_KEY_BOOK_STATS_IN_BOOKSHELF] = $isInBookshelf !== -1 ? $isInBookshelf : null;
        $output[Settings::JSON_KEY_BOOK_STATS_BOOKSHELF_ID] = $bookshelfId !== -1 ? $bookshelfId : null;

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
        }
    }

    /**
     * Function used to get global books stats (books count, books in bookshelves count, borrow count, unique borrow
     * count, return count, unique return count). If an error occurred during fetching certain data its value is
     * replaced by null.
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
    public static function getGlobalBookStats($returnRaw = false){
        $mysqli = new DatabaseConnection();
        $mysqli->databaseConnect();

        $booksCount = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKS);
        $inBookshelf = $mysqli->databaseCount(Settings::DATABASE_TABLE_BOOKSHELVES_BOOKS);
        $borrowedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_BORROWED_BOOKS);
        $borrowedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_BORROWED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_BORROWED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_BORROWED_BOOKS.
            ") as a", [null]);
        $returnedGeneral = $mysqli->databaseCount(Settings::DATABASE_TABLE_RETURNED_BOOKS);
        $returnedUnique = $mysqli->databaseRawQuery(
            "SELECT COUNT(*) FROM (SELECT DISTINCT ".Settings::KEY_RETURNED_BOOKS_BOOK_ID. ", ".
            Settings::KEY_RETURNED_BOOKS_USER_ID." FROM ".Settings::DATABASE_TABLE_RETURNED_BOOKS.
            ") as a", [null]);

        $mysqli->databaseClose();

        $output = [];
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_BOOK_COUNT] = $booksCount !== -1 ? $booksCount : null;
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_IN_BOOKSHELVES_COUNT] = $inBookshelf !== -1 ? $inBookshelf : null;
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_BORROW_GENERAL_COUNT] = $borrowedGeneral !== -1 ? $borrowedGeneral : null;
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_BORROW_UNIQUE_COUNT] = $borrowedUnique !== -1 ? $borrowedUnique[0][0] : null;
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_RETURN_GENERAL_COUNT] = $returnedGeneral !== -1 ? $returnedGeneral : null;
        $output[Settings::JSON_KEY_BOOK_STATS_GLOBAL_RETURN_UNIQUE_COUNT] = $returnedUnique !== -1 ? $returnedUnique[0][0] : null;

        if($returnRaw){
            return $output;
        } else {
            return json_encode($output);
        }
    }

    /**
     * Function used to check if provided book title format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function verifyBookTitle($value){
        return true;
    }

    /**
     * Function used to check if provided book author format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function verifyBookAuthor($value){
        return true;
    }

    /**
     * Function used to check if provided isbn format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function verifyBookIsbn($value){
        return true;
    }

    /**
     * Function used to check if provided book category value is valid.
     *
     * @param $value
     *
     * Returns:
     * true if category value is valid
     * false if category value is not valid
     *
     * @return bool
     */
    public static function verifyBookCategory($value){
        switch ($value){
            case self::BOOK_CATEGORY_FICTION:
            case self::BOOK_CATEGORY_BIOGRAPHIES:
            case self::BOOK_CATEGORY_BUSINESS_AND_INVESTMENTS:
            case self::BOOK_CATEGORY_COOKING:
            case self::BOOK_CATEGORY_HISTORY:
            case self::BOOK_CATEGORY_COMPUTERS:
            case self::BOOK_CATEGORY_CRIME_STORIES:
            case self::BOOK_CATEGORY_KIDS:
            case self::BOOK_CATEGORY_POLITICS:
            case self::BOOK_CATEGORY_LAW:
            case self::BOOK_CATEGORY_RELIGION:
            case self::BOOK_CATEGORY_ROMANCE:
            case self::BOOK_CATEGORY_SCI_FI:
            case self::BOOK_CATEGORY_HEALTH:
            case null:
                return true;
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * Function used to check if provided book cover format is valid.
     *
     * @param $value
     *
     * Returns:
     * true
     *
     * @return bool
     */
    public static function verifyBookCover($value){
        return true;
    }
}