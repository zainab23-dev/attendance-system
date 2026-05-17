# AttendancePro

A full-stack attendance management system built with PHP, MySQL, sessions, and responsive CSS.

## Features

- Teacher registration and login with hashed passwords
- Session-protected dashboard
- Create class sections with semester and week count
- Add students and assign them to classes
- Mark attendance by week and class session
- Attendance reports with filters and present-rate summaries
- Teacher-scoped data access using prepared SQL statements
- Modern responsive interface for desktop and mobile

## Setup

1. Start Apache and MySQL in XAMPP.
2. Create a MySQL database named `attendance_system`.
3. Import `database/attendance_system.sql` into that database.
4. Place this folder at `C:/xampp/htdocs/attendance_system` or keep it in your web server root.
5. Open `http://localhost/attendance_system/iindex.php`.

## Default Database Settings

The app connects with:

- Host: `localhost`
- User: `root`
- Password: empty
- Database: `attendance_system`

Update `config/db.php` if your local MySQL credentials are different. You can also set these environment variables:

- `DB_HOST`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`
