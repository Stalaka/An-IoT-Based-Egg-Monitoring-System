# Egg Storage Monitor: Step-by-Step Guide

Tip: in VS Code, press **Ctrl+Shift+V** while this file is open to see it nicely formatted.

## What your lecturer asked to see, and where it is in this guide

| Lecturer's item | Part |
|---|---|
| Setup | Part 1 |
| Where the code is saved | Part 1 (step 4) and Part 6 |
| Database | Part 2 |
| Login and register | Part 3 |
| Add a user and password | Part 4 |
| CRUD | Part 5 |
| Git | Part 6 |
| Dashboard receiving API integrations | Part 7 |
| Arduino IDE | Part 8 |

**What I could and could not test:** the PHP code, the database and the API were run and tested end to end (register, login, roles, CRUD, sales, API, dashboard, alerts). The Windows steps (XAMPP, Git, Arduino IDE) and the real ESP8266 with sensors could **not** be tested here, so do a full dry run before the demo.

---

## Part 0: Install these first

1. **XAMPP** (Apache + MySQL): https://www.apachefriends.org
2. **Visual Studio Code**: https://code.visualstudio.com
3. **Git for Windows**: https://git-scm.com (accept the default options)
4. **Arduino IDE 2.x**: https://www.arduino.cc/en/software
5. A free **GitHub** account: https://github.com

---

## Part 1: Setup (XAMPP, project folder, VS Code)

**Expected result:** the folder is open in VS Code, and Apache and MySQL show green in XAMPP.

1. Open **XAMPP Control Panel**. Click **Start** next to **Apache** and **Start** next to **MySQL**. Both names turn green and show a port number. If Windows asks about the firewall, click **Allow access**.
2. Unzip `egg-monitor.zip`. Copy the whole `egg-monitor` folder into `C:\xampp\htdocs\`.
3. Check the path is exactly `C:\xampp\htdocs\egg-monitor\login.php`. If you see `egg-monitor\egg-monitor\login.php`, you copied one level too deep.
4. Open **VS Code** > **File** > **Open Folder...** > choose `C:\xampp\htdocs\egg-monitor` > **Select Folder**. If asked, click **Yes, I trust the authors**.

**Where is the code saved? (say this to your lecturer)**

| What | Where |
|---|---|
| Website code (PHP) | `C:\xampp\htdocs\egg-monitor`. `htdocs` is the folder Apache serves, so a file there is a web page. |
| Arduino code | `C:\xampp\htdocs\egg-monitor\arduino\egg_monitor\egg_monitor.ino` (same project, same Git repo) |
| Database *structure* | `database\schema.sql` (saved in Git) |
| Database *data* | Inside MySQL, in `C:\xampp\mysql\data`. Not in Git, which is why `schema.sql` exists. |
| Online backup | Your GitHub repository (Part 6) |

---

## Part 2: Database

**Expected result:** a database called `egg_monitor` with 4 tables.

1. In your browser open **http://localhost/phpmyadmin**.
2. Click the **Import** tab at the top.
3. Click **Choose File** and select `C:\xampp\htdocs\egg-monitor\database\schema.sql`.
4. Scroll to the bottom and click **Import** (some versions say **Go**).
5. You should see a green message that the import finished. In the left sidebar, **egg_monitor** now appears.
6. Click **egg_monitor**. You should see 4 tables: `users`, `stock_registers`, `sales_logs`, `telemetry_logs`.
7. Click **users** > **Browse**. There is one row, `admin@egg.local`. Look at the `password_hash` column: it starts with `$2y$10$`. That is a scrambled hash, not the real password.

**What we did:** these 4 tables are exactly the ones named in your proposal (section 3.4). `sales_logs` links to `users` and `stock_registers` with foreign keys, so a sale cannot point to a stock batch or user that does not exist.

**If MySQL has a root password:** open `config.php` in VS Code and put it in `DB_PASS`.

---

## Part 3: Login and register

**Expected result:** you can create an account, log in, and see a menu that depends on your role.

1. Open **http://localhost/egg-monitor/**. You are sent to the login page.
2. Click **Register here**. Fill in **Full name**, **Email**, **Password (min 8 characters)**, **Confirm password**. Click **Register**. You should see "Account created. You can now log in."
3. Log in with that email and password. You land on the **Dashboard**. The menu shows only **Dashboard** and **Sales**, because public sign-up always creates a **vendor**.
4. Click **Logout**. Log in as admin: `admin@egg.local` / `Admin@123`. The menu now shows **Dashboard, Stock, Sales, Users, Simulator**.

**What we did (in simple terms):**
- Passwords are stored with `password_hash()` and checked with `password_verify()`. Nobody, including you, can read a stored password.
- After login, PHP keeps you logged in with a **session**.
- Each page checks your **role**. Try opening `http://localhost/egg-monitor/users.php` while logged in as the vendor: you get "Access denied".
- Every form has a hidden **CSRF token** so another website cannot submit forms for you.
- All database queries use **prepared statements**, which blocks SQL injection.

