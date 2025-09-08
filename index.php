<?php
//echo"Today is". date("l,F,j,Y")  . "<br>";

$servername = "localhost:3306";
$username = "root";
$password = "root";
$dbname   = "pro";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to MariaDB";

