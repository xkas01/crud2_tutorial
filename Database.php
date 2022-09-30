<?php

class Database
{
    private $db_host = "localhost";
    private $db_user = "ata";
    private $db_pass = "123456";
    private $db_name = "testing";

    private $mysqli = "";
    private $result = [];
    private $conn = false;

    public function __construct()
    {
        if (!$this->conn) {
            $this->mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
            $this->conn = true;
            if ($this->mysqli->connect_error) {
                array_push($this->result, $this->mysqli->connect_error);
                return false;
            }
        } else {
            return true;
        }
    }

    // Insert Function
    public function insert($table, $params = [])
    {
        //Check to see if the table exists
        if ($this->tableExists($table)) {

            $table_columns = implode(', ', array_keys($params));
            $table_value = implode("', '", $params);

            $sql = "INSERT INTO $table ($table_columns) VALUES ('$table_value')";
            //Make the query to insert to the database
            if ($this->mysqli->query($sql)) {
                array_push($this->result, $this->mysqli->insert_id);
                return true; //The data has been inserted
            } else {
                array_push($this->result, $this->mysqli->error);
                return false;//The data has not been inserted
            }
        } else {
            return false;//Table does not exist
        }
    }

    // Update Function
    public function update($table, $params = [], $where = null)
    {
        if ($this->tableExists($table)) {
            $args = [];
            foreach ($params as $key => $value) {
                $args[] = "$key = '$value'";
            }
            $sql = "UPDATE $table SET " . implode(', ', $args);
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($this->mysqli->query($sql)) {
                array_push($this->result, $this->mysqli->affected_rows);
            } else {
                array_push($this->result, $this->mysqli->error);
            }
        } else {
            return false;
        }
    }

    // Delete Function
    public function delete($table, $where = null)
    {
        if ($this->tableExists($table)) {
            $sql = "DELETE FROM $table";
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($this->mysqli->query($sql)) {
                array_push($this->result, $this->mysqli->affected_rows);
                return true;
            } else {
                array_push($this->result, $this->mysqli->error);
                return false;
            }
        } else {
            return false;
        }
    }

    // Select Function
    public function select($table, $rows = "*", $join = null, $where = null, $order = null, $limit = null)
    {
        if ($this->tableExists($table)) {
            $sql = "SELECT $rows FROM $table";
            if ($join != null) {
                $sql .= " JOIN $join";
            }
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($order != null) {
                $sql .= " ORDER BY $order";
            }
            if ($limit != null) {
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                } else {
                    $page = 1;
                }
                $start = ($page - 1) * $limit;
                $sql .= " LIMIT $start,$limit";
            }

            $query = $this->mysqli->query($sql);
            if ($query) {
                $this->result = $query->fetch_all(MYSQLI_ASSOC);
                return true;
            } else {
                array_push($this->result, $this->mysqli->error);
                return false;
            }
        } else {
            return false;
        }
    }

    public function pagination($table, $join = null, $where = null, $limit = null)
    {
        if ($this->tableExists($table)) {
            if ($limit != null) {
                $sql = "SELECT COUNT(*) FROM $table";
                if ($where != null) {
                    $sql .= " WHERE $where";
                }
                if ($join != null) {
                    $sql .= " JOIN $join";
                }

                $query = $this->mysqli->query($sql);

                $total_record = $query->fetch_array();
                $total_record = $total_record[0];

                $total_page = ceil($total_record / $limit);

                $url = basename($_SERVER['PHP_SELF']);

                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                } else {
                    $page = 1;
                }

                $output = "<ul class='pagination'>";

                if ($page > 1) {
                    $output .= "<li><a href='$url?page=" . ($page - 1) . "'>Prev</a></li>";
                }

                if ($total_record > $limit) {
                    for ($i = 1; $i <= $total_page; $i++) {
                        if ($i == $page) {
                            $cls = "class='active'";
                        } else {
                            $cls = "";
                        }
                        $output .= "<li><a href='$url?page=$i'>$i</a></li>";
                    }
                }
                if ($total_page > $page) {
                    $output .= "<li><a $cls href='$url?page=" . ($page + 1) . "'>Next</a></li>";
                }
                $output .= "</ul>";

                return $output;

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function sql($sql)
    {
        $query = $this->mysqli->query($sql);
        if ($query) {
            $this->result = $query->fetch_all(MYSQLI_ASSOC);
            return true;
        } else {
            array_push($this->result, $this->mysqli->error);
            return false;
        }
    }

    private function tableExists($table)
    {
        $sql = "SHOW TABLE FROM $this->db_name LIKE '$table'";
        $tableInDb = $this->mysqli->query($sql);

        if ($tableInDb) {
            if ($tableInDb->num_rows == 1) {
                return true;
            } else {
                array_push($this->result, $table . " does not exist in this database");
                return false;
            }
        }
    }

    public function getResult()
    {
        $val = $this->result;
        $this->result = [];
        return $val;
    }

    //close connection
    public function __destruct()
    {
        if ($this->conn) {
            if ($this->mysqli->close()) {
                $this->conn = false;
                return true;
            }
        } else {
            return false;
        }
    }
}