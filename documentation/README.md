# Smart Campus Issue & Facility Management System

A full-stack, production-ready, beginner-friendly college summer internship project for B.Tech IT / Computer Science students.

---

## 📌 Architecture Overview

1. **Android Client (`android-app/`)**:
   - Built with Java and native Android XML layouts.
   - 8 Complete Screens: Splash, Login, Registration, Dashboard Home, Report Issue, My Complaints, Complaint Details (with comments thread), and Student Profile.
   - Volley HTTP networking + Glide image loading.

2. **Backend REST APIs (`backend/`)**:
   - Pure Core PHP (No heavy frameworks or complicated dependencies).
   - PDO database transactions with prepared statements.
   - Secure BCrypt password hashing (`password_hash` & `password_verify`).
   - HMAC-SHA256 stateless token authentication with role-based authorization.
   - Safe multipart photo uploading with MIME type verification.

3. **Database (`database/schema.sql`)**:
   - MySQL database `smart_campus` with 6 tables: `users`, `categories`, `locations`, `tickets`, `ticket_history`, and `ticket_comments`.
   - Pre-seeded with real campus categories, locations, users, and ticket workflows.

4. **Web Admin Dashboard (`admin-dashboard/`)**:
   - Responsive HTML5, Bootstrap 5, Chart.js, and Core PHP.
   - KPI counters, Doughnut & Bar charts, live table search/filters, workflow status updater, technician assignment modal, and CSV export.

---

## 🚀 How to Run the Project (Already Live!)

### 1. Database & Backend Status
- **MySQL Server**: Running on port `3306` (Standalone MySQL in `D:\xampp\`).
- **PHP Web Server**: Running on `http://localhost/` (Port `80`).
- **Web Admin Portal**: [http://localhost/SmartCampus/admin-dashboard/](http://localhost/SmartCampus/admin-dashboard/)

### 2. Pre-configured Login Accounts
| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@campus.edu` | `Admin@123` |
| **Technician 1** | `suresh.tech@campus.edu` | `Tech@123` |
| **Technician 2** | `ramesh.tech@campus.edu` | `Tech@123` |
| **Student 1** | `ananya.student@campus.edu` | `Student@123` |
| **Student 2** | `rohan.student@campus.edu` | `Student@123` |

---

## 📱 How to Open & Run the Android App in Android Studio

1. Open **Android Studio**.
2. Click **File -> Open...** and select the folder:
   `C:\Users\ASUS\.gemini\antigravity\scratch\SmartCampus\android-app`
3. Wait for Gradle to sync dependencies (`Volley`, `Glide`, `MaterialComponents`).
4. **Networking Configuration**:
   - Open `app/src/main/java/com/campus/smartcampus/network/ApiClient.java`.
   - If using a **Physical Android Device** connected to the same Wi-Fi as your laptop, ensure the `BASE_URL` uses your laptop's Wi-Fi IP:
     `http://10.61.168.88/SmartCampus/backend/`
   - If running on the standard **Android Studio Emulator**, use:
     `http://10.0.2.2/SmartCampus/backend/`
5. Click the green **Run** button (or press `Shift + F10`).
6. Sign in with student account `ananya.student@campus.edu` / `Student@123` or register a new student account!
