# TravelGuide MVC - Complete Project

**Project:** Interactive Travel Guidance & Scout Management System  
**Architecture:** Plain PHP Model-View-Controller (MVC)  
**Database:** MySQL / MariaDB  
**Target:** XAMPP (Apache + PHP + MySQL)

## 1. Installation

1. Copy the folder `TravelGuide_MVC_Complete` into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** from XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Import `database.sql`. It creates a fresh database named `travelguide_db`.
5. If your MySQL username/password is not `root` / blank, edit `config/app.php`.
6. Open: `http://localhost/TravelGuide_MVC_Complete/`

The project intentionally uses `index.php?route=...` routing so it works without Apache rewrite configuration.

## 2. Demo accounts

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@travelguide.com` | `Admin@123` |
| Scout | `scout@travelguide.com` | `Scout@123` |
| Registered User | `user@travelguide.com` | `User@123` |

Public registration always creates a **Registered User**. Administrators can add Scout/Admin accounts from User Management.

## 3. Proposal features implemented

### Guest
- Register
- Login
- Browse **approved** destination posts
- Search/filter destinations
- View post details
- Use travel cost calculator

### Registered User
- Login/logout
- Personalized dashboard
- View/edit/delete profile
- Change password
- Reset forgotten password (demo token flow)
- Add/remove wishlist items
- Use travel cost calculator; logged-in history is saved
- View approved post details
- Add comments
- Delete only own comments

### Scout
- Scout dashboard
- Create destination post request
- View own request list
- Edit/delete own pending/change-request/rejected request
- Receive Administrator feedback
- Handle a change request by editing and resubmitting
- View approved posts
- Verified Scout check before submission

### Administrator
- Administrator dashboard
- User management: add, verify, delete
- Post-request moderation: approve, reject, request changes with feedback
- Approval automatically publishes/updates an approved destination post
- Post management: edit, publish/unpublish, delete
- Comment management: delete any comment

## 4. MVC structure

```text
TravelGuide_MVC_Complete/
|-- app/
|   |-- Core/                 # Router, DB, Auth, base Model/Controller
|   |-- Controllers/          # Request/business-flow controllers
|   |-- Models/               # Database models
|   `-- Views/                # PHP UI views and layouts
|-- config/                   # Application + database config/bootstrap
|-- public/
|   `-- assets/               # CSS + JavaScript
|-- database.sql              # Complete schema + demo data
|-- index.php                 # Front controller + route definitions
`-- README.md
```

## 5. Main database entities

- `roles`
- `users`
- `post_requests`
- `posts`
- `comments`
- `wishlists`
- `calculator_logs`
- `password_resets`

This follows the proposal's USER/ROLE/POST/POST_REQUEST/COMMENT/WISHLIST design and adds calculator/reset storage required by the implemented features.

## 6. Security / Web Technology points

- PHP sessions for authenticated login state
- Server-side role-based authorization (`user`, `scout`, `admin`)
- `password_hash()` / `password_verify()`
- PDO prepared statements
- CSRF token validation for all state-changing forms
- HTML escaping with `e()`
- Server-side validation
- AJAX + JSON response for the Travel Cost Calculator
- Foreign keys / cascade rules for data integrity

## 7. Password reset note

`DEMO_MODE` is enabled in `config/app.php`. Because a local XAMPP project normally has no SMTP mail server, the Forgot Password page generates and displays a one-time reset link. The token is **hashed in the database**, expires after 30 minutes and becomes unusable after reset. For production, connect an SMTP mailer and set `DEMO_MODE` to false.

## 8. Important workflow to demonstrate to the teacher

1. Login as Scout.
2. Open **Scout Requests** and create a destination request.
3. Logout and login as Administrator.
4. Open **Post Requests**.
5. Choose **Request Changes** and enter feedback, or approve it.
6. If changes are requested, login as Scout, edit/resubmit it.
7. Login as Admin and approve it.
8. The approved destination is now visible to Guests on **Explore**.
9. Login as Registered User, save it to Wishlist and add a comment.
10. Login as Admin to manage that post/comment.

This demonstrates the complete proposal workflow end-to-end.
