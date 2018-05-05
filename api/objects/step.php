<?php

class Step {
 
    private $conn;
    private $table_name = "user_steps";
 
    public $user_uid;
    public $device_id;
    public $date;
    public $date_added;
    public $date_changed;
    public $steps;
 
    public function __construct($db){
        $this->conn = $db;
    }

    function fetch() {
        $query = "SELECT s.* FROM " . $this->table_name . " s WHERE user_uid = :user_uid AND device_id = :device_id ORDER BY s.date DESC LIMIT 0, 7";

        $stmt = $this->conn->prepare($query);
     
        $stmt->bindParam(':device_id', htmlspecialchars(strip_tags($this->device_id)));
        $stmt->bindParam(':user_uid', htmlspecialchars(strip_tags($this->user_uid)));

        $stmt->execute();

        $steps = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $step = array(
                "date" => $row['date'],
                "steps" => $row['steps']
            );

            $steps[] = $step;
        }
     
        return $steps;
    }
    function fetchAll() {
        $query = "SELECT MAX(steps) AS max_steps, MIN(steps) AS min_steps, SUM(steps)/COUNT(steps) AS steps_average, date FROM " . $this->table_name . " GROUP BY date DESC LIMIT 0,7";

        $stmt = $this->conn->prepare($query);

        $stmt->execute();

        $steps = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $step = array(
                "date" => $row['date'],
                "average" => $row['steps_average'],
                "min" => $row['min_steps'],
                "max" => $row['max_steps']
            );

            $steps[] = $step;
        }
     
        return $steps;
    }

    function update() {

        $query = "INSERT INTO " . $this->table_name . " SET device_id = :device_id, user_uid = :user_uid, `date` = :date, date_added = :date_added, date_changed = :date_changed, steps = :steps ON DUPLICATE KEY UPDATE date_changed = :date_changed, steps = :steps";

        $stmt = $this->conn->prepare($query);
     
     
        $stmt->bindParam(':device_id', htmlspecialchars(strip_tags($this->device_id)));
        $stmt->bindParam(':user_uid', htmlspecialchars(strip_tags($this->user_uid)));
        $stmt->bindParam(':date', htmlspecialchars(strip_tags($this->date)));
        $stmt->bindParam(':date_added', htmlspecialchars(strip_tags(date("Y-m-d H:i:s"))));
        $stmt->bindParam(':date_changed', htmlspecialchars(strip_tags($this->date_changed)));
        $stmt->bindParam(':steps', htmlspecialchars(strip_tags($this->steps)));
        
     
        if($stmt->execute()){
            return true;
        }
     
        return false;
    }
}