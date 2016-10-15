<?php

namespace Futurologeek\SmartCrossing;

include_once("../config/Settings.php");
include_once("../config/Credentials.php");

class DatabaseConnection
{

    /**
     * @var \mysqli
     */
    private $mysqli;
    /**
     * @var string
     */
    private $host = Settings::DEBUG ? Credentials::DEV_MYSQL_HOST : Credentials::MYSQL_HOST;
    /**
     * @var string
     */
    private $user = Settings::DEBUG ? Credentials::DEV_MYSQL_LOGIN : Credentials::MYSQL_LOGIN;
    /**
     * @var string
     */
    private $password = Settings::DEBUG ? Credentials::DEV_MYSQL_PASSWORD : Credentials::MYSQL_PASSWORD;
    /**
     * @var string
     */
    private $database = Settings::DEBUG ? Credentials::DEV_MYSQL_DB : Credentials::MYSQL_DB;


    /**
     * DatabaseConnection constructor.
     * @param null $host
     * @param null $user
     * @param null $password
     * @param null $database
     */
    public function __construct($host = null, $user = null, $password = null, $database = null) {
        if($host != null){
            $this->host = $host;
        }

        if($user != null){
            $this->user = $user;
        }

        if($password != null){
            $this->password = $password;
        }

        if($database != null){
            $this->database = $database;
        }
    }

    public function databaseClose(){
        if($this->mysqli !== null){
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }

    /**
     * @param null $host
     * @param null $user
     * @param null $password
     * @param null $database
     * @return \mysqli|null
     */
    public function databaseConnect($host = null, $user = null, $password = null, $database = null){
        if($host !== null || $user !== null || $password !== null || $database !== null || $this->mysqli === null){
            if($host != null){ $this->host = $host; }
            if($user != null){ $this->user = $user; }
            if($password != null){ $this->password = $password; }
            if($database != null){ $this->database = $database; }
            $this->databaseClose();

            if($this->host == null || $this->user == null || $this->password == null || $this->database == null){
                return $this->mysqli = null;
            }

            $this->mysqli = @new \mysqli($this->host, $this->user, $this->password, $this->database);
            if($this->mysqli->connect_error){
                return $this->mysqli = null;
            }
            $this->mysqli->set_charset("utf8");
        }
        return $this->mysqli;
    }

    /**
     * @param $query
     * @param null $selected
     * @param null $where_types
     * @param null $where_variables
     * @return array|int|null
     */
    public function databaseRawQuery($query, $selected = null, $where_types = null, $where_variables = null){
        if($this->databaseConnect() == null){
            return -1;
        }

        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if($where_types != null && $where_variables != null){
            if(!($stmt->bind_param($where_types, ...$where_variables))){
                return -1;
            }
        }

        if(!($stmt->execute())){
            return -1;
        }

        if($selected != null){
            if(!($stmt->store_result())){
                return -1;
            }

            $output = array();
            $result = array_fill(0, count($selected), null);
            if(!($stmt->bind_result(...$result))){
                return -1;
            }

            while($stmt->fetch()){
                $temp = array();
                foreach ($result as $value) {
                    $temp[] = $value;
                }
                $output[] = $temp;
            }
        } else {
            $output = $stmt->affected_rows;
        }

        $stmt->close();

        return $output;
    }

    /**
     * @param $table
     * @param $columns
     * @param $input_types
     * @param $input
     * @return int|null
     */
    public function databaseInsertRow($table, $columns, $input_types, $input){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'INSERT INTO ' . $table . '(' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($input), '?')) . ')';
        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if(!($stmt->bind_param($input_types, ...$input))){
            return -1;
        }

        if(!($stmt->execute())){
            return -1;
        }

        $output = $stmt->insert_id;
        $stmt->close();

