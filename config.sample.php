<?php
// COPY this file to config.php and fill in your own values.
// config.php is listed in .gitignore so passwords never reach GitHub.

date_default_timezone_set('Africa/Nairobi');

define('DB_HOST', 'localhost');
define('DB_NAME', 'egg_monitor');
define('DB_USER', 'root');   // XAMPP default
define('DB_PASS', '');       // XAMPP default is an empty password

// Secret the NodeMCU must send in the X-API-KEY header. Must match API_KEY in secrets.h
define('API_KEY', 'change-this-to-a-long-random-string');

// Alert thresholds. These are PLACEHOLDERS: justify your final values from your
// literature review and the sensor datasheets before your defence.
define('TEMP_MAX', 25.0);            // degrees C
define('HUM_MIN', 50.0);             // % relative humidity
define('HUM_MAX', 80.0);             // % relative humidity
define('AIR_MAX', 400);              // raw MQ-135 value (0-1023), uncalibrated
define('DEVICE_STALE_SECONDS', 60);  // no data for this long = device offline
