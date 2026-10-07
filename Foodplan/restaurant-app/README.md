# Foodplan (PHP and XAMPP)

Foodplan is a PHP, MySQL, HTML, CSS, and vanilla JavaScript restaurant ordering site. It runs through XAMPP's Apache and MySQL services; Node.js and React are not needed.

## Install and run on Windows

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Create a MySQL database named `foodplan` in phpMyAdmin (`http://localhost/phpmyadmin`).
3. Select `foodplan`, choose **Import**, and import `server/schema.sql` from this project folder. This creates the `ff_*` tables and sample menu items.
4. Copy the contents of this project folder into `C:\xampp\htdocs\Foodplan`. Keep `index.php`, `api.php`, `db.php`, `php_helpers.php`, `app.js`, `php-app.css`, and the `pages` folder directly inside `Foodplan`.
5. Open **http://localhost/Foodplan/**. Check the API at **http://localhost/Foodplan/api.php?action=health**.
6. Open **http://localhost/Foodplan/?page=setup-admin** and choose **Create default accounts** once. This creates both the administrator and rider accounts only if the database has no administrator:

   | Role | Email | Password |
   | --- | --- | --- |
   | Admin | `admin@gmail.com` | `Amartay11Das` |
   | Rider | `rider@gmail.com` | `Amartay11Das` |

   Then sign in at `?page=login&role=admin` or `?page=login&role=rider`. These shared credentials are for local testing only; change them before exposing the site to the internet.

The default local database settings in `db.php` are host `127.0.0.1`, port `3306`, database `foodplan`, user `root`, and a blank password. If your MySQL settings differ, set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables for Apache.

## Features

- Customers can browse and filter dishes, add items to a cart, create an account, place orders, save delivery addresses, apply coupons, and submit reservations, reviews, or support requests.
- Customers can save a delivery area on the home page. Foodplan remembers it in that browser and pre-fills the checkout address; customers should still add their full street and house details before ordering.
- Administrators can confirm new orders, move them through preparing and packing to ready for pickup, review and approve rider pickup requests, assign riders, and manage dishes, riders, reservations, coupons, and support requests.
- Riders receive in-app messages when customers place orders and when orders are ready. Riders request a ready delivery, and the administrator approves or declines the request before the rider can proceed with delivery, status updates, and GPS location sharing.
- Customers can follow order status and, while the rider is sharing GPS, see the rider's location on an OpenStreetMap view. GPS requires the rider to keep location sharing active; browser geolocation works on `localhost` or HTTPS.

Payments other than cash on delivery are recorded as pending; no payment provider is connected. Delivery verification codes are shown to the customer in order tracking, so the customer should share the code with the rider only at handoff.

## Troubleshooting

- **Connection refused:** confirm Apache is running, then use `http://localhost/Foodplan/` (not the Vite development URL or `http://localhost/foodplan` unless the directory is named `foodplan`).
- **Database setup incomplete:** confirm MySQL is running and that `server/schema.sql` was imported into the `foodplan` database.
- **PHP database driver error:** in XAMPP's `php.ini`, enable `pdo_mysql`, restart Apache, then retry the health URL.
- **API returns an error:** check the Apache PHP error log from the XAMPP Control Panel. The health endpoint should return `{"status":"ok","service":"Foodplan PHP"}`.
