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
    <title>Chai Junction - Terms & Conditions</title>
    <!-- <link rel="stylesheet" href="style.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eee;
            color: #f5f5f5;
            margin: 0;
            padding: 20px;
            text-align: left;
        }

        header {
            display: flex;
            justify-content: space-between;
            /* align-items: center; */
            /* margin-bottom: 20px; */
        }

        .nav i {
            position: relative;
            top: 25px;
            right: 30px;
            color: #000000;
            font-size: 24px;
        }

        .logo img {
            width: 250px;
            height: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }

        h1,
        h2 {
            text-align: center;
            color: #575757;
        }

        h2 {
            font-size: 18px;
            margin-top: 20px;
        }

        p {
            line-height: 1.6;
            color: #333;
        }

        ul {
            color: #333;
            padding-left: 20px;
        }

        li {
            color: #333;
            margin-bottom: 5px;
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
    <div class="container">
        <h1>Chai Junction - Terms & Conditions</h1>
        <p><strong>Effective Date:</strong> [Insert Date]</p>
        <p>Welcome to Chai Junction! By using our rewards program and app, you agree to the following terms regarding
            earning, redeeming, and using points.</p>

        <h2>1. Point System & Conversion</h2>
        <p><strong>Conversion Rate:</strong> 1 point = ₹ 1</p>
        <p>Points can be earned on purchases at Chai Junction stores or via our app.</p>

        <h2>2. Earning Points</h2>
        <p>Points are awarded based on total purchase amount:</p>
        <ul>
            <li>₹21 - ₹50 → 1 point</li>
            <li>₹51 - ₹100 → 2 points</li>
            <li>₹101 - ₹150 → 4 points</li>
            <li>₹151 - ₹200 → 8 points</li>
            <li>₹201 - ₹300 → 12 points</li>
            <li>₹301 - ₹400 → 18 points</li>
            <li>₹401 - ₹500 → 30 points</li>
            <li>Above ₹500 → 50 points</li>
        </ul>

        <h2>3. Redemption Rules</h2>
        <ul>
            <li>Redeem points only on Sundays or the last date of the month.</li>
            <li>Points cannot be earned on redemption days.</li>
            <li>Minimum redemption: 10 points (₹5).</li>
            <li>Maximum redemption per transaction: 200 points (₹100).</li>
            <li>Points are non-transferable and cannot be exchanged for cash.</li>
        </ul>

        <h2>4. Excluded Items</h2>
        <p>Points will NOT be earned on:</p>
        <ul>
            <li>Chips</li>
            <li>Toffees</li>
            <li>Biscuits</li>
            <li>Pan Masala</li>
            <li>Discounted/promotional items</li>
        </ul>

        <h2>5. Referral Bonus</h2>
        <ul>
            <li>Refer a friend → Earn 5 points on signup.</li>
            <li>Earn additional 10 points when they make their first purchase.</li>
            <li>Referred users must be new customers.</li>
        </ul>

        <h2>6. Membership Tiers</h2>
        <p>Users are placed into tiers based on total spending:</p>
        <ul>
            <li><strong>Bronze</strong> (Default)</li>
            <li><strong>Silver</strong> (₹2000+ spent)</li>
            <li><strong>Gold</strong> (₹5000+ spent)</li>
            <li><strong>Platinum</strong> (₹10,000+ spent)</li>
        </ul>
        <p>Higher tiers unlock exclusive rewards, faster point accumulation, and special deals.</p>

        <h2>7. Progress Tracker</h2>
        <p>Track your membership tier progress in the app with a real-time progress bar.</p>

        <h2>8. Daily & Weekly Challenges</h2>
        <p>Complete challenges to earn bonus points:</p>
        <ul>
            <li>"Buy 3 teas this week & earn 5 bonus points!"</li>
            <li>Special Festival Challenges with double points.</li>
        </ul>

        <h2>9. Points Expiry</h2>
        <ul>
            <li>Points expire at the end of each month.</li>
            <li>Expired points cannot be reinstated or transferred.</li>
            <li>Users will receive a notification 3 days before expiry.</li>
        </ul>

        <h2>10. QR Code Transactions</h2>
        <p>Scan your unique QR code in the app to:</p>
        <ul>
            <li>Earn points instantly.</li>
            <li>Redeem points at checkout.</li>
            <li>Track purchase history.</li>
        </ul>

        <h2>11. Modification & Termination</h2>
        <ul>
            <li>Chai Junction reserves the right to modify, suspend, or terminate the rewards system at any time.</li>
            <li>Users will be notified via app notifications and store announcements.</li>
        </ul>

        <h2>12. Dispute Resolution</h2>
        <ul>
            <li>Decisions by Chai Junction’s owner regarding points, redemption, and disputes are final.</li>
            <li>For issues, contact [Customer Support].</li>
        </ul>

        <p>By using the <a href="admind/adminsignup.html" style="color: #333; text-decoration: none;">Chai Junction</a> App, you acknowledge and agree to these terms.</p>
        <p><strong>— Chai Junction Cafe</strong></p>
    </div>
</body>

</html>