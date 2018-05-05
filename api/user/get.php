<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
 
// include database and object files
include_once '../config/core.php';
include_once '../config/database.php';
include_once '../objects/user.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
$user = new User($db);
 
$user->device_id = isset($_GET['device_id']) ? $_GET['device_id'] : reject("1000", "No Device ID provided");
 
if (!$user->get()) {
	$user->date_added = $user->date_changed = date("Y-m-d H:i:s");
	$user->create();
	$user->get();
}

$user_arr = array(
    "uid" =>  $user->uid,
    "device_id" => $user->device_id,
    "date_added" => $user->date_added,
    "date_changed" => $user->date_changed
);
 
resolve($user_arr);
