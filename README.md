# ChaiJunction Platform: Your Tea Addiction, Finally Rewarded. You're Welcome.

Welcome to **ChaiJunction**, the loyalty platform that understands your tea obsession better than your therapist. Ditch those flimsy punch cards; we've gone digital so your relentless chai habit gets the 21st-century recognition it deserves. Whether you practically live at our café or just occasionally grace us with your presence, ChaiJunction shoves the warmth of your favorite brew right into your grubby little paws via our undeniably snazzy web platform and a mobile app that might just become your new best friend.

---

## Overview: Because Even Loyalty Programs Need a Purpose (Besides Free Stuff)

ChaiJunction is *the* rewards and engagement system for the ChaiJunction Café. Earn points with every purchase, then redeem them for *actual* treats. Yes, free stuff! Stay updated on café gossip (oops, "news") through an interface so modern and pastel-colored, it practically screams "I have my life together." This platform includes a web dashboard and a companion mobile app, designed to make your chai experience not just seamless, but utterly **magical**.

---

**P.S.** This isn't our first rodeo. This is **ChaiJunction Platform (Version 3)**. Previous versions, known as "Inner-Circle," are chilling in another repo. This one's the real deal – upgraded, refined, and significantly less buggy (we hope).

---

## Key Features: The Good Stuff, Briefly

* **Loyalty Points & Redemption**: Earn points. Get free stuff. It's shockingly simple.
* **Activity & Notifications**: Stalk your points, track transactions, and get bothered by café news. You asked for it.
* **User Profile & Mobile App**: Manage your account and obsess over your points on the go.
* **Pretty Design & Security**: Pastel themes because we're fancy, responsive design for all devices, and secure password hashing because we actually care about your data (mostly).
* **Dynamic & Customizable**: Real-time updates via PHP/MySQL. Easy to modify if you're feeling feisty.
* **Easy Setup**: So simple, even your tech-challenged aunt could probably do it.

---

## Features: What Each Page Does (Without All the Fluff)

### Web Platform
* **Profile Page (`profile.php`)**: See your points, recent activity, and get a polite reminder about expiring points.
* **Activity Page (`activity.php`)**: A glorious list of your earned/redeemed points with filters and pagination.
* **Transactions Page (`transactions.php`)**: See points earned, amount paid, and date. Admire your spending habits.
* **Redeem History Page (`redeem.php`)**: A record of all your glorious freebies.
* **Notifications Page (`notification.php`)**: Announcements and notifications, filtered and paginated. We'll mark them read for you.
* **Settings Page (`settings.php`)**: Edit your name, add an email, change your password. Keep things fresh.
* **About Page (`about.php`)**: Info on us and the app. Static, because not everything needs to be dynamic.
* **General**: Consistent navbar, unread notification badge, and animations to keep things smooth.

### Mobile App
* The companion app: Tracks points, views transactions, redeems rewards, and gets notifications. Basically, everything the web platform does, but for your pocket.
* Download on **Android** from [here](https://sumit7739.github.io/chaijncafe/).

---

## Technologies Used: The Guts of the Operation

* **Backend**: PHP, MySQL (because we like classics).
* **Frontend**: HTML, CSS (for those eye-pleasing pastels), JavaScript (jQuery for pizzazz).
* **Database**: `users`, `transactions`, `redeem`, `notifications`, `announcements` tables. It's all there.
* **Mobile**: Kotlin/Java for a seamless experience.

---

## Setup Instructions: How to Get This Bad Boy Running

### Prerequisites
* PHP 7.4+ with MySQLi.
* MySQL database server.
* Web server (Apache, Nginx, etc.).
* Android Studio for the app (if you're building from source).

### Installation
1.  **Clone the Repo**: `git clone https://github.com/Sumit7739/chaijncafe.git`
2.  **Database Setup**: Create DB, import `database.sql`, update `config.php` with credentials.
3.  **Deploy Web Files**: Copy to your web server, ensure `uploads/` has write permissions.
4.  **Mobile App**: Download the APK or build it yourself.
5.  **Run**: Visit `http://localhost/chaijncafe/profile.php` after logging in. Install the app.

### Configuration
* **Timezone**: Set `date_default_timezone_set('Asia/Kolkata');` in `config.php`.
* **Default Photo**: `/profile/default.png` (photo uploads TBD).

---

## Usage: What to Do Once It's Running

* **Login**: `login.php` starts your session.
* **Navigate**: Use the navbar to jump around.
* **Mobile**: Open the app and enjoy on-the-go convenience.

---

## About the Developer: Me!

I’m Sumit Srivastava, the chai-fueled coder behind ChaiJunction! This platform is my passion project, blending my love for tea with my tech skills. Built from scratch to make your café visits genuinely rewarding. Reach out at srisumit96@gmail.com for collabs, feedback, or just to chat about chai!

---

## Contributing & License

Got ideas? Fork the repo, tweak away, and submit a pull request! Let's make ChaiJunction even better.

MIT © 2025 Sumit Srivastava. All rights reserved.

---