Change the admin password after the demo (Users > Edit).

---

## Part 4: Add a user and password

**Expected result:** a new user appears in the table, and the database shows only a hash.

1. Log in as admin. Click **Users** in the menu.
2. In the **Add user** card fill: **Full name**, **Email**, **Role** (admin, vendor or supplier), **Password (min 8)**.
3. Click **Add user**. You should see "User added." and the person appears under **All users**.
4. Go to phpMyAdmin > `egg_monitor` > `users` > **Browse**. Show your lecturer the `password_hash` column.
5. Back on the Users page, click **Edit** on a row. Change the role, or type a **New password**, and click **Save changes**.
6. Click **Delete** on a row. A page asks "Delete this user?". Click **Yes, delete** (or **Cancel**).

Safety rules built in: you cannot delete yourself, cannot remove your own admin role, and cannot delete a user who already recorded sales.

---

## Part 5: CRUD (Create, Read, Update, Delete)

| Page | Create | Read | Update | Delete |
|---|---|---|---|---|
| **Users** (admin) | Add user | All users table | Edit | Delete |
| **Stock** (admin, supplier) | Add batch | Stock register | Edit | Delete |
| **Sales** (admin, vendor) | Save sale | Recent sales | no | no |

Sales cannot be edited or deleted on purpose. A sales record is a ledger: if it could be changed, the money records could not be trusted.

**Stock demo:**
1. **Stock** > fill **Batch reference**, **Crates in stock**, **Price per crate (KES)**, **Low-stock alert at (crates)**, **Date received** > **Add batch**.
2. Click **Edit** on the batch, change the crates, **Save changes**.
3. A batch at or below its alert level shows a red **LOW** badge.

**Sales demo:**
1. **Sales** > choose a **Stock batch** > type **Crates sold** > **Save sale**.
2. You see "Sale recorded" and the total in KES. Go to **Stock**: the crates dropped by the amount sold.
3. Try selling more crates than exist: it is refused with "Not enough stock".

**What we did:** saving a sale and reducing stock happen inside one database **transaction**. Both succeed together or neither happens, so stock and sales can never disagree.

---

## Part 6: Git (version control)

**Expected result:** your code is on GitHub, and your passwords are not.

Open VS Code's terminal: **Terminal** > **New Terminal**. It opens in the project folder.

**A. Tell Git who you are (once per computer)**
```
git config --global user.name "Your Full Name"
git config --global user.email "your-github-email@example.com"
```

**B. Create the repository**
```
git init
git branch -M main
git status
```
`git status` lists the files. Check that **`config.php`** and **`arduino/egg_monitor/secrets.h`** are **not** in the list. They are hidden by `.gitignore` because they hold passwords and your Wi-Fi details.

**C. First commit**
```
git add .
git commit -m "Initial commit: login, CRUD, dashboard, IoT API, Arduino sketch"
git log --oneline
```
`git log --oneline` shows your commit. A commit is a saved snapshot you can return to.

**D. Put it on GitHub**
1. On github.com click **+** (top right) > **New repository**. Name it `egg-monitor`. Choose **Private**. Do **not** tick "Add a README". Click **Create repository**.
2. Copy the commands GitHub shows under "push an existing repository". They look like:
```
git remote add origin https://github.com/YOUR-USERNAME/egg-monitor.git
git push -u origin main
```
3. A sign-in window opens in your browser. Sign in to GitHub. Refresh the repository page: your files are there.

