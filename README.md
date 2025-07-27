# Fuel Price Prediction - Admin & User Dashboard

## Requirements
- PHP 8.0 or higher
- MySQL (MariaDB)
- XAMPP (recommended for local development)
- Composer (optional, if you want to manage PHP dependencies)

## Setup Instructions

### 1. Clone or Copy the Project
Place the project folder (e.g., `fuel price prediction`) inside your XAMPP `htdocs` directory:
```
C:/xampp/htdocs/fuel price prediction
```

### 2. Start XAMPP
- Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 3. Import the Database
1. Open **phpMyAdmin** (usually at [http://localhost/phpmyadmin](http://localhost/phpmyadmin)).
2. Click **Import**.
3. Select the provided SQL file (e.g.,`).
4. Click **Go** to import. This will create the `fuel_price_db` database and all required tables.



### 4. Configure Database (if needed)
- By default, the database config is in `config.php`:
  - Host: `localhost`
  - Username: `root`
  - Password: (empty)
  - Database: `fuel_price_db`
- If your MySQL setup is different, edit these values in `config.php`.

### 5. Access the App
- Go to [http://localhost/fuel%20price%20prediction/login.php](http://localhost/fuel%20price%20prediction/login.php)

### 6. Default Admin Login
- **Username:** `admin`
- **Email:** `admin@example.com`
- **Password:** `admin123`

### 7. Requirements List
- PHP extensions: `mysqli`, `pdo_mysql`, `json`
- Bootstrap 5 (CDN included)
- Bootstrap Icons (CDN included)
- Chart.js (CDN included)

### 8. Features
- Admin and user dashboards
- Advanced analytics and charts (admin)
- Make and view predictions
- Download reports as CSV
- User management (admin)

---

## Troubleshooting
- If you see a **Not Found** error, make sure you are using the correct URL with your project folder name.
- If you get a database connection error, check your MySQL credentials in `config.php`.
- For any issues, restart Apache and MySQL from XAMPP.

---

## License
This project is for educational/demo purposes. You can modify and use it as you wish. 