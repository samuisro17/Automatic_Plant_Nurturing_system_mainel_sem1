<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "esp_el1";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Check if all data is present
if(isset($_GET['temperature']) && isset($_GET['humidity']) && isset($_GET['soil']) && isset($_GET['soil2'])) {
    
    $temp = $_GET['temperature'];
    $hum = $_GET['humidity'];
    $soil = $_GET['soil'];
    $soil2 = $_GET['soil2']; // New Variable

    // Insert both soil values
    $sql = "INSERT INTO sensor_readings (temp, humid, soil, soil2) VALUES ('$temp', '$hum', '$soil', '$soil2')";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Missing data parameters";
}

$conn->close();
?>