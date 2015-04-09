<?php
/**
 * Created by PhpStorm.
 * User: brandongroff
 * Date: 2/10/15
 * Time: 1:15 PM
 */

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

if($_SERVER['REQUEST_METHOD'] == "GET"){
    $data = getRequest();
}
else if($_SERVER['REQUEST_METHOD'] == "POST"){
    $data = postRequest();
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

//------------------------- REQUEST_METHOD FUNCTIONS ---------------

function getRequest(){
    $topicID = $_REQUEST['topicID'];
    $threadID = $_REQUEST['id'];

    $database = mysqli_connect("localhost","root","root","forum","8889");

    if (!$database) {
        http_response_code(500);
        die("Unable to connect to database");
    }

    if($topicID != null){
        $data = listThreadsByTopic($database, $topicID);
    }
    else if($threadID != null){
        $data = listPostsForThread($database, $threadID);
    }else{
        http_response_code(400);
        die("Incorrect parameters.");
    }
    return $data;
}

function postRequest(){
    $threadName = $_REQUEST['name'];
    $topicID = $_REQUEST['topicID'];
    $sessionKey = $_REQUEST['sessionKey'];
    $sessionID = $_REQUEST['sessionID'];
    $time = time();

    $database = mysqli_connect("localhost","root","root","forum","8889");

    if (!$database) {
        http_response_code(500);
        die("Unable to connect to database");
    }

    $user_id = getUserID($database, $sessionKey, $sessionID);

    $query2 = "INSERT INTO thread(name, created_time, deleted, user_id, topic_id) VALUES ('$threadName','$time', '0', '$user_id','$topicID')";
    mysqli_query($database, $query2);

    $query3 = "SELECT * FROM thread WHERE name = '$threadName'";
    $response = mysqli_query($database, $query3);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Thread not found."); //TODO:change later
    }
    $row = mysqli_fetch_assoc($response);
    $data = array('id'=>$row['id'], 'created_time'=>$row['created_time'],
        'deleted'=>$row['deleted'], 'user_id'=>$row['user_id'],
        'topic_id'=>$row['topic_id'], 'name'=>$row['name']);

    return $data;
}

//--------------------------- MAIN FUNCTIONS ----------------------
function listThreadsByTopic($database, $topicID){

    $query = "SELECT * FROM thread WHERE topic_id = '$topicID'";

    $response = mysqli_query($database, $query);
    if(mysqli_num_rows($response) == 0){
        http_response_code(400);
        die("Threads not found.");
    }

    $data = array();
    while($row = mysqli_fetch_assoc($response)){
        $tempArray = array('id'=>$row['id'], 'created_time'=>$row['created_time'],
            'deleted'=>$row['deleted'], 'user_id'=>$row['user_id'],
            'topic_id'=>$row['topic_id'], 'name'=>$row['name']);
        array_push($data, $tempArray);
    }

    return $data;
}

function listPostsForThread($database, $threadID)
{
    $query = "SELECT * FROM thread WHERE id = '$threadID'";
    $response = mysqli_query($database, $query);
    if (mysqli_num_rows($response) == 0) {
        http_response_code(400);
        die("Thread not found."); //TODO:change later
    }

    $row = mysqli_fetch_assoc($response);
    $data = array('id' => $row['id'], 'created_time' => $row['created_time'],
        'deleted' => $row['deleted'], 'user_id' => $row['user_id'],
        'topic_id' => $row['topic_id'], 'name' => $row['name'],
        'posts' => '');


    //get posts from thread ID
    $query = "SELECT * FROM post WHERE thread_id = '$threadID'";
    $response = mysqli_query($database, $query);
    if (mysqli_num_rows($response) == 0) {
        http_response_code(400);
        die("Posts not found."); //TODO:change later
    }

    $postsArray = array();
    while ($row = mysqli_fetch_assoc($response)) {
        //get user sub-data
        $userArray = getUserData($database,$row['user_id']);
        $votes = getVotes($database, $row['id']);

        $tempArray = array('id' => $row['id'], 'text' => $row['text'],
        'thread_id'=>$row['thread_id'], 'user_id'=>$row['user_id'],
            'created_time'=>$row['created_time'], 'voteTotal'=>$votes,
            'user'=>$userArray);

        array_push($postsArray, $tempArray);
    }

    $data['posts'] = $postsArray;

    return $data; //TODO:check return. Single thread header line isn't loading.

}

//---------------------- SUB-FUNCTIONS -------------------------

function getUserData($database, $user){
    $newQuery = "SELECT * FROM user WHERE id = '$user'";
    $userResponse = mysqli_query($database, $newQuery);
    if (mysqli_num_rows($userResponse) == 0) {
        http_response_code(400);
        die("User not found."); //TODO:change later
    }

    $userRow = mysqli_fetch_assoc($userResponse);
    $tempArray = array('id'=>$userRow['id'], 'email'=>$userRow['email'],
            'created_time'=>$userRow['created_time'], 'rank'=>$userRow['rank']);

    return $tempArray;
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

    return $total.'';
}

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

?>