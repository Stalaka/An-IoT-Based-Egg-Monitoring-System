# Online Arduino demo with Wokwi (no hardware needed)

Tip: in VS Code press **Ctrl+Shift+V** with this file open to read it formatted.

## The idea in one picture

```
Wokwi (in your browser)          Internet            Your laptop
 ESP32 + DHT22 + potentiometer --HTTPS--> tunnel --> PHP (port 8081) --> MySQL <-- Dashboard (Apache, port 80)
```

Wokwi's simulated Wi-Fi can reach the **internet**, but not your laptop directly (that needs a paid Wokwi plan). So we put a free **secure tunnel** (Cloudflare Quick Tunnel, no account) between the internet and ONE file on your laptop: `api/telemetry.php`. Your login pages, admin pages, `config.php` and phpMyAdmin are **not** exposed. I tested this locally: every other address returns 404, and `telemetry.php` still demands the secret API key.

## Be upfront about these three differences (say them before your lecturer asks)

| Real project | In the simulator | Why |
|---|---|---|
| ESP8266 NodeMCU | **ESP32** | Wokwi's supported boards list does not include the ESP8266. The logic and code are almost the same. |
| MQ-135 gas sensor | **Potentiometer** (turn the knob = air quality changes) | Wokwi has no MQ-135 (it lists only the MQ2). A potentiometer gives the same kind of 0 to 1023 analog value. |
| Real Wi-Fi to your laptop | Wokwi-GUEST Wi-Fi + tunnel | The simulator cannot join your home network. |

The NodeMCU sketch for the real hardware stays in `arduino/egg_monitor/` (your final system). The Wokwi one is in `wokwi/`.

---

## Part A: One-time preparation (about 15 minutes)

