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

    function query($args) {
        $query = "SELECT steps, date FROM " . $this->table_name;

        $fields = array();

        if (isset($args['where'])) {
            $where = array();
            foreach ($args['where'] as $field => $value) {
                $mysql_field = str_replace(array('(', ')', ','), '', strtolower($field));
                $where[] = $field . " = :" . $mysql_field;
                $fields[$mysql_field] = $value;
            }
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($query);

        foreach ($fields as $field => $value) {
            $stmt->bindValue($field, $value);
        }

        $stmt->execute();

        $steps = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $steps[] = $row;
        }
     
        return $steps;
    }

    function queryAll($args) {
        $query = "SELECT MAX(steps) AS max_steps, MIN(steps) AS min_steps, SUM(steps)/COUNT(steps) AS steps_average";

        if (isset($args['fields'])) {
            foreach ($args['fields'] as $field) {
                $query .= ", " . $field;
            }
        }

        $query .= " FROM " . $this->table_name;

        $fields = array();

        if (isset($args['where'])) {
            $query .= " WHERE " . implode(" AND ", $args['where']);
        }

        if (isset($args['group'])) {
            $query .= " GROUP BY " . $args['group'];
        }

        if (isset($args['having'])) {
            $query .= " HAVING ".implode(", ", $args['having']);
        }

        $stmt = $this->conn->prepare($query);

        if (isset($args['values'])) {
            foreach ($args['values'] as $field => $value) {
                $stmt->bindValue($field, $value);
            }
        }

        $stmt->execute();

        $steps = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $steps[] = $row;
        }
     
        return $steps;
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

    function me() {
        $query = "SELECT s.*, WEEKDAY(s.date) AS week_day FROM " . $this->table_name . " s WHERE user_uid = :user_uid AND device_id = :device_id AND date = :date ORDER BY s.date DESC LIMIT 0, 1";

        $stmt = $this->conn->prepare($query);
     
        $stmt->bindParam(':device_id', htmlspecialchars(strip_tags($this->device_id)));
        $stmt->bindParam(':user_uid', htmlspecialchars(strip_tags($this->user_uid)));
        $stmt->bindParam(':date', htmlspecialchars(strip_tags($this->date)));

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $week_day = $row['week_day'];

        $arr = array(
            "date" => $row['date'],
            "steps" => $row['steps']
        );

        /* Get the average for the same day */
        $steps = $this->queryAll(
            array('where'=>
                array('user_uid'=>$this->user_uid, 'device_id'=>$this->device_id, 'WEEKDAY(date)'=>(int)$week_day)
            )
        );

        foreach ($steps[0] as $field => $value) {
            $arr[$field] = $value;
        }

        /* Get yesterday's */
        $yesterday = $this->query(
            array('where'=>
                array('user_uid'=>$this->user_uid, 'device_id'=>$this->device_id, 'ADDDATE(date,1)'=>$arr['date'])
            )
        );

        $arr['yesterday'] = $yesterday[0]['steps'];

        return $arr;
    }

    function me2() {
        $steps = $this->queryAll(
            array(
                'fields'=> array('WEEK(date) as week_date', 'count(date) as days_total'),
                'where' => array('user_uid'=>$this->user_uid, 'device_id'=>$this->device_id),
                'group' => 'week_date'
            )
        );

        print_r($steps);


        $query = "SELECT sum(steps) as total_steps, WEEKDAY(s.date) AS week_day FROM " . $this->table_name . " s WHERE user_uid = :user_uid AND device_id = :device_id ORDER BY s.date DESC LIMIT 0, 1";

        $stmt = $this->conn->prepare($query);
     
        $stmt->bindParam(':device_id', htmlspecialchars(strip_tags($this->device_id)));
        $stmt->bindParam(':user_uid', htmlspecialchars(strip_tags($this->user_uid)));

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $week_day = $row['week_day'];

        $arr = array(
            "date" => $row['date'],
            "steps" => $row['steps']
        );

        /* Get the average for the same day */
        $steps = $this->queryAll(
            array('where'=>
                array('user_uid'=>$this->user_uid, 'device_id'=>$this->device_id, 'WEEKDAY(date)'=>(int)$week_day)
            )
        );

        foreach ($steps[0] as $field => $value) {
            $arr[$field] = $value;
        }

        /* Get yesterday's */
        $yesterday = $this->query(
            array('where'=>
                array('user_uid'=>$this->user_uid, 'device_id'=>$this->device_id, 'ADDDATE(date,1)'=>$arr['date'])
            )
        );

        $arr['yesterday'] = $yesterday[0]['steps'];

        return $arr;
    }

    function fetchAll($where = "") {
        $query = "SELECT MAX(steps) AS max_steps, MIN(steps) AS min_steps, SUM(steps)/COUNT(steps) AS steps_average, date FROM " . $this->table_name;

        if ($where != "") {
            $query .= " WHERE " . $where;
        }

        $query .=  " GROUP BY date DESC LIMIT 0,7";

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