<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "esp_el1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. FETCH SENSOR DATA
$sql = "SELECT id, temp, humid, soil, readtime FROM sensor_readings ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

$readings = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $readings[] = $row;
    }
}

// Get latest values safely
$latest_temp = isset($readings[0]) ? $readings[0]['temp'] : "--";
$latest_humid = isset($readings[0]) ? $readings[0]['humid'] : "--";
$latest_time = isset($readings[0]) ? $readings[0]['readtime'] : "--";
$latest_soil = isset($readings[0]) ? $readings[0]['soil'] : "--";

// 2. FETCH CURRENT PUMP STATE (SYNC LOGIC ADDED HERE)
$pumpSql = "SELECT state FROM pump_control WHERE id=1";
$pumpResult = $conn->query($pumpSql);
$currentPumpState = 0; // Default to OFF

if ($pumpResult && $pumpResult->num_rows > 0) {
    $pRow = $pumpResult->fetch_assoc();
    $currentPumpState = $pRow['state'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Environment Monitor</title>
    <meta http-equiv="refresh" content="5">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* --- General Styling --- */
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; color: #333; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        h1 { color: #2c3e50; margin-bottom: 30px; font-weight: 600; text-align: center; }

        /* --- Dashboard Cards --- */
        .dashboard-cards { display: flex; gap: 20px; margin-bottom: 40px; width: 100%; max-width: 800px; justify-content: center; flex-wrap: wrap; }
        .card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 20px; text-align: center; width: 200px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { margin: 0; color: #7f8c8d; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        .card .value { font-size: 3rem; font-weight: 600; margin: 10px 0; color: #2c3e50; }
        .card .unit { font-size: 1.2rem; color: #95a5a6; }
        .temp-color { color: #e74c3c !important; }
        .humid-color { color: #3498db !important; } 
        .soil-color { color: #f39c12 !important; }

        /* --- Control Panel (Toggle Only) --- */
        .control-card { background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 20px; text-align: center; width: 100%; max-width: 420px; margin-bottom: 30px; }

        /* Toggle Switch CSS */
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2196F3; }
        input:checked + .slider:before { transform: translateX(26px); }

        /* --- Table Styling --- */
        .table-container { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; width: 100%; max-width: 800px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #fff; color: #7f8c8d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f9f9f9; }
        .last-updated { margin-top: 10px; color: #95a5a6; font-size: 0.9rem; }
    </style>
</head>
<body>

    <h1>🚀 Environment & Irrigation Monitor</h1>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Temperature</h3>
            <div class="value temp-color">
                <?php echo $latest_temp; ?>
                <span class="unit">°C</span>
            </div>
        </div>
        
        <div class="card">
            <h3>Humidity</h3>
            <div class="value humid-color">
                <?php echo $latest_humid; ?>
                <span class="unit">%</span>
            </div>
        </div>
        
        <div class="card">
            <h3>Soil Moisture</h3>
            <div class="value soil-color">
                <?php echo $latest_soil; ?>
                <span class="unit">ADC</span>
            </div>
        </div>
    </div>
    
    <div class="control-card">
        <h3>💧 Water Pump Control</h3>
        
        <label class="switch">
            <input type="checkbox" id="pumpToggle" onchange="togglePump(this)" 
            <?php if($currentPumpState == 1) echo "checked"; ?>>
            <span class="slider"></span>
        </label>
        
        <p id="status-text" style="font-weight: bold; margin-top: 10px; color: <?php echo ($currentPumpState == 1) ? 'green' : '#555'; ?>">
            Status: <?php echo ($currentPumpState == 1) ? "Manual ON 🌊" : "Manual OFF"; ?>
        </p>
    </div>

    <script>
        function togglePump(checkbox) {
            var state = checkbox.checked ? 1 : 0;
            var statusText = document.getElementById("status-text");
            
            // Visual feedback immediately
            if(state == 1) {
                statusText.innerHTML = "Status: Manual ON 🌊";
                statusText.style.color = "green";
            } else {
                statusText.innerHTML = "Status: Manual OFF";
                statusText.style.color = "#555";
            }

            // Send to PHP
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "pump_control.php?state=" + state, true);
            xhr.send();
        }
    </script>
    
    <div class="last-updated">
        Last Reading: <?php echo $latest_time; ?>
    </div>

    <br>

    <div class="table-container">
        <h2 class="history-title">Recent History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Temp (°C)</th>
                    <th>Humid (%)</th>
                    <th>Soil (ADC)</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($readings) > 0) {
                    foreach ($readings as $row) {
                        echo "<tr>";
                        echo "<td>#" . $row["id"] . "</td>";
                        echo "<td>" . $row["temp"] . "</td>";
                        echo "<td>" . $row["humid"] . "</td>";
                        echo "<td>" . $row["soil"] . "</td>";
                        echo "<td>" . $row["readtime"] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No data available yet...</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>