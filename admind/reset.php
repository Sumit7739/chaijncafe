<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../config.php'; // Include DB connection file
require '../admind/fpdf186/fpdf.php'; // Include FPDF library (download from http://www.fpdf.org/)

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php"); // Redirect to login if not logged in
    exit();
}

// Fetch role from DB
$admin_id = $_SESSION['admin_id'];
$role_query = $conn->prepare("SELECT role FROM admin WHERE id = ?");
$role_query->bind_param("i", $admin_id);
$role_query->execute();
$role_result = $role_query->get_result()->fetch_assoc();
$role = $role_result['role'];

// Access control (only admin role)
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
            <p class="denied-message">Only admins can reset data</p>
            <a href="admindash.php" class="btn">Back</a>
        </div>
    </body>

    </html>
    <?php
    exit();

}

// Handle export to PDF
// Handle export to PDF
if (isset($_POST['export'])) {
    require_once '../admind/fpdf186/fpdf.php'; // Adjusted path

    // Clear any previous output
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Define file path with month name
    $month_name = date('M'); // e.g., Feb, Mar
    $year = date('Y'); // e.g., 2025
    $file_path = '../admind/exports/Monthly_Data_Export_' . $month_name . $year . '.pdf'; // e.g., Monthly_Data_Export_Feb2025.pdf

    // Check if folder is writable
    if (!is_writable(dirname($file_path))) {
        die("Error: The 'exports' folder is not writable. Please grant write permissions to the web server user (e.g., www-data).");
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Monthly Data Export - ' . date('F Y'), 0, 1, 'C'); // Full month name, e.g., February 2025
    $pdf->Ln(10);

    // Users Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Users', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 8, 'ID', 1);
    $pdf->Cell(40, 8, 'Name', 1);
    $pdf->Cell(30, 8, 'Phone', 1);
    $pdf->Cell(40, 8, 'Email', 1);
    $pdf->Cell(20, 8, 'Total Pts', 1);
    $pdf->Cell(20, 8, 'Rem Pts', 1);
    $pdf->Cell(20, 8, 'Amt Spent', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $users = $conn->query("SELECT user_id, name, phone, email, total_points, amount_spent, points_balance FROM users");
    while ($row = $users->fetch_assoc()) {
        $pdf->Cell(20, 6, $row['user_id'], 1);
        $pdf->Cell(40, 6, substr($row['name'], 0, 20), 1);
        $pdf->Cell(30, 6, $row['phone'], 1);
        $pdf->Cell(40, 6, substr($row['email'], 0, 20), 1);
        $pdf->Cell(20, 6, $row['total_points'], 1);
        $pdf->Cell(20, 6, $row['points_balance'], 1);
        $pdf->Cell(20, 6, $row['amount_spent'], 1);
        $pdf->Ln();
    }
    $pdf->Ln(5);

    // Transactions Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Transactions', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 8, 'User ID', 1);
    $pdf->Cell(40, 8, 'Amount Paid', 1);
    $pdf->Cell(40, 8, 'Points Given', 1);
    $pdf->Cell(50, 8, 'Date', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $trans = $conn->query("SELECT user_id, amount_paid, points_given, transaction_date FROM transactions");
    while ($row = $trans->fetch_assoc()) {
        $pdf->Cell(30, 6, $row['user_id'], 1);
        $pdf->Cell(40, 6, $row['amount_paid'], 1);
        $pdf->Cell(40, 6, $row['points_given'], 1);
        $pdf->Cell(50, 6, $row['transaction_date'], 1);
        $pdf->Ln();
    }
    $pdf->Ln(5);

    // Redeem Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Redemptions', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 8, 'User ID', 1);
    $pdf->Cell(40, 8, 'Points Redeemed', 1);
    $pdf->Cell(50, 8, 'Date', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $redeem = $conn->query("SELECT user_id, points_redeemed, date_redeemed FROM redeem");
    while ($row = $redeem->fetch_assoc()) {
        $pdf->Cell(30, 6, $row['user_id'], 1);
        $pdf->Cell(40, 6, $row['points_redeemed'], 1);
        $pdf->Cell(50, 6, $row['date_redeemed'], 1);
        $pdf->Ln();
    }
    $pdf->Ln(5);

    // Announcements Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Announcements', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, 'Title', 1);
    $pdf->Cell(30, 8, 'Priority', 1);
    $pdf->Cell(30, 8, 'Status', 1);
    $pdf->Cell(50, 8, 'Created', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $ann = $conn->query("SELECT title, message, priority, status, created_at, expires_at FROM announcements");
    while ($row = $ann->fetch_assoc()) {
        $pdf->Cell(40, 6, substr($row['title'], 0, 20), 1);
        $pdf->Cell(30, 6, $row['priority'], 1);
        $pdf->Cell(30, 6, $row['status'], 1);
        $pdf->Cell(50, 6, $row['created_at'], 1);
        $pdf->Ln();
        $pdf->MultiCell(0, 6, "Message: " . $row['message'], 1);
    }
    $pdf->Ln(5);

    // Audit Logs Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Audit Logs', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 8, 'Admin ID', 1);
    $pdf->Cell(30, 8, 'Action', 1);
    $pdf->Cell(30, 8, 'Table', 1);
    $pdf->Cell(20, 8, 'Record ID', 1);
    $pdf->Cell(50, 8, 'Date', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $audit = $conn->query("SELECT admin_id, action, table_name, record_id, message, created_at FROM audit_logs");
    while ($row = $audit->fetch_assoc()) {
        $pdf->Cell(30, 6, $row['admin_id'], 1);
        $pdf->Cell(30, 6, substr($row['action'], 0, 15), 1);
        $pdf->Cell(30, 6, substr($row['table_name'], 0, 15), 1);
        $pdf->Cell(20, 6, $row['record_id'] ?? '-', 1);
        $pdf->Cell(50, 6, $row['created_at'], 1);
        $pdf->Ln();
        $pdf->MultiCell(0, 6, "Message: " . $row['message'], 1);
    }
    $pdf->Ln(5);

    // Login Logs Table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Login Logs', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 8, 'Admin ID', 1);
    $pdf->Cell(80, 8, 'Message', 1);
    $pdf->Cell(50, 8, 'Time', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $login = $conn->query("SELECT admin_id, message, login_time FROM login_log");
    while ($row = $login->fetch_assoc()) {
        $pdf->Cell(30, 6, $row['admin_id'], 1);
        $pdf->Cell(80, 6, substr($row['message'], 0, 40), 1);
        $pdf->Cell(50, 6, $row['login_time'], 1);
        $pdf->Ln();
    }

    // Save PDF to server
    if ($pdf->Output('F', $file_path) === false) {
        die("Error: Failed to save PDF to $file_path. Check folder permissions.");
    }

    // Redirect to open in browser
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/exports/Monthly_Data_Export_' . $month_name . $year . '.pdf';
    header("Location: $url");
    exit();
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $conn->query("UPDATE users SET points_balance = 0"); // Reset points
    $conn->query("TRUNCATE TABLE transactions");
    $conn->query("TRUNCATE TABLE redeem");
    $conn->query("TRUNCATE TABLE announcements");
    $conn->query("TRUNCATE TABLE audit_logs");
    $conn->query("TRUNCATE TABLE login_log");
    $conn->query("TRUNCATE TABLE notifications");
    header("Location: reset.php?reset=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Monthly Reset</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="../css/adminoverview.css">
    <link rel="stylesheet" href="../css/addpoints.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0e6f6 0%, #e6f0fa 100%);
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            /* display: flex; */
            justify-content: center;
            align-items: center;
        }

        .container {
            margin: 0 auto;
            margin-top: 150px;
            max-width: 450px;
            width: 95%;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .card-header {
            background: linear-gradient(90deg, #ffccd5 0%, #89c4f4 100%);
            color: #fff;
            padding: 15px 20px;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .card-body {
            padding: 20px;
            text-align: center;
        }

        .warning-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .btn-export {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #a9dfbf 0%, #89c4f4 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 15px;
            transition: opacity 0.3s ease;
        }

        .btn-export:hover {
            opacity: 0.9;
        }

        .btn-reset {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #ff9999 0%, #ff4444 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .btn-reset:hover {
            opacity: 0.9;
        }

        .modal-content {
            border-radius: 15px;
            background: #fff4f4;
        }

        .modal-header {
            background: #ff9999;
            color: #fff;
            border-bottom: none;
        }

        .modal-body {
            color: #666;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #555;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            width: 100px;
        }

        .btn-confirm {
            background: #ff4444;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .success-message {
            color: #a9dfbf;
            font-size: 14px;
            margin-top: 15px;
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
        <div class="card">
            <div class="card-header">Monthly Reset</div>
            <div class="card-body">
                <p class="warning-text">This will reset all user points to 0 and clear transactions, redemptions, announcements, and logs.</p>
                <p class="warning-text" style="color:rgb(252, 58, 58);">Tip: Export data before resetting to save monthly records.</p>
                <form method="POST">
                    <button type="submit" name="export" class="btn-export">Export Data</button>
                </form>
                <button class="btn-reset" data-bs-toggle="modal" data-bs-target="#confirmModal">Reset All Data</button>
                <?php if (isset($_GET['reset'])) { ?>
                    <p class="success-message">Data reset successfully!</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Are you sure?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    This will reset all user points and delete all transactions, redemptions, announcements, and logs. Proceed?
                </div>
                <div class="modal-footer">
                    <button class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST">
                        <button type="submit" name="confirm_reset" class="btn btn-confirm">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