**E. Show the everyday workflow** (this impresses lecturers)
1. Edit one line in `README.md`, save with **Ctrl+S**.
2. In the terminal:
```
git add .
git commit -m "Update README"
git push
```
3. Refresh GitHub: a second commit appears. Click **Commits** to show the history.

**Why `config.sample.php` and `secrets.sample.h` exist:** someone who downloads your repository copies each sample to the real name and fills in their own values.

---

## Part 7: Dashboard receiving API data

**Expected result:** the dashboard shows live readings and red alerts, refreshing by itself every 5 seconds.

### 7A. Test with no hardware (Simulator)

1. Log in as admin > **Simulator**.
2. Click **Normal preset**. Go to **Dashboard**: green **ALL OK**, temperature 22.5, humidity 65, air 250.
3. Back on **Simulator**, click **Hot / bad-air preset**. The Dashboard shows a red **ALERT** with three messages (temperature too high, humidity too low, air quality too high).
4. If no reading arrives for 60 seconds, the badge turns grey: **DEVICE OFFLINE**.

Tell your audience that Simulator data is simulated. The Simulator writes straight into the `telemetry_logs` table.

### 7B. Test the real API (the same door the ESP8266 uses)

In VS Code's terminal (PowerShell) run this as **one line**:
```
Invoke-RestMethod -Uri "http://localhost/egg-monitor/api/telemetry.php" -Method Post -Headers @{ "X-API-KEY" = "egg-demo-key-2026-change-me" } -ContentType "application/json" -Body '{"device_id":"test-ps","temperature":23.5,"humidity":62,"air_quality":220}'
```
Expected output: `ok: True` and `message: Reading saved`. Within 5 seconds the Dashboard shows device `test-ps` with 23.5 °C.

Change the key in the command to anything else and you get a `401 Bad API key` error. That proves strangers cannot post fake readings.

### 7C. What the API does

`api/telemetry.php` accepts only **POST**, checks the secret **X-API-KEY**, checks the JSON is valid and the numbers are sensible, then saves a row in `telemetry_logs`. The dashboard reads the newest rows from the same table and compares them with the limits in `config.php`.

Also try **http://localhost/egg-monitor/api/latest.php** while logged in. It shows the same data as JSON, which proves the system has an API output too.

**Alert limits** (`TEMP_MAX`, `HUM_MIN`, `HUM_MAX`, `AIR_MAX` in `config.php`) are placeholders. Replace them with values you can justify from your literature review and datasheets.

---

## Part 8: Arduino IDE and the ESP8266

**Expected result:** the NodeMCU sends a reading every 10 seconds and the dashboard shows it.

### 8.1 Add the ESP8266 board to the Arduino IDE
1. Open **Arduino IDE** > **File** > **Preferences**.
2. In **Additional boards manager URLs** paste:
`http://arduino.esp8266.com/stable/package_esp8266com_index.json`
Click **OK**.
3. Click the **Boards Manager** icon on the left (or **Tools** > **Board** > **Boards Manager...**). Search `esp8266`. Install **esp8266 by ESP8266 Community**.

### 8.2 Install the sensor library
**Sketch** > **Include Library** > **Manage Libraries...** > search `DHT sensor library` > install **DHT sensor library by Adafruit**. If it asks to install dependencies, click **Install all** (this adds **Adafruit Unified Sensor**).

### 8.3 Open the sketch
**File** > **Open...** > `C:\xampp\htdocs\egg-monitor\arduino\egg_monitor\egg_monitor.ino`. Two tabs appear: `egg_monitor.ino` and `secrets.h`.

### 8.4 Wire the sensors (power off while wiring)

| Part | Pin | Goes to |
|---|---|---|
| DHT22 | VCC (+) | **3V3** |
| DHT22 | DATA | **D2** |
| DHT22 | GND (-) | **GND** |
| MQ-135 | VCC | **VIN** (5 V) |
| MQ-135 | GND | **GND** |
| MQ-135 | AO | **voltage divider**, then **A0** |

