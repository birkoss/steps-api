<?php
class User {
 
    private $conn;
    private $table_name = "users";
 
    public $uid;
    public $device_id;
    public $date_added;
    public $date_changed;
 
    public function __construct($db){
        $this->conn = $db;
    }

    function get() {
        $query = "SELECT u.* FROM " . $this->table_name . " u";

        $fields = array();
        $where = array();
        if ($this->device_id != null) {
            $where[] = "u.device_id = :device_id";
            $fields[':device_id'] = $this->device_id;
        }
        if ($this->uid != null) {
            $where[] = "u.uid = :uid";
            $fields[':uid'] = $this->uid;
        }

        if (count($where) > 0) {
            $query .= " WHERE " .implode(" AND ", $where) . " ";
        }

        $query .= " LIMIT 0,1";
     
        $stmt = $this->conn->prepare($query);
     
        foreach ($fields as $id => $value) {
            $stmt->bindParam($id, $value);
        }
     
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
     
        $this->uid = $row['uid'];
        $this->device_id = $row['device_id'];
        $this->date_added = $row['date_added'];
        $this->date_changed = $row['date_changed'];

        return true;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " SET device_id=:device_id, date_added=:date_added, date_changed=:date_changed";
     

        $stmt = $this->conn->prepare($query);
     
        $stmt->bindParam(":device_id", htmlspecialchars(strip_tags($this->device_id)));
        $stmt->bindParam(":date_added", htmlspecialchars(strip_tags($this->date_added)));
        $stmt->bindParam(":date_changed", htmlspecialchars(strip_tags($this->date_changed)));
     
        if ($stmt->execute()) {
            return true;
        }
     
        return false;
    }
}