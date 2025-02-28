<?php
session_start();
require '../config.php'; // Include DB connection file

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Fetch role from DB
$admin_id = $_SESSION['admin_id'];
$role_query = $conn->prepare("SELECT role FROM admin WHERE id = ?");
$role_query->bind_param("i", $admin_id);
$role_query->execute();
$role_result = $role_query->get_result()->fetch_assoc();
$role = $role_result['role'];


if ($role !== 'admin' && $role !== 'dev') {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                background: linear-gradient(135deg, #e9ecef 0%, #f9e1e1 100%);
                font-family: 'Roboto', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .denied-container {
                background: #fff4f4;
                border: 2px solid #ff9999;
                border-radius: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                padding: 25px;
                text-align: center;
                max-width: 90%;
                width: 300px;
            }

            .denied-title {
                font-size: 30px;
                font-weight: 600;
                color: #ff9999;
                margin-bottom: 15px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .denied-message {
                font-size: 18px;
                color: #666;
                line-height: 1.4;
            }

            .btn {
                padding: 10px 20px;
                background: #ff9999;
                color: #fff;
                border: none;
                border-radius: 8px;
                text-decoration: none;
                display: inline-block;
                margin-top: 15px;
                font-size: 16px;
            }
        </style>
    </head>

    <body>
        <div class="denied-container">
            <h2 class="denied-title">Access Denied</h2>
            <p class="denied-message">You do not have permissions to view this page.</p>
            <a href="admindash.php" class="btn">Back</a>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Fetch referral rules
$rules = $conn->query("SELECT * FROM referral_rules ORDER BY min_referrals ASC");

// Fetch referral records
$referrals = $conn->query("SELECT r.id, r.referrer_id, r.referred_user_id, r.date_referred, r.status, 
    u1.name AS referrer_name, u2.name AS referred_name 
    FROM referral r 
    JOIN users u1 ON r.referrer_id = u1.user_id 
    JOIN users u2 ON r.referred_user_id = u2.user_id 
    ORDER BY r.date_referred DESC");

// Fetch referral tracking
$tracking = $conn->query("SELECT rt.id, rt.referrer_id, u1.name AS referrer_name, 
    rt.referred_user_id, u2.name AS referred_name, rt.points_earned, rt.date_activated 
    FROM referral_tracking rt 
    JOIN users u1 ON rt.referrer_id = u1.user_id 
    JOIN users u2 ON rt.referred_user_id = u2.user_id 
    ORDER BY rt.date_activated DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Referral Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            /* background: linear-gradient(135deg, #1e1e2f 0%, #2a2a40 100%); */
            font-family: 'Roboto', sans-serif;
            color: #e0e0e0;
            margin: 0;
            padding: 0px;
            padding: 10px;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            padding: 0;
            margin: 0;
            margin-top: 40px;
        }

        .title {
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            color: #ffffff;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card {
            /* background: #2e2e4a; */
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            /* background: linear-gradient(90deg, #3b3b5c 0%, #4a4a70 100%); */
            /* color: #ffffff; */
            padding: 6px 15px;
            font-size: 20px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 5px;
        }

        .rule-card {
            background: linear-gradient(90deg, rgb(192, 226, 250) 0%, rgb(247, 221, 252) 100%);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .rule-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .rule-form label {
            font-size: 12px;
            color: rgb(0, 0, 0);
            margin-bottom: 2px;
        }

        .rule-form input {
            width: 100%;
            padding: 6px;
            font-size: 12px;
            background: #404060;
            border: none;
            border-radius: 5px;
            color: #ffffff;
        }

        .rule-form input:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(81, 203, 238, 0.5);
        }

        .rule-form button {
            padding: 6px 10px;
            font-size: 12px;
            background: #51cbee;
            border: none;
            border-radius: 5px;
            color: #1e1e2f;
            font-weight: 500;
            width: 80px;
        }

        .rule-form button:hover {
            background: #3baed4;
        }

        .referral-card,
        .tracking-card {
            padding: 12px;
            background: #353554;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .badge {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            font-weight: 500;
        }

        .toggle-btn {
            font-size: 14px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: rgb(0, 0, 0);
            border-radius: 5px;
            width: 80px;
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        small {
            font-size: 11px;
            color: #a0a0c0;
        }

        strong {
            color: #ffffff;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="admindash.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <div class="container">
        <!-- Referral Rules -->
        <div class="card">
            <div class="card-header">
                Referral Rules
                <button class="toggle-btn" onclick="$('#rulesContainer').slideToggle();">Hide</button>
            </div>
            <div class="card-body" id="rulesContainer">
                <?php while ($row = $rules->fetch_assoc()) { ?>
                    <div class="rule-card">
                        <form method="POST" class="rule-form">
                            <input type="hidden" name="rule_id" value="<?= $row['id'] ?>">
                            <div>
                                <label>Min</label>
                                <input type="number" name="min_referrals" value="<?= $row['min_referrals'] ?>" placeholder="Min">
                            </div>
                            <div>
                                <label>Max</label>
                                <input type="number" name="max_referrals" value="<?= $row['max_referrals'] ?>" placeholder="Max">
                            </div>
                            <div>
                                <label>Points</label>
                                <input type="number" name="points_awarded" value="<?= $row['points_awarded'] ?>" placeholder="Points">
                            </div>
                            <button type="submit" name="update_rule">Update</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Referral Records -->
        <div class="card">
            <div class="card-header">Referral Records</div>
            <div class="card-body">
                <?php while ($row = $referrals->fetch_assoc()) { ?>
                    <div class="referral-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= $row['referrer_name'] ?></strong> → <strong><?= $row['referred_name'] ?></strong>
                                <div><small>Date: <?= $row['date_referred'] ?></small></div>
                            </div>
                            <span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Referral Tracking -->
        <div class="card">
            <div class="card-header">Referral Tracking</div>
            <div class="card-body">
                <?php while ($row = $tracking->fetch_assoc()) { ?>
                    <div class="tracking-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= $row['referrer_name'] ?></strong>
                                <div><small>Referred: <?= $row['referred_name'] ?> | <?= $row['date_activated'] ?></small></div>
                            </div>
                            <span class="badge bg-success"><?= $row['points_earned'] ?> pts</span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php
    // Handle Rule Update
    if (isset($_POST['update_rule'])) {
        $stmt = $conn->prepare("UPDATE referral_rules SET min_referrals = ?, max_referrals = ?, points_awarded = ? WHERE id = ?");
        $stmt->bind_param("iiii", $_POST['min_referrals'], $_POST['max_referrals'], $_POST['points_awarded'], $_POST['rule_id']);
        $stmt->execute();
        echo "<script>location.href = 'referraltracking.php';</script>";
    }
    ?>

</body>

</html>