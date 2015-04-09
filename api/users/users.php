<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:16 PM
 */


if($_SERVER['REQUEST_METHOD'] == "POST"){ //createNewUser
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];
    $currentTime = time();

    $database = mysqli_connect("localhost","root","root","forum","8889");
    if (!$database){
        http_response_code(500);
        die("Unable to connect to database");
    }

    $data = createNewUser();
}
else if($_SERVER['REQUEST_METHOD'] == "GET"){ //getUserById
    $id = $_REQUEST['id'];

    $database = mysqli_connect("localhost","root","root","forum","8889");
    if (!$database){
        http_response_code(500);
        die("Unable to connect to database");
    }

    $data = getUserById();
}
else { //Do Nothing
    http_response_code(405);
    die("Must use GET or POST method. You used ".$_SERVER['REQUEST METHOD']);
}

//---return data---
$returnData = array("data"=>$data);
//set content type
header("Content-Type: application/json");
http_response_code(200);
//encode to json
die(json_encode($returnData));

//------------------------------- MAIN FUNCTIONS --------------------------------

function createNewUser(){
    global $database, $email, $password;
    //sanitize user input
    $email = mysqli_real_escape_string($database,$email);
    $password = mysqli_real_escape_string($database, $password);
//sql query
    $password = password_hash($password, PASSWORD_BCRYPT);

    if(!checkUserTaken()){
        registerUser();
        if (!checkUserCreated()){
            http_response_code(400);
            die("User not created."); //TODO:change later
        }
    }
    else{
        http_response_code(400);
        die("Username taken."); //change later
    }

    $query = "SELECT * FROM user WHERE email = '$email'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("User not found."); //TODO:change later
    }
//    echo "User created.";
    $row = mysqli_fetch_assoc($response);

    $data = array('id'=>$row['id'], 'email'=>$row['email'],'created_time'=>$row['created_time'],
        'rank'=>$row['rank']);
    return $data;
}

function getUserById(){
    global $database, $id;

    $query = "SELECT * FROM user WHERE id = '$id'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Id not found."); //TODO:change later
    }
//    echo "User found";
    $row = mysqli_fetch_assoc($response);

    $data = array('id'=>$row['id'], 'email'=>$row['email'],'created_time'=>$row['created_time'],
        'rank'=>$row['rank']);
    return $data;
}

//------------------------------- SUB-FUNCTIONS ------------------------------------
function registerUser(){
    global $database, $email, $password, $currentTime;
    $query = "INSERT INTO user(email,password,created_time, rank) VALUES ('$email', '$password','$currentTime',0 )";
    mysqli_query($database, $query);
}

function checkUserTaken(){
    global $database, $email;
    $query = "SELECT * FROM user WHERE email = '$email'";
    $emailResponse = mysqli_query($database, $query);

    if(mysqli_num_rows($emailResponse) != 0){
        return true;
    }
    else{
        return false;
    }
}

function checkUserCreated(){
    global $database, $email;
    $query = "SELECT * FROM user WHERE email = '$email'";
    $emailResponse = mysqli_query($database, $query);

    if(mysqli_num_rows($emailResponse) != 0){
        return true;
    }
    else{
        return false;
    }
}

?>