# VaxCare: Vaccination Management System

A web application that helps parents keep their children's vaccinations on track, lets hospitals manage and record appointments, and gives the admin full control and live statistics.

Built with **PHP (PDO)**, **MySQL**, **HTML, CSS, JavaScript** and **Bootstrap 5**.

---

## Screenshots

| Landing page | Admin dashboard |
|---|---|
| ![Landing](docs/screenshots/01-landing.png) | ![Admin dashboard](docs/screenshots/02-admin-dashboard.png) |

| Parent dashboard | Booking | Certificate |
|---|---|---|
| ![Parent dashboard](docs/screenshots/03-parent-dashboard.png) | ![Booking](docs/screenshots/04-booking.png) | ![Certificate](docs/screenshots/05-certificate.png) |

| Hospital appointments | Mobile view |
|---|---|
| ![Hospital](docs/screenshots/06-hospital.png) | ![Mobile](docs/screenshots/07-mobile.png) |

---

## The problem and our solution

Vaccinations for infants and children are often handled manually, so some children miss their doses and are exposed to diseases such as polio. VaxCare moves the whole process online: parents register their children, see exactly which vaccines are due, and book hospital appointments, while the admin approves requests and hospitals record each vaccination.

## Features

### Parent
- Register, login, manage profile and change password
- Add, edit and delete children (with ownership protection)
- **Smart vaccine schedule:** from the child's date of birth the system marks every vaccine as *Vaccinated, Booked, Due now, Overdue* or *Upcoming*
- Dashboard **reminders** for appointments in the next 7 days
- Search hospitals and book an appointment (date, time slot, slot capacity checks); hospitals in the parent's city are shown first as **Near you**; if a hospital is missing, the parent can **suggest it** and the admin verifies it before it is listed
- Track and cancel pending bookings
- **Vaccination report** and a printable **vaccination certificate** (Save as PDF)
- **Past vaccinations:** record vaccines the child already had before joining (verified by the admin), so the schedule does not show them as overdue

### Hospital
- Register with hospital name, **license / registration number** and city; the account becomes active **after our team verifies it**
- Dashboard with today's and upcoming appointments
- Sees only **approved** appointments of its own
- Records *Vaccinated* or *Not vaccinated* (only on or after the appointment day)

### Admin
- Dashboard with live statistics and **Chart.js charts**
- Hospitals: add, edit, delete
- Vaccines: add, edit, availability toggle, delete (blocked if already used)
- View all children with their vaccination history
- **Approve or reject** parent requests (unavailable vaccines cannot be approved)
- **Hospital requests:** verify hospitals suggested by parents and add them to the list
- **Users:** approve new hospitals, enable or disable parent and hospital accounts
- **Past records:** verify or reject earlier vaccinations entered by parents
- All bookings with status and date filters (with pagination); the admin can record the vaccination for hospitals that do not have their own login
- Date-wise **reports** with print and CSV export

### General
- Fully **responsive** (tables turn into cards on mobile)
- Public **certificate verification** page (`verify.php`): anyone can check a certificate number
- **Toast notifications** for every success / error message and a modern **confirm dialog** instead of the browser's pop-up
- Pagination on long admin lists
- Landing page with scroll animations and FAQ
- Works offline once the local assets are downloaded (see setup)

## Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5, Bootstrap Icons, Chart.js |
| Backend | PHP 8 (plain PHP, PDO) |
| Database | MySQL |
| Local server | XAMPP (Apache, MySQL, phpMyAdmin) |

## Setup

1. Install **XAMPP** and start **Apache** and **MySQL**.
2. Copy this folder to `C:\xampp\htdocs\vaccination-system` (the folder name must match `BASE_URL` in `config/config.php`).
3. Open `http://localhost/phpmyadmin`, go to the **Import** tab and import `database/vaccination.sql`.
4. Open `http://localhost/vaccination-system/`.
5. *(Optional, recommended)* To make the website work without internet, run once with internet access:
   ```
   powershell -ExecutionPolicy Bypass -File .\download-vendor.ps1
   ```

Database settings are in `config/db.php` (default XAMPP: user `root`, empty password).

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@vaccine.com | admin123 |
| Parent | ali@example.com | 123456 |
| Parent | sara@example.com | 123456 |
| Parent | fatima@example.com | 123456 |
| Hospital | city@hospital.com | 123456 |
| Hospital | care@hospital.com | 123456 |
| Hospital (waiting for approval) | sunrise@hospital.com | 123456 |

## How the system works

```
Parent adds child -> sees due vaccines -> books appointment (pending)
        -> Admin approves or rejects
        -> Hospital sees approved appointment -> records Vaccinated / Not vaccinated
        -> Parent sees updated schedule and downloads certificate
```

## Project structure

```
vaccination-system/
├── config/        config.php (settings), db.php (PDO connection)
├── includes/      layouts (header/footer per role), auth, helper functions, schedule engine
├── assets/        css, js, img, vendor (offline libraries)
├── database/      vaccination.sql (schema + demo data)
├── admin/         admin panel pages
├── parent/        parent panel pages
├── hospital/      hospital panel pages
├── docs/          viva questions, demo script, screenshots
├── index.php  login.php  register.php  logout.php
```

## Database (7 tables)

| Table | Purpose |
|---|---|
| `users` | Login for all roles (`admin`, `parent`, `hospital`), account `status` (active, pending, disabled) and login lockout fields |
| `hospitals` | Hospital details (city, address, license number), linked to a user account |
| `children` | Children of a parent |
| `vaccines` | Vaccine list with recommended age (in days) and availability |
| `bookings` | Appointments: `approval_status` (admin) and `vaccination_status` (hospital) |
| `past_vaccinations` | Earlier vaccines entered by parents, verified by the admin |
| `hospital_suggestions` | Hospitals suggested by parents, reviewed by the admin |

## Security

- Passwords hashed with `password_hash()` / verified with `password_verify()`
- **PDO prepared statements** everywhere user input reaches SQL (SQL injection)
- Output escaped with `htmlspecialchars()` through the `e()` helper (XSS)
- **CSRF token** in every POST form, checked on the server
- **Role-based access control** on every protected page
- **Ownership checks** (`AND parent_id = ?`, `AND hospital_id = ?`) so users cannot open other people's records (IDOR)
- Server-side validation for every form (client-side validation is only for convenience)
- Session cookie flags `HttpOnly` and `SameSite`, session ID regenerated on login and password change
- Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`)
- **Login lockout:** 5 wrong passwords lock the account for 5 minutes (brute-force protection)
- **Account status is checked on every request:** a disabled account is logged out immediately; new hospitals cannot log in or be booked until the admin approves them
- **Certificate numbers are signed (HMAC)**, so a number cannot be forged; the public verify page shows only a shortened name and the birth year
- Database transactions where several tables change together

## Known limitations and future work

- The login lockout is per account (it can temporarily lock a user out if someone keeps guessing their email); an IP-based limit would be the next step
- No email verification or password reset by email
- Slot capacity check and booking insert are separate steps (a database lock would remove the small race condition)
- A recorded vaccination status is final; corrections would need an admin audit feature
- No SMS or email notifications (reminders are shown on the dashboard)

## Team

| Name | Role / modules |
|---|---|
| (name) | Team lead: database, authentication, GitHub |
| (name) | Admin: hospitals and vaccines |
| (name) | Admin: requests, bookings, reports |
| (name) | Parent module |
| (name) | Hospital module, landing page, testing |
