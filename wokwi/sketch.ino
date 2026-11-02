/*
  Egg Storage Monitor - SIMULATOR version for Wokwi (ESP32)

  This is the same logic as arduino/egg_monitor/egg_monitor.ino, adapted for the simulator:
    - Wokwi has no ESP8266, so an ESP32 stands in for the NodeMCU
    - Wokwi has no MQ-135, so a potentiometer on GPIO34 stands in for it (turn it to change the
      "air quality" value)
    - the simulated Wi-Fi is "Wokwi-GUEST" and reaches the internet, so the data goes through a
      secure tunnel to the XAMPP server on your laptop (see WOKWI_GUIDE.md)

  Fill in the two lines marked CHANGE before pressing Play.
*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include "DHTesp.h"

const char* WIFI_SSID    = "Wokwi-GUEST";   // Wokwi's built-in open Wi-Fi
const char* WIFI_PASS    = "";
const int   WIFI_CHANNEL = 6;               // skips the Wi-Fi scan, connects faster

// CHANGE 1: your tunnel address + /api/telemetry.php  (must start with https://)
const char* SERVER_URL = "https://CHANGE-ME.trycloudflare.com/api/telemetry.php";
// CHANGE 2: must be exactly the same as API_KEY in your config.php
const char* API_KEY    = "CHANGE-ME";

const char* DEVICE_ID  = "wokwi-sim-01";
const int   DHT_PIN    = 15;                // DHT22 data pin
const int   AIR_PIN    = 34;                // potentiometer (stand-in for the MQ-135)
const unsigned long SEND_INTERVAL_MS = 10000;

DHTesp dht;
unsigned long lastSend = 0;

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  Serial.print("Connecting to WiFi");
  WiFi.begin(WIFI_SSID, WIFI_PASS, WIFI_CHANNEL);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(250);
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi connected. IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi FAILED.");
  }
}

int readAirQuality() {
  long sum = 0;
  for (int i = 0; i < 10; i++) {            // average 10 samples
    sum += analogRead(AIR_PIN);
    delay(5);
  }
  int raw = (int)(sum / 10);                // ESP32 gives 0-4095
  return map(raw, 0, 4095, 0, 1023);        // scale to 0-1023 like the NodeMCU's A0
}

void sendReading() {
  TempAndHumidity d = dht.getTempAndHumidity();
  if (dht.getStatus() != 0 || isnan(d.temperature) || isnan(d.humidity)) {
    Serial.print("DHT22 read failed: ");
    Serial.println(dht.getStatusString());
    return;
  }
  int air = readAirQuality();

  char payload[160];
  snprintf(payload, sizeof(payload),
           "{\"device_id\":\"%s\",\"temperature\":%.1f,\"humidity\":%.1f,\"air_quality\":%d}",
           DEVICE_ID, d.temperature, d.humidity, air);
  Serial.print("Sending: ");
  Serial.println(payload);

  WiFiClientSecure client;
  client.setInsecure();                     // demo only: skips certificate checking
  HTTPClient http;
  http.setTimeout(10000);
  if (!http.begin(client, SERVER_URL)) {
    Serial.println("Bad SERVER_URL.");
    return;
  }
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  int code = http.POST(payload);
  if (code > 0) {
    Serial.print("Server replied ");
    Serial.print(code);                     // 201 = saved, 401 = wrong key, 422 = bad data
    Serial.print(": ");
    Serial.println(http.getString());
  } else {
    Serial.print("HTTP error: ");
    Serial.println(http.errorToString(code));
  }
  http.end();
}

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.println("Egg Storage Monitor (simulator) starting...");
  dht.setup(DHT_PIN, DHTesp::DHT22);
  connectWiFi();
  lastSend = millis() - SEND_INTERVAL_MS;   // first reading goes out straight away
}

void loop() {
  connectWiFi();
  if (WiFi.status() == WL_CONNECTED && millis() - lastSend >= SEND_INTERVAL_MS) {
    lastSend = millis();
    sendReading();
  }
}
