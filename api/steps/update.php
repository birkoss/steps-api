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
mail("birkoss@gmail.com", "UPDATE", "OK");
$database = new Database();
$db = $database->getConnection();
 
$user = new User($db);
$user->uid = isset($_GET['user_uid']) ? $_GET['user_uid'] : reject("1000", "No user UID provided");
  
if (!$user->get()) {
	reject("2000", "No user is matching this information");
}

$devices = json_decode(base64_decode($_GET['data']));

$systemID = "";

foreach ($devices as $single_device) {
    $systemID = $single_device->systemID;

    foreach ($single_device->steps as $single_step) {
        $step = new Step($db);
        $step->device_id = ($single_device->uuid != "" ? $single_device->uuid : $single_device->systemID);
        $step->user_uid = $user->uid;
        $step->date = $single_step->date;
        $step->steps = $single_step->count;
        $step->date_changed = date("Y-m-d H:i:s");
        $step->update();
    }
}

$success = array(
    "systemID" =>  $systemID,
    "date_updated" => date('Y-m-d H:i:s')
);
 
resolve($success);