- A bare 4-pin DHT22 needs a 10 kΩ resistor between DATA and VCC. A 3-pin DHT22 module already has it.
- **Voltage divider for the MQ-135:** the MQ-135 can output up to about 5 V, but the NodeMCU's A0 pin only tolerates 3.3 V. Connect a **10 kΩ** resistor from **AO** to **A0**, and a **20 kΩ** resistor from **A0** to **GND**. Without it you risk damaging the board.
- The MQ-135 needs time to warm up (ideally many hours the first time). The value is a **raw 0 to 1023 number, not ppm**, so describe it as a relative air-quality indicator.

### 8.5 Fill in `secrets.h`
1. On your laptop open **Command Prompt** and type `ipconfig`. Find **IPv4 Address** (for example `192.168.43.10`).
2. In the `secrets.h` tab set:
   - `WIFI_SSID` and `WIFI_PASS`: your Wi-Fi name and password
   - `SERVER_URL`: `http://192.168.43.10/egg-monitor/api/telemetry.php` (use your own IP, **not** `localhost`: to the ESP8266, "localhost" means itself)
   - `API_KEY`: must be exactly the same as `API_KEY` in `config.php`
3. Your laptop and the NodeMCU must be on the **same Wi-Fi**. The ESP8266 only supports **2.4 GHz** Wi-Fi.
4. **Windows Firewall:** if the NodeMCU cannot reach the laptop, open **Windows Security** > **Firewall & network protection** > **Allow an app through firewall** > make sure **Apache HTTP Server** has **Private** ticked.
5. **Quick check:** on your phone (same Wi-Fi) open `http://YOUR-IP/egg-monitor/login.php`. If the login page opens, the NodeMCU can reach it too.
6. **Campus Wi-Fi often blocks devices from talking to each other.** If so, switch both your laptop and the NodeMCU to your **phone's hotspot**.

### 8.6 Upload
1. Plug the NodeMCU into the laptop with a **data** USB cable (some cables only charge).
2. **Tools** > **Board** > **ESP8266 Boards** > **NodeMCU 1.0 (ESP-12E Module)**.
3. **Tools** > **Upload Speed** > **115200**.
4. **Tools** > **Port** > choose the new **COM** port. If no port appears, install the **CH340** or **CP2102** USB driver (the chip name is printed on the board).
5. Click the **Upload** arrow (top left). Wait for **Done uploading.**
6. **Tools** > **Serial Monitor**, set the bottom-right speed to **115200 baud**.

### 8.7 What you should see in the Serial Monitor
```
Egg Storage Monitor starting...
Connecting to WiFi: <your wifi>
WiFi connected. NodeMCU IP: 192.168.x.x
Sending: {"device_id":"nodemcu-01","temperature":24.1,"humidity":61.3,"air_quality":187}
Server replied 201: {"ok":true,"message":"Reading saved"}
```
Then open the Dashboard: device `nodemcu-01` appears with real readings.

| Serial Monitor says | Meaning and fix |
|---|---|
| `DHT22 read failed` | Wiring or pin problem on D2, 3V3 or GND |
| `HTTP error: connection refused` | Wrong IP in `SERVER_URL`, Apache not started, or firewall |
| `Server replied 401` | `API_KEY` in `secrets.h` differs from `config.php` |
| `Server replied 422` | Reading out of range (check sensor wiring) |
| `WiFi FAILED` | Wrong name or password, or the network is 5 GHz only |

Your laptop's IP can change each time you reconnect. If readings stop, re-run `ipconfig` and update `SERVER_URL`.

---

## Part 9: What we built, explained (use your proposal's 5 tiers)

| Tier in your proposal | What it is here |
|---|---|
| **Physical layer** | ESP8266 NodeMCU + DHT22 + MQ-135 |
| **Transport layer** | The NodeMCU's Wi-Fi sends JSON over HTTP through your router |
| **Application layer** | Apache (XAMPP) runs the PHP scripts, including `api/telemetry.php` |
| **Data layer** | MySQL database `egg_monitor` with 4 tables |
| **Presentation layer** | The PHP pages in a browser: login, dashboard, stock, sales, users |

