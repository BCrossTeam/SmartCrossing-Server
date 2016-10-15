<?php

include_once("../classes/User.php");
include_once("../config/Settings.php");

use Futurologeek\SmartCrossing\User as User;
use Futurologeek\SmartCrossing\Settings as Settings;

$json = json_decode(file_get_contents('php://input'), true);

if(isset($_GET["class"])){
    switch ($_GET["class"]) {
        case "user":
            $user = new User();

            switch ($_GET["action"]){
                case "sign":
                    switch ($_SERVER["REQUEST_METHOD"]){
                        case "POST":
                            if(isset($json) && $json !== null){
                                $user->setUserEmail(isset($json[Settings::JSON_KEY_USERS_USER_EMAIL])
                                    ? $json[Settings::JSON_KEY_USERS_USER_EMAIL] : null);

                                $user->setUserPassword(isset($json[Settings::JSON_KEY_USERS_USER_PASSWORD])
                                    ? $json[Settings::JSON_KEY_USERS_USER_PASSWORD] : null);

                                echo $user->signIn();
                            } else {
                                echo "No data supplied to server";
                            }
                            break;

                        default:
                            echo "Invalid method";
                            break;
                    }
                    break;

                case "auth":
                    switch ($_SERVER["REQUEST_METHOD"]){
                        case "GET":
                            if(isset($_GET["token"])){
                                $user->setUserAuthToken($_GET["token"]);
                                echo $user->signAuth();
                            } else {
                                echo "No data supplied to server";
                            }
                            break;

                        case "DELETE":
                            if(isset($_GET["token"])){
                                $user->setUserAuthToken($_GET["token"]);
                                echo $user->signOut();
                            } else {
                                echo "No data supplied to server";
                            }
                            break;

                        default:
                            echo "Invalid method";
                            break;
                    }
                    break;

                default:
                    switch ($_SERVER["REQUEST_METHOD"]){
                        case "GET":
                            if (isset($_GET["id"])) {
                                $user->setUserId($_GET["id"]);
                                echo $user->getUser();
                            } else {
                                echo "No user selected";
                            }
                            break;

                        case "POST":
                            if (isset($_GET["id"])) {
                                echo "Invalid method";
                            } else {
                                if(isset($json) && $json !== null){
                                    $user->setUserEmail(isset($json[Settings::JSON_KEY_USERS_USER_EMAIL])
                                        ? $json[Settings::JSON_KEY_USERS_USER_EMAIL] : null);

                                    $user->setUserName(isset($json[Settings::JSON_KEY_USERS_USER_NAME])
                                        ? $json[Settings::JSON_KEY_USERS_USER_NAME] : null);

                                    $user->setUserPassword(isset($json[Settings::JSON_KEY_USERS_USER_PASSWORD])
                                        ? $json[Settings::JSON_KEY_USERS_USER_PASSWORD] : null);

                                    echo $user->signUp();
                                } else {
                                    echo "No data supplied to server";
                                }

                            }
                            break;

                        default:
                            echo "Invalid method";
                            break;
                    }
                    break;
            }
            break;

        default:
            echo "Invalid class";
            break;
    }
} else {
    echo "No action";
}