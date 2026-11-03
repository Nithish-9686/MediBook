# MediBook

Doctor appointment and subscription platform. Patients search doctors, subscribe for access to hospital contact details, and book and manage appointments.

## Features

- Doctor search with filter and sort
- Subscription paywall gating hospital contact info
- Appointment booking with duplicate-slot checking
- Dashboard with upcoming and past appointments, plus cancellation
- JWT authentication (httpOnly cookie)

## Tech stack

| Layer | Tech |
|---|---|
| Backend | PHP, MySQL |
| Auth | firebase/php-jwt |
| Frontend (current) | HTML, CSS, JavaScript |
| Frontend (in progress) | React, Vite, React Router, Axios, Tailwind |
| Local server | XAMPP |



## Status

- [x] Core PHP app: auth, doctor search, subscription, booking, dashboard
- [x] JSON REST API: doctors, doctor-profile, book-appointment, dashboard, cancel-appointment, signup
- [ ] Remaining API endpoints: subscribe, logout
- [ ] React frontend
- [ ] Razorpay payments
- [ ] Admin panel
- [ ] Live queue position tracking

## Run locally

1. Install [XAMPP](https://www.apachefriends.org/) and start Apache and MySQL.
2. Clone into the XAMPP web root:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Nithish-9686/MediBook.git medibook
   cd medibook
   ```
3. Install dependencies:
   ```bash
   composer install
   ```
4. Create a database named `medibook` in phpMyAdmin and import `database.sql`.
5. Copy `.env.example` to `.env` and set `JWT_SECRET` to a long random string.
6. Open `http://localhost/medibook`.

## Author

Nithish: [LinkedIn](https://linkedin.com/in/nithish-d-0a1a13332)