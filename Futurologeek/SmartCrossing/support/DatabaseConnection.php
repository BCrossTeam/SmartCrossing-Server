<?php

namespace Futurologeek\SmartCrossing;

include_once("../config/Settings.php");
include_once("../config/Credentials.php");

class DatabaseConnection
{
    /** @var \mysqli */
    private $mysqli;
    /** @var string */
    private $host = Settings::DEBUG ? Credentials::DEV_MYSQL_HOST : Credentials::MYSQL_HOST;
    /** @var string */
    private $user = Settings::DEBUG ? Credentials::DEV_MYSQL_LOGIN : Credentials::MYSQL_LOGIN;
    /** @var string */
    private $password = Settings::DEBUG ? Credentials::DEV_MYSQL_PASSWORD : Credentials::MYSQL_PASSWORD;
    /** @var string */
    private $database = Settings::DEBUG ? Credentials::DEV_MYSQL_DB : Credentials::MYSQL_DB;

    /**
     * DatabaseConnection constructor.
     *
     * @param null|string $host
     * @param null|string $user
     * @param null|string $password
     * @param null|string $database
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
     * Function used to connect to database.
     * If params are null default credentials are used.
     * In other case supplied credentials are used.
     *
     * @param null|string $host
     * @param null|string $user
     * @param null|string $password
     * @param null|string $database
     *
     * Returns:
     * mysqli on success
     * null on error
     *
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
     * Function used to execute query not possible to execute in other functions ex. statement using joined tables.
     *
     * @param string $query
     * @param null|array $selected
     * @param null|string $where_types
     * @param null|array $where_variables
     *
     * Returns:
     * -1 on error
     * affected rows count on success if $selected is null
     * selected rows (array) on success if $selected is not null
     *
     * @return array|int
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
     * Function used to insert single table row to database.
     *
     * @param string $table
     * @param array $columns
     * @param string $input_types
     * @param array $input
     *
     * Returns:
     * -1 on error
     * inserted row id on success
     *
     * @return int
     */
    public function databaseInsertRow($table, $columns, $input_types, $input){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'INSERT INTO ' . $table . '(' . implode(', ', $columns) .
            ') VALUES (' . implode(', ', array_fill(0, count($input), '?')) . ')';
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
     * Function used to delete table row(s) from database.
     *
     * @param string $table
     * @param string $where
     * @param string $where_types
     * @param array $where_variables
     *
     * Returns:
     * -1 on error
     * affected rows count on success
     *
     * @return int
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
     * Function used to delete all table rows from database.
     *
     * @param string $table
     *
     * Returns:
     * -1 on error
     * affected rows count on success
     *
     * @return int
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
     * Function used to update table row in database.
     *
     * @param string $table
     * @param array $columns
     * @param string $input_types
     * @param array $input
     * @param string $where
     * @param string $where_types
     * @param array $where_variables
     *
     * Returns:
     * -1 on error
     * affected rows count on success
     *
     * @return int
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
     * Function used to fetch table row(s) form database.
     *
     * @param string $table
     * @param array $selected
     * @param null|string $where
     * @param null|string $where_types
     * @param null|array $where_variables
     * @param null|string $order_by
     * @param null|bool $descendant
     * @param int $buffer
     *
     * Returns:
     * -1 on error
     * null if no rows has been fetched
     * array on success
     *
     * @return array|int|null
     */
    public function databaseFetch($table, $selected, $where = null, $where_types = null, $where_variables = null,
                                  $order_by = null, $descendant = false, $buffer = 0){
        if($this->databaseConnect() == null){
            return -1;
        }

        $query = 'SELECT ' . implode(', ', $selected) . ' FROM '. $table . ($where != null ? (' WHERE ' . $where) : '')
            . ($order_by != null ? (' ORDER BY '. $order_by . ($descendant ? ' DESC' : ' ASC')) : '')
            . ($buffer > 0 ? (' LIMIT 0, ?') : '');
        $stmt = null;
        $output = null;

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
     * Function used to check count of table rows matching conditions.
     *
     * @param string $table
     * @param null|string $where
     * @param null|string $where_types
     * @param null|array $where_variables
     *
     * Returns
     * -1 on error
     * rows matching condition on success
     *
     * @return int
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
     * Function used to check if database table contains row matching conditions.
     *
     * @param string $table
     * @param null|string $where
     * @param null|string $where_types
     * @param null|array $where_variables
     *
     * Returns:
     * -1 on error
     * false if row is not present in database
     * true if row is present in database
     *
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