# ♻️ SmartWaste — On-Demand Waste Collection Management System

> A ward-based waste pickup management system connecting residents, collectors, and administrators through a unified platform.

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=flat&logo=javascript&logoColor=black)
![Leaflet](https://img.shields.io/badge/Leaflet.js-Map-199900?style=flat&logo=leaflet&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Installation](#-installation)
- [Usage](#-usage)
- [Screenshots](#-screenshots)
- [System Flow](#-system-flow)
- [API Reference](#-api-reference)

---

## 🌍 Overview

SmartWaste replaces fixed-schedule waste collection with an **on-demand, GPS-optimized** system. Residents submit pickup requests, collectors manage them in batches with real-time route navigation, and administrators monitor operations across all wards.

The system covers **10 wards** and supports three user roles — Residents, Collectors, and Administrators — each with a dedicated portal.

---

## ✨ Features

### 👤 User (Resident)
- Register and log in securely
- Submit waste pickup requests with GPS location capture
- Select from 9 waste types (Plastic, Glass, E-Waste, Medical Waste, etc.)
- Track request status in real time
- Mark unavailability — auto-rescheduled to next available batch
- Receive notifications for pickup dates, reschedules, and truck proximity alerts

### 🚛 Collector
- Log in with ward assignment
- View and manage a queue of pending requests
- Create batches of 15 requests and set pickup dates
- Accept and complete individual requests
- Generate GPS-optimized pickup routes (nearest-neighbour algorithm)
- Real-time turn-by-turn navigation with live GPS truck tracking
- Automatic 1km proximity alert sent to the next resident on the route
- Report inability to collect with reschedule notification

### 🛡️ Admin
- Dashboard with live statistics (total, completed, pending, overdue)
- Filter and search all requests by ward, status, and waste type
- Waste type distribution and ward-wise charts
- Ward summary with completion percentage
- Send overdue alerts (15+ days) directly to collectors
- Manage notifications from collectors

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ with PDO |
| Database | MySQL 8 |
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6) |
| Maps | Leaflet.js + MapTiler Streets v2 |
| Routing | OSRM (Open Source Routing Machine) |
| Charts | Chart.js |
| Fonts | Plus Jakarta Sans (Google Fonts) |
| Server | Apache (XAMPP/WAMP) |

---

## 📁 Project Structure

```
SmartBin/
│
├── index.html                  # Landing page
├── login.html                  # User login
├── register.html               # User registration
├── collector-login.html        # Collector login
├── admin-login.php             # Admin login
│
├── user-dashboard.php          # User dashboard
├── user-dashboard.js
├── user-dashboard.css
│
├── collector-dashboard.php     # Collector dashboard
├── collector-dashboard.js
├── collector-dashboard.css
│
├── admin-dashboard.php         # Admin dashboard
├── admin-dashboard.js
├── admin-dashboard.css
│
├── style.css                   # Shared styles
│
└── backend/
    ├── db.php                      # PDO database connection
    ├── register.php                # User registration
    ├── login.php                   # User login
    ├── collector_login.php         # Collector login
    ├── admin_login.php             # Admin login
    ├── logout_admin.php            # Admin logout
    ├── request_pickup.php          # Submit pickup request
    ├── get_user_requests.php       # Fetch requests
    ├── accept_request.php          # Accept a request
    ├── complete_request.php        # Complete a request
    ├── create_batch.php            # Create request batch
    ├── get_batches.php             # Fetch batches
    ├── get_queued_requests.php     # Fetch queue / batch / history
    ├── set_batch_date.php          # Set pickup date + notify users
    ├── complete_batch.php          # Mark batch complete
    ├── mark_unavailable.php        # User marks unavailability
    ├── alert_next_user.php         # 1km truck proximity alert
    ├── notify_cannot_collect.php   # Collector reschedule notification
    ├── notify_overdue_collector.php# Admin overdue alert
    ├── get_user_notifications.php
    ├── mark_notification_read.php
    ├── get_collector_notifications.php
    ├── mark_collector_notif_read.php
    ├── get_admin_notifications.php
    ├── mark_admin_notif_read.php
    ├── get_admin_stats.php
    ├── get_all_requests.php
    ├── get_waste_stats.php
    ├── get_ward_stats.php
    ├── get_ward_summary.php
    ├── update_profile.php
    └── skip_request.php
```

---

## 🗄️ Database Schema

Database name: `smartbin`

```sql
-- Core tables
users               -- Registered residents
collectors          -- Ward collectors
admins              -- System administrators
requests            -- Pickup requests (core table)
batches             -- Grouped request batches

-- Notification tables
notifications           -- User notifications
collector_notifications -- Collector notifications
admin_notifications     -- Admin notifications
```

### Key columns in `requests`

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| user_id | INT | FK → users |
| ward_id | INT | Ward number (1–10) |
| address | TEXT | Pickup address |
| latitude / longitude | FLOAT | GPS coordinates |
| waste_type | VARCHAR | Comma-separated waste types |
| status | ENUM | requested / accepted / completed / rescheduled |
| batch_id | INT | FK → batches |
| batch_status | ENUM | queued / active / completed |
| pickup_date | DATE | Set by collector |
| alert_sent | TINYINT | 1km truck alert flag |

---

## ⚙️ Installation

### Prerequisites
- XAMPP or WAMP (Apache + MySQL + PHP 8+)
- Web browser with geolocation support
- MapTiler API key (free at [maptiler.com](https://www.maptiler.com))

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/smartwaste.git
```

**2. Move to server root**
```
Copy the SmartBin folder to:
XAMPP → C:/xampp/htdocs/SmartBin
WAMP  → C:/wamp64/www/SmartBin
```

**3. Create the database**

Open phpMyAdmin → create database `smartbin` → run the following SQL:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ward INT NOT NULL,
    phone VARCHAR(20),
    address TEXT
);

CREATE TABLE collectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ward_assigned INT NOT NULL
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ward_id INT NOT NULL,
    address TEXT,
    latitude FLOAT,
    longitude FLOAT,
    waste_type VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('requested','accepted','completed','rescheduled') DEFAULT 'requested',
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_date DATE DEFAULT NULL,
    batch_id INT DEFAULT NULL,
    batch_status ENUM('queued','active','completed') DEFAULT 'queued',
    route_order INT DEFAULT NULL,
    alert_sent INT DEFAULT 0,
    unavailable TINYINT DEFAULT 0,
    rescheduled_date DATE DEFAULT NULL
);

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    batch_number INT NOT NULL,
    pickup_date DATE DEFAULT NULL,
    status ENUM('pending','scheduled','in_progress','completed') DEFAULT 'pending',
    total_requests INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE collector_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collector_id INT NOT NULL,
    ward_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) DEFAULT 'info',
    message TEXT NOT NULL,
    ward_id INT DEFAULT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**4. Insert default admin**
```sql
INSERT INTO admins (username, password) VALUES ('admin', 'your_password_here');
```

**5. Insert a test collector**
```sql
INSERT INTO collectors (name, email, password, ward_assigned)
VALUES ('Collector 1', 'collector1@example.com', 'password123', 1);
```

**6. Configure database connection**

Edit `backend/db.php`:
```php
$host     = "localhost";
$user     = "root";
$password = "";           // your MySQL password
$database = "smartbin";
```

**7. Add your MapTiler API key**

In `collector-dashboard.js`, find the `buildMap` function and replace the key:
```javascript
L.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=YOUR_KEY_HERE', ...
```

**8. Start the server**

Start Apache and MySQL in XAMPP/WAMP then open:
```
http://localhost/SmartBin/
```

---

## 🚀 Usage

### Resident
1. Go to `localhost/SmartBin/register.html` → create account
2. Login → submit a pickup request with GPS location
3. Wait for collector to assign a pickup date
4. Check Notifications for updates and truck proximity alerts

### Collector
1. Go to `localhost/SmartBin/collector-login.html`
2. Login with email + ward number
3. Queue tab → wait for 15 requests → click **Create Batch**
4. Open the batch → click **Set Pickup Date** → users get notified
5. On pickup day → Generate Best Route → Start Navigation
6. Accept and complete each stop along the route

### Admin
1. Go to `localhost/SmartBin/admin-login.php`
2. Login with admin credentials
3. Monitor dashboard stats, charts, and ward summaries
4. Send overdue alerts to collectors for requests pending 15+ days

---

## 🔄 System Flow

```
Resident submits request
        ↓
Request enters queue (batch_id = NULL)
        ↓
Collector creates batch (min 15 requests)
        ↓
Collector sets pickup date → users notified
        ↓
Collector generates GPS-optimized route
        ↓
Collector starts navigation (live GPS tracking)
        ↓
1km from house → resident gets truck alert
        ↓
Collector accepts → completes each stop
        ↓
Batch marked complete → all users notified
```

---

## 📡 API Reference

All backend endpoints are in the `backend/` folder and return JSON.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `request_pickup.php` | POST | Submit pickup request |
| `get_user_requests.php` | GET | Fetch requests for user/collector |
| `accept_request.php` | POST | Accept a request |
| `complete_request.php` | POST | Complete a request |
| `create_batch.php` | POST | Create batch (min 15) |
| `set_batch_date.php` | POST | Set pickup date, notify users |
| `complete_batch.php` | POST | Mark batch done, notify users |
| `mark_unavailable.php` | POST | Move request to next batch |
| `alert_next_user.php` | POST | Send 1km truck proximity alert |
| `notify_cannot_collect.php` | POST | Reschedule and notify users |
| `notify_overdue_collector.php` | POST | Admin sends overdue alert |
| `get_admin_stats.php` | GET | Dashboard statistics |
| `get_ward_summary.php` | GET | Per-ward summary with overdue |
| `update_profile.php` | POST | Update user profile |

---

## 👥 Roles & Credentials (Development)

| Role | Login Page | Default Credentials |
|------|-----------|-------------------|
| Resident | `/login.html` | Register first |
| Collector | `/collector-login.html` | Set in collectors table |
| Admin | `/admin-login.php` | Set in admins table |

---

## 📝 Notes

- GPS geolocation requires **HTTPS** in production (works on localhost without it)
- OSRM routing uses the public server `router.project-osrm.org` — for production use a self-hosted instance
- MapTiler free tier allows 100,000 map tile requests/month
- Batch minimum is set to **15 requests** — change `$batch_size` in `create_batch.php` to adjust
- Notifications poll every **60 seconds** — adjust `setInterval` in dashboard JS files

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgements

- [Leaflet.js](https://leafletjs.com/) — Interactive maps
- [MapTiler](https://www.maptiler.com/) — Map tiles
- [OSRM](http://project-osrm.org/) — Road routing engine
- [Chart.js](https://www.chartjs.org/) — Analytics charts
- [Plus Jakarta Sans](https://fonts.google.com/specimen/Plus+Jakarta+Sans) — Typography

---

<div align="center">
  Made with ♻️ for a cleaner environment
</div>
