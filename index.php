<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
<?php
$username = "id21726884_bhyrav";
$password = "iamPrasad@256";
$conn = new mysqli("localhost", $username, $password, "twitter");

function query($conn, $query){
    $result = mysqli_query($conn, $query);
    return $result;
}

function getSingle($conn, $query){
    $result = query($conn, $query);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

function getUID($conn, $ip){
    $escapedIP = mysqli_real_escape_string($conn, $ip);
    $query = "SELECT uid FROM users WHERE ip = '$escapedIP'";
    $uid = getSingle($conn, $query);
    
    if(!$uid){
        query($conn, "INSERT INTO users(ip) VALUES ('$escapedIP')");
        $uid = mysqli_insert_id($conn); // Get the last inserted ID
    }
    
    return $uid;
}

function renderTweets($conn, $result){
    print "<table>";
    while ($row = mysqli_fetch_assoc($result)) {
        $uid = $row['uid'];
        $post = $row['post'];
        $date = $row['date'];
        if (!getSingle($conn, "SELECT follower FROM follows WHERE uid = $uid AND follower = $uid")) {
            $follow = <<<EOF
            <a href="index.php?follow=$uid">Follow</a>
EOF;
        } else {
            $follow = "Followed";
        }
        print <<<EOF
        <tr><td>$uid</td><td>$post</td><td>$date</td><td>$follow</td></tr>
EOF;
    }
    print "</table>";
}

if ($_REQUEST['follow']) {
    $follow = mysqli_real_escape_string($conn, $_REQUEST['follow']);
    $uid = getUID($conn, $_SERVER['REMOTE_ADDR']);
    query($conn, "INSERT IGNORE INTO follows(uid, follower) VALUES ($uid, '$follow')");
}

if(isset($_REQUEST['tweet'])){
    $tweet = $_REQUEST['tweet'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $uid = getUID($conn, $ip); // Retrieve or insert user ID based on IP
    
    $date = date("Y-m-d H:i:s"); // Current date and time
    
    query($conn, "INSERT INTO tweets(uid, post, date) VALUES ($uid, '$tweet', '$date')");
    
    print "$tweet, $ip";
}

print <<<EOF
<form action="index.php">
    <textarea name="tweet"></textarea>
    <input type="submit" value="Tweet">
</form>
EOF;

// Pass $conn as the first argument to query() for fetching tweets
$result = query($conn, "SELECT * FROM tweets ORDER BY date DESC");
renderTweets($conn, $result);
print "<HR>";
print "<h1>Followed Users</h1>";
// Retrieve tweets from followed users based on the current user's ID ($uid)
$newResult = query($conn, "SELECT t.* FROM tweets t JOIN follows f ON t.uid = f.follower WHERE f.uid = $uid ORDER BY t.date DESC");
renderTweets($conn, $newResult);
?>

</body>
</html>
