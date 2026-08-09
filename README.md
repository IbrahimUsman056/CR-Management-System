# 🎓 CR Portal — Class Representative Management System

A mobile-first web app built for university class representatives to manage attendance, quiz marks, project groups, and teacher task tracking — all in one place, without a mess of WhatsApp messages and paper attendance sheets.

Built for a real use case: one CR juggling 6–8 courses, each with a different teacher and a different subset of enrolled students (electives, repeaters, failed pre-reqs — not everyone takes everything).

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black" />
  <img src="https://img.shields.io/badge/Mobile--First-1e40af?style=flat-square" />
</p>

---

## 📸 Screenshots

<!-- Replace these with real screenshots before publishing -->
<table>
  <tr>
    <td align="center"><img width="319" height="526" alt="image" src="https://github.com/user-attachments/assets/8b42aba1-9c91-4814-a96a-ef48db174e22" />
<br><sub>Login</sub></td>
    <td align="center"><img width="318" height="539" alt="image" src="https://github.com/user-attachments/assets/313d627a-142d-4073-94ac-ef1dcb8d9495" />
<br><sub>Dashboard</sub></td>
    <td align="center"><img width="320" height="536" alt="image" src="https://github.com/user-attachments/assets/1d1b8d23-3bef-4b89-bfa5-eab99a57bee8" />
<br><sub>Attendance</sub></td>
  </tr>
  <tr>
    <td align="center"><img width="320" height="536" alt="image" src="https://github.com/user-attachments/assets/f4373889-3acb-4c4a-a2b7-0e5e88c856e5" />
<br><sub>Quiz Marks</sub></td>
    <td align="center"><img width="322" height="539" alt="image" src="https://github.com/user-attachments/assets/f9cefde9-aed0-4fb6-ace4-5aef6f0aada9" />
<br><sub>Project Groups</sub></td>
    <td align="center"><img width="317" height="538" alt="image" src="https://github.com/user-attachments/assets/778a9e31-1062-482d-9491-b0323e61c309" />
<br><sub>Task Tracker</sub></td>
  </tr>
</table>

---

## ✨ Features

**Roster & Courses**
- Single class roster shared across every feature — add a student once, use them everywhere
- Courses each track their own enrollment — active, repeater, or not enrolled, since not every student takes every course

**Attendance**
- Mark present/absent per course, per lecture date
- Bulk "Mark All Present" then flip the few absentees, instead of tapping every student one by one
- Revisiting a course + date pre-fills existing marks, so it doubles as an edit screen

**Quiz Marks**
- Create quizzes per course with a total mark value
- Enter marks against the enrolled list only
- Class average auto-calculates live as marks are entered

**Project Groups**
- Group students from the roster into project teams
- Optional project name per group, editable any time
- A student can only belong to one group at a time — no double-booking

**Task Tracker**
- The actual daily grind of being a CR: "collect assignment files by Thursday," "get feedback forms signed" — with due dates and status
- Dashboard surfaces what's due soon so nothing slips through

**Exports**
- Clean, colored JPG snapshots for sharing directly with a teacher or in a group chat
- Plain CSV exports for anyone who wants to open it in Excel/Sheets and work with the numbers

**Design**
- Built mobile-first with a fixed bottom nav, since this is used on a phone between classes far more than on a laptop
- Custom in-app confirmation dialogs instead of the browser's default popups

---

## 🛠️ Tech Stack

- **Backend:** PHP (PDO for all database access — no raw string queries)
- **Database:** MySQL
- **Frontend:** Vanilla JS, hand-written CSS (no framework)
- **Exports:** html2canvas (JPG capture), native PHP CSV generation
- **Hosting:** InfinityFree (shared hosting, free tier)

---

## 📁 Project Structure

```
cr_portal/
├── auth/               # login, register, logout
├── config/              # database connection (not committed — see setup)
├── includes/            # shared header, footer, navbar, functions
├── assets/
│   ├── css/style.css    # single stylesheet, mobile-first
│   └── js/script.js     # attendance toggles, exports, confirm modal
├── sql/schema.sql       # full database schema
├── dashboard.php
├── students.php
├── courses.php
├── course_detail.php    # enrollment management per course
├── attendance.php
├── marks.php
├── groups.php
├── tasks.php
└── export_*.php         # CSV export endpoints
```

---

## 🚀 Setup

### 1. Clone the repo
```bash
git clone https://github.com/IbrahimUsman056/CR-Management-System
cd cr-portal
```

### 2. Create the database
Import `sql/schema.sql` into your MySQL server (via phpMyAdmin or the CLI).

### 3. Configure the database connection
Copy the example config and fill in your real credentials:
```bash
cp config/db.example.php config/db.php
```
Then edit `config/db.php` with your actual DB host, name, username, and password.

### 4. Serve it locally
Point a local PHP server (XAMPP, WAMP, or `php -S`) at the project folder, then visit:
```
http://localhost/cr_portal/auth/register.php
```
to create your first CR account.

---

## 🔑 Demo

<!-- Add live demo link and/or demo credentials once deployed -->
- **Live demo:** cr-portal.infinityfreeapp.com

---
