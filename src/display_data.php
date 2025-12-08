<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; // Your database password (keep empty if you reset it)
$dbname = "esp_el";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the latest 20 readings
$sql = "SELECT id, temp, humid, readtime FROM sensorreadings ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

// Store data in an array so we can use it multiple times
$readings = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $readings[] = $row;
    }
}

// Get the most recent reading for the top cards (if data exists)
$latest_temp = isset($readings[0]) ? $readings[0]['temp'] : "--";
$latest_humid = isset($readings[0]) ? $readings[0]['humid'] : "--";
$latest_time = isset($readings[0]) ? $readings[0]['reading_time'] : "--";

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
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }

        /* --- Top Cards Container --- */
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            width: 100%;
            max-width: 800px;
            justify-content: center;
            flex-wrap: wrap; /* Wraps on small screens */
        }

        /* --- Individual Card Style --- */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            width: 200px;
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
        }

        .card h3 {
            margin: 0;
            color: #7f8c8d;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card .value {
            font-size: 3rem;
            font-weight: 600;
            margin: 10px 0;
            color: #2c3e50;
        }

        .card .unit {
            font-size: 1.2rem;
            color: #95a5a6;
        }

        /* Specific Colors for Temp and Humidity */
        .temp-color { color: #e74c3c !important; } /* Red for Temp */
        .humid-color { color: #3498db !important; } /* Blue for Humidity */

        /* --- Table Styling --- */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px;
            width: 100%;
            max-width: 800px;
            overflow-x: auto; /* Scrollable on small screens */
        }

        h2.history-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #34495e;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #fff;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* --- Last Updated Text --- */
        .last-updated {
            margin-top: 10px;
            color: #95a5a6;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <h1>🚀 ESP32 Monitor</h1>

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
    </div>
    
    <div class="last-updated">
        Last Reading: <?php echo $latest_time; ?>
    </div>

    <br>

    <div class="table-container">
        <h2 class="history-title">Recent History</h2>
        <table>
            <thead>
                <tr>
                    <th>Reading ID</th>
                    <th>Temperature</th>
                    <th>Humidity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($readings) > 0) {
                    foreach ($readings as $row) {
                        echo "<tr>";
                        echo "<td>#" . $row["id"] . "</td>";
                        echo "<td>" . $row["temp"] . " °C</td>";
                        echo "<td>" . $row["humid"] . " %</td>";
                        echo "<td>" . $row["reading_time"] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;'>No data available yet...</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>