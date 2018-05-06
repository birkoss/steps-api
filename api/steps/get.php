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

$step = new Step($db);
$step->user_uid = $user->uid;
$step->device_id = isset($_GET['device_id']) ? base64_decode($_GET['device_id']) : reject("2000", "No device ID provided");

$steps = $step->fetch();

$stats = new Step($db);

$success = array(
    "steps" => $steps,
    "stats" => $stats->fetchAll()
);
 
resolve($success);