#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

// --- WIFI SETTINGS ---
const char* ssid = "Octadex Prime";      // <--- CHANGE THIS
const char* password = "meitantei@2060"; // <--- CHANGE THIS

// --- SERVER SETTINGS ---
// Replace 192.168.x.x with your Laptop's IP address (Run 'ipconfig' in cmd)
String serverBase = "http://192.168.70.104:8080/"; 

// --- PIN DEFINITIONS ---
#define DHTPIN 4
#define DHTTYPE DHT11     // <--- Added this line!
#define SOIL_PIN_1 34     // First Sensor (ADC1)
#define SOIL_PIN_2 35     // Second Sensor (ADC1)
#define PUMP_PIN 26       // Pump Transistor

// --- CALIBRATION THRESHOLDS (Capacitive) ---
// High Number = Dry, Low Number = Wet
const int DRY_THRESHOLD = 2100; // Trigger watering above this
const int WET_THRESHOLD = 1500; // Stop watering below this
const int PUMP_ON_SPEED = 4095; // Max Speed

// --- TIMERS ---
unsigned long lastTime = 0;
unsigned long timerDelay = 5000;    // Upload data every 5 seconds
unsigned long lastPumpTime = 0;
unsigned long pumpInterval = 1000;  // Check pump logic every 1 second

// --- OBJECTS ---
DHT dht(DHTPIN, DHTTYPE);
int manualPumpCommand = 0; // 0=OFF/Disabled, 1=ON/Auto-Active

// --- PWM PROPERTIES ---
const int freq = 5000;
const int pwmChannel = 0;
const int resolution = 12;

void setup() {
  Serial.begin(115200);

  // Initialize Sensors
  dht.begin();
  pinMode(SOIL_PIN_1, INPUT);
  pinMode(SOIL_PIN_2, INPUT);

  // Initialize Pump (PWM)
  ledcSetup(pwmChannel, freq, resolution);
  ledcAttachPin(PUMP_PIN, pwmChannel);
  ledcWrite(pwmChannel, 0); // Start OFF

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  unsigned long currentMillis = millis();

  // ============================================================
  // TASK 1: MASTER PUMP CONTROL (Every 1 Second)
  // ============================================================
  if (currentMillis - lastPumpTime >= pumpInterval) {
    lastPumpTime = currentMillis;
    
    // 1. Read Both Soil Sensors
    int soil1 = analogRead(SOIL_PIN_1);
    int soil2 = analogRead(SOIL_PIN_2);

    // 2. Identify the DRIEST plant (Highest Value = Driest for Capacitive)
    int driestValue = (soil1 > soil2) ? soil1 : soil2;

    // 3. Fetch Master Command from Website (System ON or OFF?)
    if(WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.begin(serverBase + "pump_control.php"); 
      int httpCode = http.GET();
      
      if (httpCode > 0) {
        String payload = http.getString();
        manualPumpCommand = payload.toInt(); 
      }
      http.end();
    }

    // 4. Apply Logic
    if (manualPumpCommand == 1) {
        // --- SYSTEM ACTIVE (AUTO MODE) ---
        // If the driest plant is too dry, turn ON the pump
        if (driestValue > DRY_THRESHOLD) {
            ledcWrite(pwmChannel, PUMP_ON_SPEED); 
            Serial.printf("PUMP ON | Driest: %d (S1:%d, S2:%d)\n", driestValue, soil1, soil2);
        } 
        // If the driest plant is now wet enough, turn OFF
        else if (driestValue < WET_THRESHOLD) {
            ledcWrite(pwmChannel, 0);
            Serial.printf("PUMP OFF | Soil Good (Driest: %d)\n", driestValue);
        }
        else {
             Serial.printf("MONITORING | Driest: %d\n", driestValue);
        }
    } else {
        // --- SYSTEM DISABLED ---
        ledcWrite(pwmChannel, 0); // Force OFF
        Serial.println("System DISABLED (Switch is OFF)");
    }
  }

  // ============================================================
  // TASK 2: UPLOAD SENSOR DATA (Every 5 Seconds)
  // ============================================================
  if (currentMillis - lastTime >= timerDelay) {
    lastTime = currentMillis;
    
    // Read Sensors
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    int s1 = analogRead(SOIL_PIN_1);
    int s2 = analogRead(SOIL_PIN_2);

    // Check if DHT read failed
    if (isnan(t) || isnan(h)) {
      Serial.println("Failed to read from DHT sensor!");
      return; 
    }

    // Send Data
    if(WiFi.status() == WL_CONNECTED){
      HTTPClient http;
      // Send 4 variables: temperature, humidity, soil, soil2
      String serverPath = serverBase + "insert_dht_data.php?temperature=" + String(t) + "&humidity=" + String(h) + "&soil=" + String(s1) + "&soil2=" + String(s2);
      
      Serial.println("Uploading: " + serverPath);
      
      http.begin(serverPath.c_str());
      int httpResponseCode = http.GET();
      
      if (httpResponseCode > 0) {
        Serial.print("HTTP Response code: ");
        Serial.println(httpResponseCode);
      } else {
        Serial.print("Error code: ");
        Serial.println(httpResponseCode);
      }
      http.end();
    }
  }
}