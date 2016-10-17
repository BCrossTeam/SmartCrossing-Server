<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include_once("../classes/User.php");
include_once("../classes/Book.php");
include_once("../classes/Bookshelf.php");
include_once("../config/Settings.php");

use Futurologeek\SmartCrossing\User as User;
use Futurologeek\SmartCrossing\Book as Book;
use Futurologeek\SmartCrossing\Bookshelf as Bookshelf;
use Futurologeek\SmartCrossing\Settings as Settings;

$json = null;
if(file_get_contents("php://input") != null){
    $json = json_decode(file_get_contents("php://input"), true);
} else if(isset($_POST["json"]) && $_POST["json"] != null){
    $json = json_decode($_POST["json"], true);
}

if(isset($_GET["class"])){
    switch ($_GET["class"]) {
        case "user":
            echo handleUser($json);
            break;

        case "bookshelf":
            echo handleBookshelf($json);
            break;

        case "book":
            echo handleBook($json);
            break;

        default:
            echo "Invalid class";
            break;
    }
} else {
    echo "No action";
}

function handleUser($jsonData){
    $user = new User();

    switch ($_GET["action"]){
        case "sign":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "POST":
                    if(isset($jsonData) && $jsonData !== null){
                        $user->setUserEmail(isset($jsonData[Settings::JSON_KEY_USERS_USER_EMAIL])
                            ? $jsonData[Settings::JSON_KEY_USERS_USER_EMAIL] : null);

                        $user->setUserPassword(isset($jsonData[Settings::JSON_KEY_USERS_USER_PASSWORD])
                            ? $jsonData[Settings::JSON_KEY_USERS_USER_PASSWORD] : null);

                        return $user->signIn();
                    } else {
                        return "No data supplied to server";
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        case "auth":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if(isset($_GET["token"])){
                        $user->setUserAuthToken($_GET["token"]);
                        return $user->signAuth();
                    } else {
                        return "No data supplied to server";
                    }
                    break;

                case "DELETE":
                    if(isset($_GET["token"])){
                        $user->setUserAuthToken($_GET["token"]);
                        return $user->signOut();
                    } else {
                        return "No data supplied to server";
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        case "stats":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $user->setUserId($_GET["id"]);
                        return $user->getUserStats();
                    } else {
                        return User::getGlobalUserStats();
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        default:
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $user->setUserId($_GET["id"]);
                        return $user->getUser();
                    } else {
                        return "No user selected";
                    }
                    break;

                case "POST":
                    if (isset($_GET["id"])) {
                        return "Invalid method";
                    } else {
                        if(isset($jsonData) && $jsonData !== null){
                            $user->setUserEmail(isset($jsonData[Settings::JSON_KEY_USERS_USER_EMAIL])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_EMAIL] : null);

                            $user->setUserName(isset($jsonData[Settings::JSON_KEY_USERS_USER_NAME])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_NAME] : null);

                            $user->setUserPassword(isset($jsonData[Settings::JSON_KEY_USERS_USER_PASSWORD])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_PASSWORD] : null);

                            return $user->signUp();
                        } else {
                            return "No data supplied to server";
                        }

                    }
                    break;

                default:
                    echo "Invalid method";
                    break;
            }
            break;
    }
}

function handleBook($jsonData){
    $user = new User();
    if(isset($_GET["token"])) { $user->setUserAuthToken($_GET["token"]); }
    if(isset($_FILES["uploaded"])) { $book = new Book($user, $_FILES["uploaded"]); } else { $book = new Book($user);}

    switch ($_GET["action"]){
        case "stats":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $book->setBookId($_GET["id"]);
                        return $book->getBookStats();
                    } else {
                        return Book::getGlobalBookStats();
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        default:
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $book->setBookId($_GET["id"]);
                        return $book->getBook();
                    } else {
                        return "No book selected";
                    }
                    break;

                case "POST":
                    if (isset($_GET["id"])) {
                        return "Invalid method";
                    } else {
                        if(isset($jsonData) && $jsonData !== null){
                            $user->setUserAuthToken(isset($jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN] : null);

                            $book->setBookTitle(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_TITLE])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_TITLE] : null);

                            $book->setBookAuthor(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_AUTHOR])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_AUTHOR] : null);

                            $book->setBookIsbn(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_ISBN])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_ISBN] : null);

                            $book->setBookPublicationDate(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE] : null);

                            $book->setBookCategory(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_CATEGORY])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_CATEGORY] : null);

                            return $book->addBook();
                        } else {
                            return "No data supplied to server";
                        }

                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;
    }
}

