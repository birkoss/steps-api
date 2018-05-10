<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
 
include_once '../config/core.php';
include_once '../config/database.php';
include_once '../objects/user.php';
include_once '../objects/step.php';
 
$database = new Database();
$db = $database->getConnection();
 
$user = new User($db);
$user->uid = isset($_GET['user_uid']) ? $_GET['user_uid'] : reject("2000", "No user UID provided");
  
if (!$user->get()) {
	reject("2000", "No user is matching this information");
}

$success = array();

switch (isset($_GET['filter']) ? $_GET['filter'] : 'days') {
	case "weeks":
		$days = getWeek($db, $user, date('Y-m-d'));

		$success = array(
			'stats' => $days
		);

		break;
	case "months":
		$start = date('Y-m-01');
		$end = date("Y-m-t", strtotime($start));

		/* Get the stats for the same day */
		$step = new Step($db);
		$step->user_uid = $user->uid;
		$step->device_id = isset($_GET['device_id']) ? base64_decode($_GET['device_id']) : reject("2000", "No device ID provided");

		$steps = $step->queryAll(
		    array(
		        'fields'=> array('date', 'MONTH(date) as month_date', 'YEAR(date) as year_date'),
		        'where' => array('user_uid = :user_uid', 'device_id = :device_id'),
		        'group' => 'month_date',
		        //'having' => array('week_date = '.(int)$weekOfYear)
		        'values'=> array(':user_uid'=>$step->user_uid, ':device_id'=>$step->device_id)
		    )
		);


		$success = array(
			'stats' => $steps
		);

		break;
	default:
		$date = date('Y-m-d');
		
		$days = getWeek($db, $user, $date);
		$data = null;
		foreach($days as $single_date => $single_data) {
			if ($single_date == $date) {
				$data = $single_data;
			}
		}

		$success = array(
			'stats' => $data
		);
}
 
resolve($success);

function getWeek($db, $user, $date) {
	$dayOfWeek = date('w', strtotime($date));
	$firstDayOfWeek = date('Y-m-d', strtotime("-".(int)$dayOfWeek." days", strtotime($date)));
	$lastDayOfWeek = date('Y-m-d', strtotime("+6 days", strtotime($firstDayOfWeek)));

	/* Generate the default value for the full week */
	$days = array();
	for ($i=0; $i<7; $i++) {
		$weekDay = ($i - 1);
		if ($weekDay < 0) {
			$weekDay = 6;
		}

		$date = date('Y-m-d', strtotime("+" . $i . " days", strtotime($firstDayOfWeek)));
		$days[$date] = array('steps'=>0, 'previous'=>0, 'average'=>0, 'weekday'=>$weekDay, 'max'=>0);
	}

	/* Get the stats for the same day */
	$step = new Step($db);
	$step->user_uid = $user->uid;
	$step->device_id = isset($_GET['device_id']) ? base64_decode($_GET['device_id']) : reject("2000", "No device ID provided");

	$steps = $step->queryAll(
	    array(
	        'fields'=> array('date'),
	        'where' => array('user_uid = :user_uid', 'device_id = :device_id', 'date >= :firstDate', 'date <= :lastDate'),
	        'group' => 'date',
	        //'having' => array('week_date = '.(int)$weekOfYear)
	        'values'=> array(':user_uid'=>$step->user_uid, ':device_id'=>$step->device_id, ':firstDate'=>$firstDayOfWeek, ':lastDate'=>$lastDayOfWeek)
	    )
	);
	foreach ($steps as $single_step) {
		$days[ $single_step['date'] ]['steps'] = (int)$single_step['steps_average'];
	}

	/* Get for the previous week */
	$steps = $step->queryAll(
	    array(
	        'fields'=> array('date', 'DATE_ADD(date, INTERVAL 1 WEEK) as date_index'),
	        'where' => array('user_uid = :user_uid', 'device_id = :device_id', 'date >= :firstDate', 'date <= :lastDate'),
	        'group' => 'date',
	        //'having' => array('week_date = '.(int)$weekOfYear)
	        'values'=> array(':user_uid'=>$step->user_uid, ':device_id'=>$step->device_id, ':firstDate'=>date('Y-m-d', strtotime("-7 days", strtotime($firstDayOfWeek))), ':lastDate'=>date('Y-m-d', strtotime("-7 days", strtotime($lastDayOfWeek))))
	    )
	);
	foreach ($steps as $single_step) {
		$days[ $single_step['date_index'] ]['previous'] = (int)$single_step['steps_average'];
	}

	/* Get average */
	$steps = $step->queryAll(
	    array(
	        'fields'=> array('date', 'weekday(date) as date_weekday'),
	        'where' => array('user_uid = :user_uid', 'device_id = :device_id'),
	        'group' => 'date_weekday',
	        'values'=> array(':user_uid'=>$step->user_uid, ':device_id'=>$step->device_id)
	    )
	);
	foreach ($steps as $single_step) {
		foreach ($days as $date => $single_day) {
			if ($single_day['weekday'] == $single_step['date_weekday']) {
				$days[$date]['average'] = round($single_step['steps_average']);

				if ($single_day['max'] < $single_step['max_steps']) {
					$days[$date]['max'] = (int)$single_step['max_steps'];
				}
			}
		}
	}
	return $days;
}