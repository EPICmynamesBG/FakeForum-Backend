<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:15 PM
 */

//error_reporting(E_ALL);
//ini_set('display_errors',1);

$database = mysqli_connect("localhost","root","root","forum","8889");
if (!$database) {
    http_response_code(500);
    die("Unable to connect to database");
}

if($_SERVER['REQUEST_METHOD'] == "GET"){
    $data = getUserVotes();
}
else if($_SERVER['REQUEST_METHOD'] == "POST"){
    $data = makeVote();
}
else{
    http_response_code(405);
    die("Must use GET method");
}

$returnData = array("data"=>$data);
//set content type
header("Content-Type: application/json");
http_response_code(200);
//encode to json
die(json_encode($returnData));

//---------------- MAIN FUNCTIONS --------------

function getUserVotes(){
    global $database;
    $sessionKey = $_REQUEST['sessionKey'];
    $sessionID = $_REQUEST['sessionID'];

    $user_id = getUserID($database, $sessionKey, $sessionID);

    $query = "SELECT * FROM votes WHERE user_id = '$user_id'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Votes not found.");
    }
    $row = mysqli_fetch_assoc($response);

    $data = array('post_id'=>$row['post_id'], 'vote'=>$row['vote'],
        'user_id'=>$row['user_id']);

    return $data;
}

function makeVote(){
    global $database;
    $vote = $_REQUEST['vote'];
    $postID = $_REQUEST['postID'];
    $sessionKey = $_REQUEST['sessionKey'];
    $sessionID = $_REQUEST['sessionID'];
    $user_id = getUserID($database, $sessionKey, $sessionID);

    if(alreadyVotedOn($postID, $user_id)){
        $query = "UPDATE votes SET vote = '$vote' WHERE user_id = '$user_id' AND post_id = '$postID'";
    }
    else {
        $query = "INSERT INTO votes(user_id, post_id, vote) VALUES ('$user_id','$postID','$vote')";
    }

    mysqli_query($database, $query);

    $data = array('success'=>true);//TODO:any way to check this?

    return $data;
}


//--------------- SUB-FUNCTIONS ---------------

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

function alreadyVotedOn($postID, $userID){
    global $database;
    $query = "SELECT * FROM votes WHERE post_id = '$postID' AND user_id = '$userID'";
    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 1){
        return true;
    }
    else if (mysqli_num_rows($response) > 1){
        http_response_code(500);
        die("Too many votes for one user on a single post");
    }

    return false;


}

?>