<?php
// --- CONFIGURATION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "esp_el1"; // Make sure this matches your database name

// --- CONNECT TO DATABASE ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- 1. HANDLE WEBSITE COMMANDS (The Switch) ---
if (isset($_GET['state'])) {
    $state = $_GET['state']; // 1 = ON, 0 = OFF
    
    // Update the database
    $sql = "UPDATE pump_control SET state=$state WHERE id=1";
    
    if ($conn->query($sql) === TRUE) {
        echo "Success: State updated to " . $state;
    } else {
        echo "Error: " . $conn->error;
    }
}

// --- 2. HANDLE ESP32 REQUESTS (The Pump) ---
// If no parameters are passed, just read the current state
if (!isset($_GET['state'])) {
    $result = $conn->query("SELECT state FROM pump_control WHERE id=1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['state']; // Output just "1" or "0" for the ESP32
    } else {
        echo "0"; // Default to OFF if table is empty
    }
}

$conn->close();
?>