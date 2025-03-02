<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #e6fafa;
            /* Pastel teal */
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        /* Header
        header {
            background: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        } */

        .logo img {
            /* height: 40px; */
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.1);
        }

        .nav a {
            position: absolute;
            top: 20;
            right: 25px;
            font-size: 24px;
            color: #00b7b7;
            /* Pastel teal accent */
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .nav a:hover {
            color: #008787;
            /* Darker teal */
        }

        /* About Sections */
        .about-container {
            max-width: 800px;
            margin: 20px auto;
        }

        .section {
            background-color: rgba(255, 255, 255, 0.48);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease forwards;
        }

        .section h2 {
            font-size: 22px;
            font-weight: 600;
            color: #00b7b7;
            /* Pastel teal */
            margin-bottom: 15px;
            text-align: center;
        }

        .section p {
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            text-align: justify;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            header {
                padding: 10px 15px;
            }

            .nav a {
                margin-top: 20px;
                font-size: 16px;
            }

            .section {
                padding: 15px;
            }

            .section h2 {
                font-size: 18px;
            }

            .section p {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="profile.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <br>

    <div class="about-container">
        <div class="section">
            <h2>About the Cafe</h2>
            <p>Welcome to Chai Junction Cafe! We love "Chai" and a friendly atmosphere. Here, you can enjoy fresh Tea, and a cozy space to relax or catch up with friends. Whether you're a regular or visiting for the first time, we're happy to have you here!</p>
        </div>

        <div class="section">
            <h2>About the App</h2>
            <p>This app makes your cafe visits even better! You can track points, redeem rewards, and stay updated on new offers. It's simple, easy to use, and helps you enjoy extra perks with every visit.</p>
        </div>

        <div class="section">
            <h2>About the Developer</h2>
            <p>Hi, I’m Sumit Srivastava, the developer of this app. I created it to make your cafe experience more convenient. When I’m not coding, I enjoy a good cup of coffee. Thanks for using the app!</p>
        </div>
    </div>

</body>

</html>