1. **Change the API key.** Open `config.php` in VS Code. Change the value of `API_KEY` to a new long random string (for example `egg-2026-` plus 12 random letters and digits). Save. Reason: the key will be written inside your Wokwi project, so do not reuse a key you use anywhere else.
2. Start **Apache** and **MySQL** in XAMPP. Open `http://localhost/egg-monitor/` and log in as admin. Open **Dashboard**. It should load (it will say "No readings yet.").
3. Download the tunnel program: https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe . Save it in a simple folder such as `C:\cloudflared\`. If Windows SmartScreen warns you, click **More info** > **Run anyway** (the file comes from Cloudflare's official GitHub).

## Part B: Start the two helpers (do this right before presenting)

Open VS Code's **Terminal** > **New Terminal** twice (the **+** button), so you have two terminals. Leave both open during the demo.

**Terminal 1: the receiving door (PHP, port 8081)**
```
C:\xampp\php\php.exe -S 127.0.0.1:8081 C:\xampp\htdocs\egg-monitor\tunnel_router.php
```
Expected: a line like `Development Server (http://127.0.0.1:8081) started`.

**Terminal 2: the tunnel**
```
cd C:\cloudflared
cloudflared-windows-amd64.exe tunnel --url http://localhost:8081
```
Expected: after a few seconds, a box containing an address like `https://something-random-words.trycloudflare.com`. **Copy that address.** It changes every time you start the tunnel.

**Check it works:** in your browser open that address followed by `/api/telemetry.php`. You should see `{"ok":false,"error":"Use POST"}`. That means the internet can reach your laptop's receiving door. (If you see an error page instead, wait 30 seconds and refresh.)

## Part C: Build the circuit in Wokwi

1. Go to **https://wokwi.com**. Start a **new project** and choose **ESP32** (the Arduino / C++ one). If it offers templates, pick the plain "ESP32" starter. Button names can differ slightly from my wording.
2. You will see three things: the code (`sketch.ino`), the circuit picture, and a Serial Monitor area. If asked to sign in to save, sign in.
3. Click the **+** (add part) button above the circuit picture and add:
   - **DHT22**
   - **Potentiometer** (the round knob one)
4. Wire them by clicking one pin, then the pin it connects to. Drag parts so wires are easy to see.

| Part pin | Connect to (ESP32 board label) |
|---|---|
| DHT22 **VCC** | **3V3** |
| DHT22 **SDA** | **D15** |
| DHT22 **GND** | **GND** (any GND pin) |
| Potentiometer **VCC** | **3V3** |
| Potentiometer **SIG** | **D34** |
| Potentiometer **GND** | **GND** |

(The DHT22's **NC** pin stays empty. It can also show as pin 3.)

5. **Add the library:** open the **Library Manager** (the library tab/icon beside the code, or the `libraries.txt` tab), add `DHT sensor library for ESPx`. This file is also provided as `wokwi/libraries.txt`.
6. **Paste the code:** open `wokwi/sketch.ino` from your project folder in VS Code, select everything (**Ctrl+A**), copy (**Ctrl+C**). In Wokwi click the `sketch.ino` tab, select everything and paste (**Ctrl+V**), replacing what was there.
7. **Edit the two lines marked CHANGE:**
   - `SERVER_URL` -> your tunnel address + `/api/telemetry.php`, for example `https://something-random-words.trycloudflare.com/api/telemetry.php` (must start with `https://`).
   - `API_KEY` -> the new key you put in `config.php` (Part A step 1). They must match exactly.
8. Click the green **Play** button (Start the simulation).

**What you should see in the Serial Monitor:**
```
Egg Storage Monitor (simulator) starting...
Connecting to WiFi....
WiFi connected. IP: 10.10.0.2
Sending: {"device_id":"wokwi-sim-01","temperature":24.0,"humidity":40.0,"air_quality":0}
Server replied 201: {"ok":true,"message":"Reading saved"}
```
(`10.10.0.2` is the address Wokwi's documentation gives for its simulated Wi-Fi.)

## Part D: Show the dashboard receiving the data

1. In your browser open `http://localhost/egg-monitor/dashboard.php` (logged in). Within a few seconds the egg turns yellow, the badge shows **All clear**, and the page says device **wokwi-sim-01** with a **Simulated data** tag.
2. **Make an alert on demand:** in Wokwi click the **DHT22** part. Sliders appear for temperature and humidity. Set temperature to **30**. After the next reading (up to 10 seconds) the dashboard turns red: temperature above the safe limit.
3. Click the **potentiometer** and drag the knob high. The air-quality value rises and, above 400, an air-quality alert appears (the 0 to 1023 scale is the same as the NodeMCU's A0 pin).
4. Open phpMyAdmin > `egg_monitor` > `telemetry_logs` > **Browse** to show the rows saved from the simulator.

## Part E: What to say (30 seconds)

"The physical layer of my proposal is an ESP8266 with a DHT22 and an MQ-135. Because I am demonstrating online, I used Wokwi, which simulates an ESP32 with the DHT22. A potentiometer stands in for the MQ-135. The device sends JSON by HTTPS POST with a secret API key to my PHP API, which saves to MySQL, and my dashboard reads it from there. The firmware for the real NodeMCU is in my project folder and I will deploy it once I have the hardware."

## Part F: If something goes wrong

| Problem | Fix |
|---|---|
| Serial Monitor: `HTTP error` or connection refused | The tunnel is not running or the address is old. Restart Terminal 2, copy the **new** address into `SERVER_URL`, press Stop then Play. |
| `Server replied 401` | `API_KEY` in the sketch differs from `config.php`. |
| `Server replied 404` | `SERVER_URL` is missing `/api/telemetry.php` at the end. |
| `Server replied 422` | A value is out of range (check the DHT22 sliders). |
| Dashboard says "The device is offline." although Wokwi is sending | The simulator may run slower than real time. In `config.php` change `DEVICE_STALE_SECONDS` from `60` to `120`. |
| Nothing at all in Wokwi's Wi-Fi line | Check the line says `Wokwi-GUEST` exactly, with channel 6. |
| Tunnel will not start | Check you have internet. As a last resort use **Simulator** in the admin menu (it saves a fake reading directly) and say clearly that it is simulated. |

## Part G: After the demo

- Close both terminals (this shuts the tunnel, so the door to your laptop closes).
- Change the admin password (**Users** > **Edit**).
- Do not share the Wokwi project link publicly while it still contains your API key. Change `API_KEY` in `config.php` afterwards.

## Honest limits

- This is a **simulation**. Say so. The values come from sliders, not from a real egg store.
- The sketch skips HTTPS certificate checking (`setInsecure()`), which is acceptable for a demo only. A real deployment should verify certificates.
- Wokwi's public Wi-Fi gateway is monitored, so never send real personal or business data through it.
- Quick Tunnels are meant for testing, have no uptime guarantee, and give a new address every time.
