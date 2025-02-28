<?php
session_start();
require '../config.php';

// Check if admin is logged in
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

// Access control
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
            <p class="denied-message">You don’t have permission to view this page.</p>
            <a href="admindash.php" class="btn">Back</a>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Timezone fix (IST)
date_default_timezone_set('Asia/Kolkata');

// Fetch admins
$admins = $conn->query("SELECT id, name, phone, access, role FROM admin ORDER BY id");


// Fetch conversion rules
$rules = $conn->query("SELECT * FROM conversion_rules ORDER BY min_amount ASC");

// Handle conversion rule update
if (isset($_POST['update_rule'])) {
    $rule_id = $_POST['rule_id'];
    $min_amount = $_POST['min_amount'];
    $max_amount = $_POST['max_amount'];
    $points_awarded = $_POST['points_awarded'];
    $stmt = $conn->prepare("UPDATE conversion_rules SET min_amount = ?, max_amount = ?, points_awarded = ? WHERE id = ?");
    $stmt->bind_param("iiii", $min_amount, $max_amount, $points_awarded, $rule_id);
    $stmt->execute();
    header("Location: adminsettings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef 0%, #f9e1e1 100%);
            font-family: 'Roboto', sans-serif;
            color: #555;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            padding: 0;
        }

        .title {
            font-size: 24px;
            text-align: center;
            color: #89c4f4;
            margin-bottom: 20px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .card {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(90deg, #b8e1ff 0%, #f4d4fa 100%);
            color: #555;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 15px;
        }

        .toggle-btn {
            font-size: 12px;
            padding: 5px 10px;
            background: #d4a5e6;
            border: none;
            color: #fff;
            border-radius: 8px;
        }

        .admin-card {
            background: #f0f7fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .rule-card {
            background: rgb(193, 227, 241);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .rule-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rule-form label {
            font-size: 12px;
            color: #444;
            margin-bottom: 5px;
        }

        .rule-form input {
            padding: 6px;
            font-size: 12px;
            background: #fff;
            border: 1px solid #d4e6f1;
            border-radius: 5px;
            color: #555;
            width: 100%;
        }

        .rule-form button {
            padding: 8px 12px;
            font-size: 12px;
            background: rgb(124, 255, 177);
            border: none;
            border-radius: 5px;
            color: #000;
            align-self: flex-end;
            width: 100%;
        }

        .log-item {
            background: #f0f7fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        small {
            font-size: 11px;
            color: #555;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo img {
            width: 200px;
            height: auto;
            margin-left: 20px;
        }

        .fa-arrow-left {
            position: absolute;
            top: 30px;
            right: 0;
            margin-right: 30px;
            font-size: 25px;
            color: #555;
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
    <br>
    <div class="container">
        <h2 class="title">Admin Settings</h2>
        <!-- Admins List -->
        <div class="card">
            <div class="card-header">
                Admins & Managers
                <button class="toggle-btn" onclick="$('#adminsList').slideToggle();">Toggle</button>
            </div>
            <div class="card-body" id="adminsList">
                <?php while ($admin = $admins->fetch_assoc()) { ?>
                    <div class="admin-card">
                        <div>
                            <strong><?= $admin['name'] ?></strong><br>
                            <small>ID: <?= $admin['id'] ?> | Phone: <?= $admin['phone'] ?> | Role: <?= $admin['role'] ?></small>
                            <br>
                            <small>Access: <?= $admin['access'] ?></small>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <!-- Conversion Rules (Moved to Bottom, Card-Based) -->
        <div class="card">
            <div class="card-header">
                Conversion Rules
                <button class="toggle-btn" onclick="$('#rulesContainer').slideToggle();">Toggle</button>
            </div>
            <div class="card-body" id="rulesContainer">
                <?php while ($rule = $rules->fetch_assoc()) { ?>
                    <div class="rule-card">
                        <form method="POST" class="rule-form">
                            <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                            <div>
                                <label>Min Amount (₹)</label>
                                <input type="number" name="min_amount" value="<?= $rule['min_amount'] ?>" placeholder="Min">
                            </div>
                            <div>
                                <label>Max Amount (₹)</label>
                                <input type="number" name="max_amount" value="<?= $rule['max_amount'] ?>" placeholder="Max">
                            </div>
                            <div>
                                <label>Points Awarded</label>
                                <input type="number" name="points_awarded" value="<?= $rule['points_awarded'] ?>" placeholder="Pts">
                            </div>
                            <button type="submit" name="update_rule">Update</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>
