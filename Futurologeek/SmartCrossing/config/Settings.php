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
    const ERROR_INPUT_EMPTY                     = 1;
    const ERROR_INPUT_INVALID                   = 2;
    const ERROR_MYSQL_CONNECTION                = 3;
    const ERROR_USER_ALREADY_SIGNED_UP          = 4;
    const ERROR_USER_NOT_EXISTS                 = 5;
    const ERROR_SIGN_IN_FAILED                  = 6;
    const ERROR_AUTH_FAILED                     = 7;
    const ERROR_USER_NOT_SIGNED_IN              = 8;
    const ERROR_PERMISSION_DENIED               = 9;
    const ERROR_BOOKSHELF_NOT_EXISTS            = 10;
    const ERROR_BOOK_NOT_EXISTS                 = 11;
    const ERROR_BOOK_ALREADY_IN_BOOKSHELF       = 12;
    const ERROR_BOOK_NOT_IN_BOOKSHELF           = 13;
    const ERROR_CANNOT_BORROW_BOOK              = 14;
    const ERROR_CANNOT_RETURN_BOOK              = 15;

    const SUB_ERROR_USER_EMAIL_ADDRESS          = 1;
    const SUB_ERROR_USER_NAME                   = 2;
    const SUB_ERROR_USER_PASSWORD               = 3;
    const SUB_ERROR_USER_AUTH_TOKEN             = 4;
    const SUB_ERROR_BOOKSHELF_COORDINATES       = 5;
    const SUB_ERROR_BOOKSHELF_ID                = 6;
    const SUB_ERROR_BOOK_ID                     = 7;

    /* Success */
    const SUCCESS_SIGNED_UP                     = 1;
    const SUCCESS_SIGNED_IN                     = 2;
    const SUCCESS_AUTH                          = 3;
    const SUCCESS_SIGNED_OUT                    = 4;
    const SUCCESS_BOOKSHELF_ADDED               = 5;
    const SUCCESS_BOOK_ADDED                    = 6;
    const SUCCESS_BOOK_ADDED_TO_BOOKSHELF       = 7;
    const SUCCESS_BOOK_REMOVED_FORM_BOOKSHELF   = 8;
    const SUCCESS_BORROWED_BOOK                 = 9;
    const SUCCESS_RETURNED_BOOK                 = 10;

    /* Database */
    const DATABASE_TABLE_USERS                  = "users";
    const KEY_USERS_USER_ID                     = "user_id";
    const KEY_USERS_USER_EMAIL                  = "user_email";
    const KEY_USERS_USER_PASSWORD               = "user_password";
    const KEY_USERS_USER_AUTH_TOKEN             = "user_auth_token";
    const KEY_USERS_USER_SIGNED_IN              = "user_signed_in";
    const KEY_USERS_USER_NAME                   = "user_name";
    const KEY_USERS_USER_SCORE                  = "user_score";
    const KEY_USERS_USER_CREATION_DATE          = "user_creation_date";
    const KEY_USERS_USER_ACCOUNT_TYPE           = "user_account_type";

    const DATABASE_TABLE_BOOKSHELVES            = "bookshelves";
    const KEY_BOOKSHELVES_BOOKSHELF_ID          = "bookshelf_id";
    const KEY_BOOKSHELVES_BOOKSHELF_LATITUDE    = "bookshelf_latitude";
    const KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE   = "bookshelf_longitude";
    const KEY_BOOKSHELVES_BOOKSHELF_NAME        = "bookshelf_name";
    const KEY_BOOKSHELVES_BOOKSHELF_AUTHOR      = "bookshelf_author";

    const DATABASE_TABLE_BOOKS                  = "books";
    const KEY_BOOKS_BOOK_ID                     = "book_id";
    const KEY_BOOKS_BOOK_TITLE                  = "book_title";
    const KEY_BOOKS_BOOK_AUTHOR                 = "book_author";
    const KEY_BOOKS_BOOK_ISBN                   = "book_isbn";
    const KEY_BOOKS_BOOK_PUBLICATION_DATE       = "book_publication_date";
    const KEY_BOOKS_BOOK_CATEGORY               = "book_category";
    const KEY_BOOKS_BOOK_COVER                  = "book_cover";
    const KEY_BOOKS_BOOK_USER_AUTHOR            = "book_user_author";

    const DATABASE_TABLE_BOOKSHELVES_BOOKS      = "bookshelves_books";
    const KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID    = "bookshelf_id";
    const KEY_BOOKSHELVES_BOOKS_BOOK_ID         = "book_id";
    const KEY_BOOKSHELVES_BOOKS_BOOK_ADDER      = "book_adder";

    const DATABASE_TABLE_BORROWED_BOOKS         = "borrowed_books";
    const KEY_BORROWED_BOOKS_BORROW_ID          = "borrow_id";
    const KEY_BORROWED_BOOKS_USER_ID            = "user_id";
    const KEY_BORROWED_BOOKS_BOOKSHELF_ID       = "bookshelf_id";
    const KEY_BORROWED_BOOKS_BOOK_ID            = "book_id";
    const KEY_BORROWED_BOOKS_BORROW_TIME        = "borrow_time";

    const DATABASE_TABLE_RETURNED_BOOKS         = "returned_books";
    const KEY_RETURNED_BOOKS_RETURN_ID          = "return_id";
    const KEY_RETURNED_BOOKS_USER_ID            = "user_id";
    const KEY_RETURNED_BOOKS_BOOKSHELF_ID       = "bookshelf_id";
    const KEY_RETURNED_BOOKS_BOOK_ID            = "book_id";
    const KEY_RETURNED_BOOKS_RETURN_TIME        = "return_time";

    /* JSON */
    const JSON_KEY_ERROR                        = "error";
    const JSON_KEY_SUCCESS                      = "success";
    const JSON_KEY_SUB_ERROR                    = "sub_error";
    const JSON_KEY_ERROR_MSG                    = "error_msg";

    const JSON_KEY_USER_STATS_USER_ID                           = "user_id";
    const JSON_KEY_USER_STATS_USER_SCORE                        = "user_score";
    const JSON_KEY_USER_STATS_BOOKS_ADDED_COUNT                 = "user_books_added_count";
    const JSON_KEY_USER_STATS_BORROW_GENERAL_COUNT              = "user_borrow_count";
    const JSON_KEY_USER_STATS_BORROW_UNIQUE_COUNT               = "user_unique_borrow_count";
    const JSON_KEY_USER_STATS_RETURN_GENERAL_COUNT              = "user_return_count";
    const JSON_KEY_USER_STATS_RETURN_UNIQUE_COUNT               = "user_unique_return_count";

    const JSON_KEY_BOOK_STATS_GLOBAL_USERS_COUNT                = "user_global_count";

    const JSON_KEY_BOOK_STATS_BOOK_ID                           = "book_id";
    const JSON_KEY_BOOK_STATS_BORROW_GENERAL_COUNT              = "book_borrow_count";
    const JSON_KEY_BOOK_STATS_BORROW_UNIQUE_COUNT               = "book_unique_borrow_count";
    const JSON_KEY_BOOK_STATS_RETURN_GENERAL_COUNT              = "book_return_count";
    const JSON_KEY_BOOK_STATS_RETURN_UNIQUE_COUNT               = "book_unique_return_count";
    const JSON_KEY_BOOK_STATS_IN_BOOKSHELF                      = "book_in_bookshelf";
    const JSON_KEY_BOOK_STATS_BOOKSHELF_ID                      = "book_bookshelf_id";

    const JSON_KEY_BOOK_STATS_GLOBAL_BOOK_COUNT                 = "book_global_count";
    const JSON_KEY_BOOK_STATS_GLOBAL_IN_BOOKSHELVES_COUNT       = "book_global_in_bookshelves_count";
    const JSON_KEY_BOOK_STATS_GLOBAL_BORROW_GENERAL_COUNT       = "book_global_borrow_count";
    const JSON_KEY_BOOK_STATS_GLOBAL_BORROW_UNIQUE_COUNT        = "book_global_unique_borrow_count";
    const JSON_KEY_BOOK_STATS_GLOBAL_RETURN_GENERAL_COUNT       = "book_global_return_count";
    const JSON_KEY_BOOK_STATS_GLOBAL_RETURN_UNIQUE_COUNT        = "book_global_unique_return_count";

    const JSON_KEY_BOOKSHELF_STATS_BOOKSHELF_ID                 = "bookshelf_id";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS                        = "bookshelf_books";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS_COUNT                  = "bookshelf_books_count";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS_BORROW_GENERAL_COUNT   = "bookshelf_books_borrow_general_count";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS_BORROW_UNIQUE_COUNT    = "bookshelf_books_borrow_unique_count";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS_RETURN_GENERAL_COUNT   = "bookshelf_books_return_general_count";
    const JSON_KEY_BOOKSHELF_STATS_BOOKS_RETURN_UNIQUE_COUNT    = "bookshelf_books_return_unique_count";

    const JSON_KEY_BOOKSHELVES_STATS_GLOBAL_BOOKSHELF_COUNT     = "bookshelf_global_count";

    const JSON_KEY_BOOKSHELF_LIST                               = "bookshelves";
    const JSON_KEY_BOOKS_LIST                                   = "books";

    const JSON_KEY_USERS_USER_ID                    = self::KEY_USERS_USER_ID;
    const JSON_KEY_USERS_USER_EMAIL                 = self::KEY_USERS_USER_EMAIL;
    const JSON_KEY_USERS_USER_PASSWORD              = self::KEY_USERS_USER_PASSWORD;
    const JSON_KEY_USERS_USER_AUTH_TOKEN            = self::KEY_USERS_USER_AUTH_TOKEN;
    const JSON_KEY_USERS_USER_SIGNED_IN             = self::KEY_USERS_USER_SIGNED_IN;
    const JSON_KEY_USERS_USER_NAME                  = self::KEY_USERS_USER_NAME;
    const JSON_KEY_USERS_USER_SCORE                 = self::KEY_USERS_USER_SCORE;
    const JSON_KEY_USERS_USER_CREATION_DATE         = self::KEY_USERS_USER_CREATION_DATE;
    const JSON_KEY_USERS_USER_ACCOUNT_TYPE          = self::KEY_USERS_USER_ACCOUNT_TYPE;

    const JSON_KEY_BOOKSHELVES_BOOKSHELF_ID         = self::KEY_BOOKSHELVES_BOOKSHELF_ID;
    const JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE   = self::KEY_BOOKSHELVES_BOOKSHELF_LATITUDE;
    const JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE  = self::KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE;
    const JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME       = self::KEY_BOOKSHELVES_BOOKSHELF_NAME;
    const JSON_KEY_BOOKSHELVES_BOOKSHELF_AUTHOR     = self::KEY_BOOKSHELVES_BOOKSHELF_AUTHOR;

    const JSON_KEY_BOOKS_BOOK_ID                    = self::KEY_BOOKS_BOOK_ID;
    const JSON_KEY_BOOKS_BOOK_TITLE                 = self::KEY_BOOKS_BOOK_TITLE;
    const JSON_KEY_BOOKS_BOOK_AUTHOR                = self::KEY_BOOKS_BOOK_AUTHOR;
    const JSON_KEY_BOOKS_BOOK_ISBN                  = self::KEY_BOOKS_BOOK_ISBN;
    const JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE      = self::KEY_BOOKS_BOOK_PUBLICATION_DATE;
    const JSON_KEY_BOOKS_BOOK_CATEGORY              = self::KEY_BOOKS_BOOK_CATEGORY;
    const JSON_KEY_BOOKS_BOOK_COVER                 = self::KEY_BOOKS_BOOK_COVER;
    const JSON_KEY_BOOKS_BOOK_USER_AUTHOR           = self::KEY_BOOKS_BOOK_USER_AUTHOR;

    const JSON_KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID   = self::KEY_BOOKSHELVES_BOOKS_BOOKSHELF_ID;
    const JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ID        = self::KEY_BOOKSHELVES_BOOKS_BOOK_ID;
    const JSON_KEY_BOOKSHELVES_BOOKS_BOOK_ADDER     = self::KEY_BOOKSHELVES_BOOKS_BOOK_ADDER;

    const JSON_KEY_BORROWED_BOOKS_BORROW_ID         = self::KEY_BORROWED_BOOKS_BORROW_ID;
    const JSON_KEY_BORROWED_BOOKS_USER_ID           = self::KEY_BORROWED_BOOKS_USER_ID;
    const JSON_KEY_BORROWED_BOOKS_BOOKSHELF_ID      = self::KEY_BORROWED_BOOKS_BOOKSHELF_ID;
    const JSON_KEY_BORROWED_BOOKS_BOOK_ID           = self::KEY_BORROWED_BOOKS_BOOK_ID;
    const JSON_KEY_BORROWED_BOOKS_BORROW_TIME       = self::KEY_BORROWED_BOOKS_BORROW_TIME;

    const JSON_KEY_RETURNED_BOOKS_RETURN_ID         = self::KEY_RETURNED_BOOKS_RETURN_ID;
    const JSON_KEY_RETURNED_BOOKS_USER_ID           = self::KEY_RETURNED_BOOKS_USER_ID;
    const JSON_KEY_RETURNED_BOOKS_BOOKSHELF_ID      = self::KEY_RETURNED_BOOKS_BOOKSHELF_ID;
    const JSON_KEY_RETURNED_BOOKS_BOOK_ID           = self::KEY_RETURNED_BOOKS_BOOK_ID;
    const JSON_KEY_RETURNED_BOOKS_RETURN_TIME       = self::KEY_RETURNED_BOOKS_RETURN_TIME;

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