| File or folder | Job |
|---|---|
| `config.php` | Database login, API key, alert limits |
| `db.php` | One shared database connection |
| `auth.php` | Sessions, role checks, CSRF tokens |
| `login.php`, `register.php`, `logout.php` | User authentication module |
| `users.php`, `stock.php`, `sales.php` | CRUD pages (sales ledger module) |
| `api/telemetry.php` | IoT telemetry ingestion module |
| `dashboard.php`, `lib.php` | Dashboard alert module (PHP builds the page and the SVG chart) |
| `simulate.php` | Admin test tool for demos without hardware |
| `arduino/egg_monitor/egg_monitor.ino` | ESP8266 firmware |
| `database/schema.sql` | Database structure and starter data |

**About "PHP, not HTML/CSS/JavaScript":** a browser can only display HTML, so PHP writes the HTML for every page. This project uses **no JavaScript at all**: the dashboard refreshes itself with a simple HTML refresh tag, and PHP draws the chart as an SVG picture. A small `assets/style.css` file is only for colours and layout. Your proposal (section 3.5) also lists HTML and CSS, so this matches it.

---

## Part 10: Suggested demo order (about 6 minutes)

1. Show the folder in VS Code and say where the code is saved (Part 1).
2. Show phpMyAdmin: the 4 tables (Part 2).
3. Register a new user, log in, show the vendor's limited menu, log out (Part 3).
4. Log in as admin, add a user with a password, show the hash in phpMyAdmin (Part 4).
5. Do one create, update and delete on Stock, then record a sale (Part 5).
6. Show the terminal: `git log --oneline` and your GitHub page (Part 6).
7. Open the Dashboard, then in Arduino IDE show the sketch and the Serial Monitor sending data, and the dashboard updating (Parts 7 and 8). If the hardware misbehaves, use the Simulator and say clearly that it is simulated.

---

## Part 11: Questions a lecturer may ask

- **Why PHP and MySQL?** They are what your proposal chose (section 3.5), run free on XAMPP, and suit a low-cost system for small sellers.
- **Why does the ESP8266 use HTTP POST with JSON?** It is simple, works on the ESP8266's built-in Wi-Fi, and any PHP server can receive it.
- **How do you stop strangers sending fake readings?** The API requires the secret `X-API-KEY` header.
- **How are passwords protected?** They are hashed with `password_hash()`. The database never holds the real password.
- **Why no HTTPS?** This is a local network prototype on XAMPP. A live deployment would add HTTPS (and the ESP8266 would then need certificate handling).
- **What does the MQ-135 value mean?** A raw reading, not calibrated ppm. It shows change in air quality, not an exact ammonia amount.
- **What if the Wi-Fi drops?** The sketch reconnects automatically. Readings during the outage are lost (listed as a limitation in your proposal).

---

## Part 12: Quick fixes

| Problem | Fix |
|---|---|
| Apache will not start | Another program uses port 80 (often Skype or IIS). Close it, or change Apache's port in XAMPP > Config. |
| "Database error" or blank page | MySQL is not started, or `schema.sql` was not imported (Part 2). |
| `404 Not Found` | The folder is not at `C:\xampp\htdocs\egg-monitor`. |
| Cannot log in as admin | Re-import `schema.sql`, or use the password you set after the demo. |
| Dashboard says NO DATA YET | No reading saved yet. Use the Simulator. |
| Dashboard says DEVICE OFFLINE | Nothing arrived in the last 60 seconds. Check the Serial Monitor. |
| `git push` is rejected | Check the `git remote add origin` URL and that you are signed in to GitHub. |

---

## Part 13: Honest limits (mention these before they ask)

- The alert limits are placeholders and need justification from your literature.
- No HTTPS and no device-level authentication beyond one shared API key (fine for a local prototype).
- The dashboard refreshes the whole page every 5 seconds instead of updating live.
- Sensor values depend on correct wiring, warm-up time and calibration.
- The default admin password `Admin@123` must be changed after the demo.
