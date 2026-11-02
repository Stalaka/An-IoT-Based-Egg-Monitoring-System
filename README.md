# Egg Storage Monitor

An IoT-based egg storage monitoring and sales/stock system for small-scale egg sellers.

- **Hardware:** ESP8266 NodeMCU, DHT22 (temperature, humidity), MQ-135 (air quality)
- **Firmware:** Arduino IDE (C++), folder `arduino/egg_monitor`
- **Backend:** PHP + MySQL on XAMPP
- **Roles:** admin, vendor, supplier

## Run it

1. Copy this folder to `C:\xampp\htdocs\egg-monitor`
2. Start Apache and MySQL in the XAMPP Control Panel
3. In phpMyAdmin, import `database/schema.sql`
4. Copy `config.sample.php` to `config.php` (already done in the zip) and check the values
5. Open http://localhost/egg-monitor/ and log in with `admin@egg.local` / `Admin@123`

Full step-by-step instructions are in `GUIDE.md`.

No hardware? `WOKWI_GUIDE.md` shows how to demo the Arduino side in the Wokwi online simulator.