        return $output;
    }

    /**
     * @param $table
     * @param $where
     * @param $where_types
     * @param $where_variables
     * @return int|null
     */
    public function databaseDeleteRow($table, $where, $where_types, $where_variables){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'DELETE FROM ' . $table . ' WHERE ' . $where;
        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if(!($stmt->bind_param($where_types, ...$where_variables))){
            return -1;
        }

        if(!($stmt->execute())){
            return -1;
        }

        $output = $stmt->affected_rows;
        $stmt->close();

        return $output;
    }

    /**
     * @param $table
     * @return int|null
     */
    public function databaseDeleteAllRows($table){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'DELETE * FROM ' . $table;
        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if(!($stmt->execute())){
            return -1;
        }

        $output = $stmt->affected_rows;
        $stmt->close();

        return $output;
    }

    /**
     * @param $table
     * @param $columns
     * @param $input_types
     * @param $input
     * @param $where
     * @param $where_types
     * @param $where_variables
     * @return int|null
     */
    public function databaseUpdate($table, $columns, $input_types, $input, $where, $where_types, $where_variables){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'UPDATE ' . $table . ' SET ' . implode('=? , ', $columns) . '=? WHERE ' . $where;
        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if(!($stmt->bind_param($input_types . $where_types, ...$input, ...$where_variables))){
            return -1;
        }

        if(!($stmt->execute())){
            return -1;
        }
        $output = $stmt->affected_rows;
        $stmt->close();
        return $output;
    }

    /**
     * @param $table
     * @param $selected
     * @param null $where
     * @param null $where_types
     * @param null $where_variables
     * @param null $order_by
     * @param bool $descendant
     * @param int $buffer
     * @return array|int|null
     */
    public function databaseFetch($table, $selected, $where = null, $where_types = null, $where_variables = null, $order_by = null, $descendant = false, $buffer = 0){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'SELECT ' . implode(', ', $selected) . ' FROM '. $table . ($where != null ? (' WHERE ' . $where) : '') . ($order_by != null ? (' ORDER BY ? ' . ($descendant ? ' DESC' : ' ASC')) : '') . ($buffer > 0 ? (' LIMIT 0, ?') : '');
        $stmt = null;
        $output = null;

        if($order_by != null){
            if($where_variables == null){
                $where_types = "";
                $where_variables = array();
            }

            $where_types .= "s";
            $where_variables[] = $order_by;
        }

        if($buffer > 0){
            if($where_variables == null){
                $where_types = "";
                $where_variables = array();
            }

            $where_types .= "i";
            $where_variables[] = $buffer;
        }

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if($where_types != null && $where_variables != null){
            if(!($stmt->bind_param($where_types, ...$where_variables))){
                return -1;
            }
        }

        if(!($stmt->execute())){
            return -1;
        }

        if(!($stmt->store_result())){
            return -1;
        }

        $output = array();
        $result = array_fill(0, count($selected), null);
        if(!($stmt->bind_result(...$result))){
            return -1;
        }

        while($stmt->fetch()){
            $temp = array();
            foreach ($result as $value) {
                $temp[] = $value;
            }
            $output[] = $temp;
        }

        $stmt->close();
        return count($output) > 0 ? $output : null;
    }

    /**
     * @param $table
     * @param null $where
     * @param null $where_types
     * @param null $where_variables
     * @return int|null
     */
    public function databaseCount($table, $where = null, $where_types = null, $where_variables = null){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'SELECT COUNT(*) FROM '. $table . ($where != null ? (' WHERE ' . $where) : '');
        $stmt = null;
        $output = null;

        if(!($stmt = $this->mysqli->prepare($query))){
            return -1;
        }

        if($where_types != null && $where_variables != null){
            if(!($stmt->bind_param($where_types, ...$where_variables))){
                return -1;
            }
        }

        if(!($stmt->execute())){
            return -1;
        }

        if(!($stmt->store_result())){
            return -1;
        }

        $result = null;
        if(!($stmt->bind_result($result))){
            return -1;
        }

        $stmt->fetch();
        $stmt->close();

        return $result;
    }

    /**
     * @param $table
     * @param null $where
     * @param null $where_types
     * @param null $where_variables
     * @return bool|int
     */
    public function databaseExists($table, $where = null, $where_types = null, $where_variables = null){
        $result = $this->databaseCount($table, $where, $where_types, $where_variables);
        if($result == -1){
            return -1;
        } else {
            return $result > 0;
        }
    }
}