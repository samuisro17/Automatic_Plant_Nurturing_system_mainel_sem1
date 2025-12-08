#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

// --- Configuration ---
// Replace with your network credentials
const char* ssid = "Octadex Prime";
const char* password = "meitantei@2060";

// Replace with your local server's IP address and PHP script path
// Example: http://192.168.1.100/insert_dht_data.php
String serverName = "http://192.168.70.104/insert_dht_data.php"; 

#define DHTPIN 4   // Digital pin connected to the DHT11 data pin
#define DHTTYPE DHT11 // DHT 11 Sensor Type

// Initialize DHT sensor.
DHT dht(DHTPIN, DHTTYPE);

// Time to wait between sending readings (in milliseconds)
const long readingDelay = 30000; // 30 seconds
unsigned long lastSendTime = 0;

// Function prototypes (necessary for PlatformIO/C++)
void connectToWiFi();
void sendDataToDatabase(float tempC, float hum);

// --- Setup Function ---
void setup() {
  Serial.begin(115200);
  dht.begin();
  connectToWiFi();
}

// --- Loop Function ---
void loop() {
  // Check if it's time to send data
  if (millis() - lastSendTime >= readingDelay) {
    lastSendTime = millis();
    
    // Read sensor data
    float humidity = dht.readHumidity();
    float temperature = dht.readTemperature(); // Celsius
    
    // Check if readings failed
    if (isnan(humidity) || isnan(temperature)) {
      Serial.println("Failed to read from DHT sensor! Retrying in 30 seconds.");
      return;
    }
    
    Serial.printf("Temperature: %.1f °C, Humidity: %.1f %%\n", temperature, humidity);

    // Send data to the database
    sendDataToDatabase(temperature, humidity);
  }
}

// --- Helper Functions ---

void connectToWiFi() {
  Serial.print("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  // Wait for connection
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }
  
  Serial.println("\nConnected to WiFi!");
  Serial.print("Local IP address: ");
  Serial.println(WiFi.localIP());
}

void sendDataToDatabase(float tempC, float hum) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Construct the full URL with GET parameters
    String serverPath = serverName + "?temperature=" + String(tempC) + "&humidity=" + String(hum);
    
    Serial.print("Sending to: ");
    Serial.println(serverPath);

    http.begin(serverPath.c_str());
    
    // Send HTTP GET request
    int httpResponseCode = http.GET();
    
    if (httpResponseCode > 0) {
      Serial.printf("HTTP Response code: %d\n", httpResponseCode);
      String payload = http.getString();
      Serial.printf("Server Response: %s\n", payload.c_str()); // Response from PHP script
    } else {
      Serial.printf("Error code: %d\n", httpResponseCode);
    }
    
    // Free resources
    http.end();
  } else {
    Serial.println("WiFi not connected. Attempting to reconnect...");
    connectToWiFi(); // Try to reconnect if connection is lost
  }
}