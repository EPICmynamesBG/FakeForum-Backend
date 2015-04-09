<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:16 PM
 */

if($_SERVER['REQUEST_METHOD'] != "GET"){
    http_response_code(405);
    die("Must use GET method");
}

$email = $_REQUEST['email'];
$password = $_REQUEST['password'];

$database = mysqli_connect("localhost","root","root","forum","8889");

if (!$database){
    http_response_code(500);
    die("Unable to connect to database");
}
//sanitize user input
$email = mysqli_real_escape_string($database,$email);
$password = mysqli_real_escape_string($database, $password);
//s1l query
$query = "SELECT * FROM user WHERE email = '$email'";
$emailResponse = mysqli_query($database, $query);

if(mysqli_num_rows($emailResponse) == 0){
    http_response_code(400);
    die("Username not valid."); //TODO:change later
}
//fetch row
$emailAssoc = mysqli_fetch_assoc($emailResponse);
$dbPassword = str_replace('@', '$', $emailAssoc['password']);

//password verification
if(!password_verify($password, $dbPassword)){
    http_response_code(400);
    die("Password not valid."); //TODO:change later
}
//generate session key
$sessionKey = bin2hex(openssl_random_pseudo_bytes(30));

$query = "INSERT INTO session (session_key, user_id) VALUES('$sessionKey', {$emailAssoc['id']})";

mysqli_query($database,$query);
//get latest id out of insert
$sessionID = mysqli_insert_id($database);

$data = array("sessionID" => $sessionID, "sessionKey"=>$sessionKey, "userID"=>$emailAssoc['id']);
$returnData = array("data"=>$data);
//set content type
header("Content-Type: application/json");
http_response_code(200);
//encode to json
die(json_encode($returnData));
?>