function handleBookshelf($jsonData){
    $user = new User();
    if(isset($_GET["token"])) { $user->setUserAuthToken($_GET["token"]); }
    if(isset($_FILES["uploaded"])) { $book = new Book($user, $_FILES["uploaded"]); } else { $book = new Book($user);}
    if(isset($_GET["book_id"])){ $book->setBookId($_GET["book_id"]); }
    $bookshelf = new Bookshelf($user, $book);

    switch ($_GET["action"]){
        case "book":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $bookshelf->setBookshelfId($_GET["id"]);
                        return $bookshelf->getBooksInBookshelf();
                    } else {
                        return "No bookshelf selected";
                    }
                    break;

                case "POST":
                    if (isset($_GET["id"])) {
                        if(isset($_GET["book_id"])) {
                            $bookshelf->setBookshelfId($_GET["id"]);
                            if(isset($jsonData) && $jsonData !== null){
                                $user->setUserAuthToken(isset($jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN])
                                    ? $jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN] : null);

                                return $bookshelf->returnBook();
                            } else {
                                return "No data supplied to server";
                            }
                        } else {
                            $bookshelf->setBookshelfId($_GET["id"]);
                            $user->setUserAuthToken(isset($jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN] : null);

                            $book->setBookTitle(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_TITLE])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_TITLE] : null);

                            $book->setBookAuthor(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_AUTHOR])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_AUTHOR] : null);

                            $book->setBookIsbn(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_ISBN])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_ISBN] : null);

                            $book->setBookPublicationDate(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_PUBLICATION_DATE] : null);

                            $book->setBookCategory(isset($jsonData[Settings::JSON_KEY_BOOKS_BOOK_CATEGORY])
                                ? $jsonData[Settings::JSON_KEY_BOOKS_BOOK_CATEGORY] : null);

                            $result = $book->addBook(true);
                            if(is_int($result)){
                                switch ($result){
                                    case -1:
                                        return Settings::buildErrorMessage(Settings::ERROR_MYSQL_CONNECTION);
                                        break;

                                    case -2:
                                        return Settings::buildErrorMessage(Settings::ERROR_AUTH_FAILED);
                                        break;
                                }
                            } else {
                                return $bookshelf->returnBook();
                            }
                        }
                    } else {
                        return "No bookshelf selected";
                    }
                    break;

                case "DELETE":
                    if (isset($_GET["id"])) {
                        $bookshelf->setBookshelfId($_GET["id"]);
                        if(isset($jsonData) && $jsonData !== null){
                            $user->setUserAuthToken(isset($jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN] : null);

                            return $bookshelf->borrowBook();
                        } else {
                            return "No data supplied to server";
                        }
                    } else {
                        return "No bookshelf selected";
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        case "stats":
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $bookshelf->setBookshelfId($_GET["id"]);
                        return $bookshelf->getBookshelfStats();
                    } else {
                        return Bookshelf::getGlobalBookshelfStats();
                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;

        default:
            switch ($_SERVER["REQUEST_METHOD"]){
                case "GET":
                    if (isset($_GET["id"])) {
                        $bookshelf->setBookshelfId($_GET["id"]);
                        return $bookshelf->getBookshelf();
                    } else {
                        return Bookshelf::getBookshelfList();
                    }
                    break;

                case "POST":
                    if (isset($_GET["id"])) {
                        return "Invalid method";
                    } else {
                        if(isset($jsonData) && $jsonData !== null){
                            $user->setUserAuthToken(isset($jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN])
                                ? $jsonData[Settings::JSON_KEY_USERS_USER_AUTH_TOKEN] : null);

                            $bookshelf->setBookshelfLatitude(isset($jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE])
                                ? $jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LATITUDE] : null);

                            $bookshelf->setBookshelfLongitude(isset($jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE])
                                ? $jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_LONGITUDE] : null);

                            $bookshelf->setBookshelfName(isset($jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME])
                                ? $jsonData[Settings::JSON_KEY_BOOKSHELVES_BOOKSHELF_NAME] : null);

                            return $bookshelf->addBookshelf();
                        } else {
                            return "No data supplied to server";
                        }

                    }
                    break;

                default:
                    return "Invalid method";
                    break;
            }
            break;
    }
}