
# ChaiJunction Platform

Welcome to **ChaiJunction**, a cozy loyalty platform for tea lovers! Whether you’re sipping a chai at our café or tracking your points on the go, ChaiJunction brings the warmth of your favorite brew right to your fingertips—via our web platform and mobile app.

## Overview
ChaiJunction is a rewards and engagement system built for the ChaiJunction Café. Earn points with every purchase, redeem them for treats, and stay connected with café updates—all wrapped in a modern, pastel-colored interface. The platform includes a web dashboard and a companion mobile app, designed to make your chai experience seamless and delightful.

<!-- **This is the 3rd verion of the platform. Previous Versions can be found on the Repo named Inner-Circle** -->
**ChaiJunction Platform (Version 3)**

This is the third iteration of the ChaiJunction platform. Previous versions, known as "Inner-Circle," can be found in the repository under that name. This version represents a significant upgrade, incorporating feedback and new features to enhance the user experience.



## Key Features

- **Loyalty Points**: Earn points with every purchase.
- **Redemption**: Redeem points for discounts and treats.
- **Activity Tracking**: Keep track of your points and transactions.
- **Notifications**: Stay updated with café news and your points balance.
- **User Profile**: Manage your account and preferences.
- **Mobile App**: Access your account on the go.
- **Pastel Themes**: Enjoy a visually appealing, modern design.
- **Responsive Design**: Works seamlessly on all devices.
- **Secure**: User data is protected with secure password hashing.
- **Dynamic Content**: Real-time updates via PHP and MySQL.
- **Customizable**: Easy to modify and extend.
- **Easy Setup**: Simple installation and configuration.



## Features

### Web Platform
- **Profile Page (`profile.php`)**:
  - Displays user ID, name, total points, and total redeemed points.
  - Shows recent activity (earned/redeemed) with color-coded cards (green/red).
  - Top notification popup for new points/redeem events (stored in `localStorage`, once daily).
  - Accurate month-end expiry countdown (Asia/Kolkata timezone).

- **Activity Page (`activity.php`)**:
  - Card-based list of earned/redeemed points from `notifications` table.
  - Filters (All, Earned, Redeemed) with pagination.
  - Pastel green/red styling.

- **Transactions Page (`transactions.php`)**:
  - Table-like card layout showing points earned, amount paid, and date.
  - Pastel blue theme with pagination.

- **Redeem History Page (`redeem.php`)**:
  - Table-like card layout for points redeemed and dates.
  - Pastel orange theme with pagination.

- **Notifications Page (`notification.php`)**:
  - Dual-section layout: Announcements (high/normal/low priority) and Notifications (earned/redeemed).
  - Filters for notifications, pagination, and pastel yellow/orange styling.
  - Marks all unread notifications as read on load.

- **Settings Page (`settings.php`)**:
  - Edit name, add email (if empty), change password (secure hashing).
  - Read-only user ID and phone—pastel purple theme.

- **About Page (`about.php`)**:
  - Info on ChaiJunction Café, the app, and the developer (you!).
  - Pastel teal theme, static content.

- **General**:
  - Consistent fixed-bottom navbar across all pages (Transactions, Redeem, Profile, Notifications, Settings).
  - Unread notification indicator (red badge) on bell icon.
  - Responsive design with pastel color schemes and animations (fadeIn, slideInUp).

### Mobile App
- Companion app for ChaiJunction, built to mirror web features.
- Track points, view transactions, redeem rewards, and get notifications.
- Available on **Android** download from [here](https://sumit7739.github.io/chaijncafe/).

## Technologies Used
- **Backend**: PHP, MySQL (dynamic data via `config.php` for DB connection).
- **Frontend**: HTML, CSS (pastel themes), JavaScript (jQuery for notifications).
- **Database**: Tables (`users`, `transactions`, `redeem`, `notifications`, `announcements`)—schema in repo.
- **Mobile**: Built for seamless user access in Kotlin/Java.


## Setup Instructions

### Prerequisites
- PHP 7.4+ with MySQLi extension.
- MySQL database server.
- Web server (e.g., Apache, Nginx).
- Mobile app build tools—e.g., Android Studio for APK if Android.

### Installation
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/Sumit7739/chaijncafe.git
   cd chaijncafe
   ```

2. **Database Setup**:
   - Create a MySQL database (e.g., `chaijunction_db`).
   - Import the schema from `database.sql` (create this with your table structures—`users`, `transactions`, etc.).
   - Update `config.php` with your DB credentials:
     ```php
     $host = 'localhost';
     $dbname = 'chaijunction_db';
     $username = 'your_username';
     $password = 'your_password';
     ```

3. **Deploy Web Files**:
   - Copy files to your web server’s root (e.g., `/var/www/html/`).
   - Ensure `uploads/` folder (if added later) has write permissions.

4. **Mobile App**:
   - Download APK from releases or via the link above.

5. **Run**:
   - Visit `http://localhost/chaijncafe/profile.php` after logging in.
   - Install the app on your device.

### Configuration
- **Timezone**: Set to `Asia/Kolkata` in `config.php`—adjust via `date_default_timezone_set('Asia/Kolkata');`.
- **Default Photo**: Users get `/profile/default.png`—photo upload TBD based on poll.

## Usage
- **Login**: Access via `login.php` creates session with `user_id`.
- **Navigate**: Use the navbar to jump between Profile, Transactions, Redeem, Notifications, Settings, and About.
- **Mobile**: Open the app—same features, on-the-go convenience.

## Database Schema
- **`users`**: `user_id`, `name`, `email`, `phone`, `password`, `points_balance`, `profile_pic`.
- **`transactions`**: `id`, `user_id`, `points_given`, `amount_paid`, `transaction_date`.
- **`redeem`**: `id`, `user_id`, `points_redeemed`, `date_redeemed`.
- **`notifications`**: `id`, `user_id`, `message`, `type` (points_update, redeem, etc.), `status` (unread/read), `created_at`.
- **`announcements`**: `id`, `admin_id`, `title`, `message`, `priority` (high/normal/low), `status`, `created_at`, `expires_at`.

## About the Developer
Hi, I’m Sumit Srivastava, the tea-loving coder behind ChaiJunction! This platform blends my passion for chai with my tech skills—built from scratch to make your café visits rewarding. When I’m not sipping chai or coding, I’m dreaming up new features. Reach out at srisumit96@gmail.com open to collabs, feedback, or just chai chats!

## Contributing
Got ideas? Fork the repo, tweak away, and submit a pull request—let’s make ChaiJunction even better!

## License
MIT © 2025 Sumit Srivastava. All rights reserved.

---
