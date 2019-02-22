<?php

class Auth {

    public static function try_auth($user_id, $password) : bool {

        if($user_id === 0 || !is_numeric($user_id)) {
            return false;
        }

        if(isset($_COOKIE["auth_token_secret"]) && !empty($_COOKIE["auth_token_secret"])) {
            if(self::checkAuthToken($user_id)) {
                return true;
            }
        }

        $conn = DB::getDBConn();

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");

        $stmt->execute(array($user_id));

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(password_verify($password, $user["password"])) {

            self::generateNewAuthToken($user["id"]);
            setcookie("userid", $user["id"]);
            setcookie("username", self::idToUsername($user["id"]));

            return true;

        } else {
            return false;
        }
    }

    public static function generateNewAuthToken($user_id) {

        try {
            $newToken = random_bytes(16);
        } catch (Exception $e) {
            $newToken = openssl_random_pseudo_bytes(16);
        }

        $newToken = base64_encode($newToken);

        $conn = DB::getDBConn();

        $stmt = $conn->prepare("INSERT INTO `auth_tokens` (`user_id`, `token`, `created`) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute(array($user_id, $newToken));

        setcookie("auth_token_secret", $newToken);
    }

    public static function checkAuthToken($user_id, $token = "") {

        if(empty($token)) {
            if(empty($_COOKIE["auth_token_secret"])) {
                return false;
            } else {
                $token = $_COOKIE["auth_token_secret"];
            }
        }


        $conn = DB::getDBConn();

        $stmt = $conn->prepare("SELECT * FROM auth_tokens WHERE user_id = ? AND created + INTERVAL 14 DAY > NOW() ORDER BY created DESC LIMIT 10");
        $stmt->execute(array($user_id));

        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(sizeof($res) < 1) { // No Auth tokens found, so we do not auth the user
            return false;
        }

        $userAuth = false;

        foreach ($res as $try_token) { // We iterate over the most recent 10 tokens
            if($token === $try_token["token"]) {
                $userAuth = true;
                break;
            }
        }

        if(!$userAuth) {
            setcookie("auth_token_secret", "null", time()-1);
        }

        return $userAuth;
    }

    public static function usernameToId($username) : int {
        $conn = DB::getDBConn();
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(array($username));

        $res = $stmt->fetch();

        if(is_numeric($res["id"])) {
            return $res["id"];
        } else {
            return 0;
        }
    }

    public static function idToUsername($user_id) : string {
        $conn = DB::getDBConn();
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute(array($user_id));

        $res = $stmt->fetch();

        if(is_string($res["username"])) {
            return $res["username"];
        } else {
            return "";
        }
    }

    public static function loggedInOnly() {
        if(!isset($_COOKIE["userid"]) || !is_numeric($_COOKIE["userid"])) {
            echo "no userid/wrong userid";
            header("Location: login.php");
            exit;
        }

        if(!isset($_COOKIE["auth_token_secret"])) {
            echo "no auth token";
            header("Location: login.php");
            exit;
        } else {
            if(!self::checkAuthToken($_COOKIE["userid"])) {
                echo "invalid token";
                header("Location: login.php");
                exit;
            }
        }
    }
}