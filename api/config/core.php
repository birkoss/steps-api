<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

function reject($code, $message = "") {
	$error_arr = array(
		"status" => "error",
	    "code" =>  $code,
	    "message" => $message
	);

	echo json_encode($error_arr);
	die(""); 
}

function resolve($data) {
	$success_arr = array(
		"status" => "success",
	    "data" =>  $data
	);

	echo json_encode($success_arr);
	die(""); 
}