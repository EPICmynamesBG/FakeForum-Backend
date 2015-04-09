<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:15 PM
 */

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

if($_SERVER['REQUEST_METHOD'] != "GET"){
    http_response_code(405);
    die("Must use GET method");
}

$topicID = $_REQUEST['topicID'];

$database = mysqli_connect("localhost","root","root","forum","8889");

if (!$database){
    http_response_code(500);
    die("Unable to connect to database");
}


$query = "SELECT * FROM topic";
$response = mysqli_query($database, $query);

if(mysqli_num_rows($response) == 0){
    http_response_code(400);
    die("No topics found."); //TODO:change later
}

$data = array();
while($row = mysqli_fetch_assoc($response)) {
    $tempArray = array('id'=>$row['id'],'name'=>$row['name']);
    array_push($data, $tempArray);
}

$returnData = array("data"=>$data);
//set content type
header("Content-Type: application/json");
http_response_code(200);
//encode to json
die(json_encode($returnData));


?>