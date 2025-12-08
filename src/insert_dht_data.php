<?php
// PHP Script to insert data into MySQL (XAMPP/WAMP/MAMP)
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "esp_el"; // Ensure this matches your database name
 
if(isset($_GET["temperature"]) && isset($_GET["humidity"])) {
    $temperature = $_GET["temperature"];
    $humidity = $_GET["humidity"];
 
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        // HTTP 500 status code on connection failure
        http_response_code(500); 
        die("Connection failed: " . $conn->connect_error);
    } 
    
    $sql = "INSERT INTO sensor_readings (temperature, humidity) 
            VALUES ('" . $temperature . "', '" . $humidity . "')";
    
    if ($conn->query($sql) === TRUE) {
        echo "OK"; // Simple success response for the ESP32
    } else {
        echo "Error: " . $conn->error;
    }
    
    $conn->close();
} else {
    echo "Missing parameters";
}
?>