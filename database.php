<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "hotel_room_booking_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>