<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:15 PM
 */

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

$database = mysqli_connect("localhost","root","root","forum","8889");
if (!$database) {
    http_response_code(500);
    die("Unable to connect to database");
}

if($_SERVER['REQUEST_METHOD'] == "POST"){ //create new post
    $data = newPost();
}
else if($_SERVER['REQUEST_METHOD'] == "GET"){
    $threadID = $_REQUEST['threadID'];
    $id = $_REQUEST['id'];

    if ($threadID != null){
        $data = getPostsByThreadID($threadID);
    }
    else if ($id != null){
        $data = getPostByID($id);
    }
    else{
        http_response_code(400);
        die("Incorrect parameters.");
    }
}
else{
    http_response_code(405);
    die('Must use GET or POST method. You used '.$SERVER['REQUEST_METHOD']);
}

$returnData = array("data"=>$data);
//set content type
header("Content-Type: application/json");
http_response_code(200);
//encode to json
die(json_encode($returnData));

//------------- MAIN FUNCTIONS ------------

function newPost(){
    global $database;
    $text = $_REQUEST['text'];
    $threadID = $_REQUEST['threadID'];
    $sessionKey = $_REQUEST['sessionKey'];
    $sessionID = $_REQUEST['sessionID'];
    $time = time();

    //get user_id based on session key
    $user_id = getUserID($database, $sessionKey, $sessionID);


    $query = "INSERT INTO post(text, thread_id, user_id, created_time) VALUES ('$text', '$threadID', '$user_id','$time')";
    mysqli_query($database, $query);

    $postID = mysqli_insert_id($database);
    $votes = getVotes($database, $postID);

    $query = "SELECT * FROM post WHERE id = '$postID'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Post not found.");
    }
    $row = mysqli_fetch_assoc($response);

    $data = array('id'=>$row['id'], 'text'=>$row['text'],
        'thread_id'=>$row['thread_id'],'user_id'=>$row['user_id'],
        'created_time'=>$row['created_time'], 'voteTotal'=>$votes);

    return $data;
}

function getPostsByThreadID($threadID){
    global $database;
    $query = "SELECT * FROM post WHERE thread_id = '$threadID'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Thread not found.");
    }

    $data = array();
    while ($row = mysqli_fetch_assoc($response)){
        $votes = getVotes($database, $row['id']);
        $tempArray = array('id'=>$row['id'], 'text'=>$row['text'],
            'thread_id'=>$row['thread_id'],'user_id'=>$row['user_id'],
            'created_time'=>$row['created_time'], 'voteTotal'=>$votes);

        array_push($data, $tempArray);
    }
    return $data;
}

function getPostByID($postID){
    global $database;
    $query = "SELECT * FROM post WHERE id = '$postID'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Post not found.");
    }

    $row = mysqli_fetch_assoc($response);
    $votes = (string)getVotes($database, $postID);

    $data = array('id'=>$row['id'],'text'=>$row['text'],
        'thread_id'=>$row['thread_id'],'user_id'=>$row['user_id'],
        'created_time'=>$row['created_time'],'voteTotal'=>$votes);

    return $data;
}



//-------------- SUB_FUNCTIONS -------------------

function getUserID($database, $sessionKey, $sessionID){
    //get user_id based on session key
    $query1 = "SELECT * FROM session WHERE session_key = '$sessionKey'";
    $response = mysqli_query($database, $query1);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("User id not found."); //TODO:change later
    }
    $row = mysqli_fetch_assoc($response);
    $dbID = $row['id']; $dbKey = $row['session_key'];

    //session ID and Key authentication
    if($dbID == $sessionID && $dbKey == $sessionKey){
        $user_id = $row['user_id'];
    }
    else{
        http_response_code(401);
        die(''.$dbID.' AND '.$sessionID.' - '.$dbKey.' AND '.$sessionKey);
    }

    return $user_id;
}

function getVotes($database, $postID){
    $query = "SELECT * FROM votes WHERE post_id = '$postID'";
    $response = mysqli_query($database, $query);

    $total=0;
    if (mysqli_num_rows($response) == 0) {
        return $total;
    }


    while($row = mysqli_fetch_assoc($response)){
        $total += $row['vote'];
    }

    return (string)$total;
}

?>