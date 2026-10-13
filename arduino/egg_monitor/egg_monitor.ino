/*
  Egg Storage Monitor - ESP8266 NodeMCU firmware (Arduino IDE)

  What it does, every 10 seconds:
    1. reads temperature + humidity from the DHT22
    2. reads the air-quality value from the MQ-135 (raw 0-1023, not ppm)
    3. sends them as JSON to the PHP API on your laptop (XAMPP)

  Wiring (see GUIDE.md for the full table and the MQ-135 voltage divider):
    DHT22  VCC -> 3V3,  DATA -> D2,  GND -> GND
    MQ-135 VCC -> VIN (5V),  GND -> GND,  AO -> divider -> A0

  Libraries to install (Sketch > Include Library > Manage Libraries):
    "DHT sensor library" by Adafruit  (also install "Adafruit Unified Sensor" when asked)
*/

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <DHT.h>
#include "secrets.h"            // WIFI_SSID, WIFI_PASS, SERVER_URL, API_KEY, DEVICE_ID

#define DHTPIN      D2          // DHT22 data pin
#define DHTTYPE     DHT22
#define MQ135_PIN   A0          // analog input

const unsigned long SEND_INTERVAL_MS = 10000;   // send a reading every 10 seconds

DHT dht(DHTPIN, DHTTYPE);
WiFiClient wifiClient;
unsigned long lastSend = 0;

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi connected. NodeMCU IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi FAILED. Check the name/password in secrets.h (2.4 GHz networks only).");
  }
}

int readAirQuality() {
  long sum = 0;
  for (int i = 0; i < 10; i++) {      // average 10 samples to smooth the noise
    sum += analogRead(MQ135_PIN);
    delay(10);
  }
  return (int)(sum / 10);
}

void sendReading() {
  float h = dht.readHumidity();
  float t = dht.readTemperature();    // degrees Celsius

  if (isnan(h) || isnan(t)) {
    Serial.println("DHT22 read failed. Check the wiring on D2 and the 3V3 / GND pins.");
    return;
  }
  int air = readAirQuality();

  char payload[160];
  snprintf(payload, sizeof(payload),
           "{\"device_id\":\"%s\",\"temperature\":%.1f,\"humidity\":%.1f,\"air_quality\":%d}",
           DEVICE_ID, t, h, air);

  Serial.print("Sending: ");
  Serial.println(payload);

  HTTPClient http;
  http.begin(wifiClient, SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  int code = http.POST(payload);
  if (code > 0) {
    Serial.print("Server replied ");
    Serial.print(code);                 // 201 = saved, 401 = wrong API key, 422 = bad data
    Serial.print(": ");
    Serial.println(http.getString());
  } else {
    Serial.print("HTTP error: ");
    Serial.println(http.errorToString(code));   // usually a wrong IP, firewall, or different WiFi
  }
  http.end();
}

void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println();
  Serial.println("Egg Storage Monitor starting...");
  dht.begin();
  connectWiFi();
  lastSend = millis() - SEND_INTERVAL_MS;       // makes the first reading go out straight away
}

void loop() {
  connectWiFi();                                 // reconnects if the WiFi dropped
  if (WiFi.status() == WL_CONNECTED && millis() - lastSend >= SEND_INTERVAL_MS) {
    lastSend = millis();
    sendReading();
  }
}
