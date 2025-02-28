<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../config.php'; // Include DB connection file

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

// Fetch user signup data (updated to created_at)
$users_today = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$users_7days = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$users_15days = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)")->fetch_assoc()['count'];
$users_30days = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'];

// Fetch transaction data (daily, weekly, monthly)
$trans_daily = $conn->query("SELECT SUM(amount_paid) as total_paid, SUM(points_given) as total_points FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch_assoc();
$trans_weekly = $conn->query("SELECT SUM(amount_paid) as total_paid, SUM(points_given) as total_points FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc();
$trans_monthly = $conn->query("SELECT SUM(amount_paid) as total_paid, SUM(points_given) as total_points FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc();

// Fetch redemption data (daily, weekly, monthly)
$redeem_daily = $conn->query("SELECT SUM(points_redeemed) as total_redeemed FROM redeem WHERE DATE(date_redeemed) = CURDATE()")->fetch_assoc()['total_redeemed'];
$redeem_weekly = $conn->query("SELECT SUM(points_redeemed) as total_redeemed FROM redeem WHERE date_redeemed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total_redeemed'];
$redeem_monthly = $conn->query("SELECT SUM(points_redeemed) as total_redeemed FROM redeem WHERE date_redeemed >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['total_redeemed'];

// Data for charts (only dates with data)
$chart_labels = [];
$chart_trans_paid = [];
$chart_points_given = [];
$chart_points_redeemed = [];

// Fetch distinct dates with data from transactions and redeem within the last 30 days
$query = "
    SELECT DISTINCT DATE(date_column) as date
    FROM (
        SELECT transaction_date as date_column FROM transactions
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        UNION
        SELECT date_redeemed as date_column FROM redeem
        WHERE date_redeemed >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ) as combined_dates
    ORDER BY date ASC";
$dates_result = $conn->query($query);

if ($dates_result->num_rows > 0) {
    while ($row = $dates_result->fetch_assoc()) {
        $date = $row['date'];
        $formatted_date = date('M d', strtotime($date));
        $chart_labels[] = $formatted_date;

        // Fetch transaction data for this date
        $trans = $conn->query("SELECT SUM(amount_paid) as total_paid, SUM(points_given) as total_points 
                               FROM transactions 
                               WHERE DATE(transaction_date) = '$date'")->fetch_assoc();
        $chart_trans_paid[] = $trans['total_paid'] ?? 0;
        $chart_points_given[] = $trans['total_points'] ?? 0;

        // Fetch redemption data for this date
        $redeem = $conn->query("SELECT SUM(points_redeemed) as total_redeemed 
                                FROM redeem 
                                WHERE DATE(date_redeemed) = '$date'")->fetch_assoc();
        $chart_points_redeemed[] = $redeem['total_redeemed'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Analytics & Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background: #eee;
            font-family: 'Roboto', sans-serif;
            color: #e0e0e0;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 96%;
            padding: 0;
            margin: 0 auto;
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
            background: linear-gradient(90deg, rgb(192, 226, 250) 0%, rgb(247, 221, 252) 100%);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card1 .card-body {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            align-items: center;
            justify-content: center;
            /* background-color: rgb(255, 255, 255); */
        }

        .card1 .stat-box {
            width: 45%;
        }

        .card-header {
            /* background: linear-gradient(90deg, #3b3b5c 0%, #4a4a70 100%); */
            color: rgb(0, 0, 0);
            padding: 12px 15px;
            font-size: 16px;
            font-weight: 500;
        }

        .card-body {
            padding: 15px;
        }

        .stat-box {
            background: #353554;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
        }

        .stat-box h6 {
            font-size: 15px;
            color: #b0b0d0;
            margin-bottom: 5px;
        }

        .stat-box p {
            font-size: 18px;
            color: #51cbee;
            margin: 0;
        }

        canvas {
            max-width: 100%;
            height: 300px;
        }

        small {
            font-size: 11px;
            color: #a0a0c0;
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
        <!-- User Signups -->
        <div class="card card1">
            <div class="card-header">New User Signups</div>
            <div class="card-body">
                <div class="stat-box">
                    <h6>Today</h6>
                    <p><?= $users_today ?></p>
                </div>
                <div class="stat-box">
                    <h6>Last 7 Days</h6>
                    <p><?= $users_7days ?></p>
                </div>
                <div class="stat-box">
                    <h6>Last 15 Days</h6>
                    <p><?= $users_15days ?></p>
                </div>
                <div class="stat-box">
                    <h6>Last 30 Days</h6>
                    <p><?= $users_30days ?></p>
                </div>
            </div>
        </div>

        <!-- Transactions Summary -->
        <div class="card">
            <div class="card-header">Transactions Summary</div>
            <div class="card-body">
                <div class="stat-box">
                    <h6>Today</h6>
                    <p>₹<?= number_format($trans_daily['total_paid'] ?? 0, 2) ?> | <?= number_format($trans_daily['total_points'] ?? 0) ?> pts</p>
                </div>
                <div class="stat-box">
                    <h6>Last 7 Days</h6>
                    <p>₹<?= number_format($trans_weekly['total_paid'] ?? 0, 2) ?> | <?= number_format($trans_weekly['total_points'] ?? 0) ?> pts</p>
                </div>
                <div class="stat-box">
                    <h6>Last 30 Days</h6>
                    <p>₹<?= number_format($trans_monthly['total_paid'] ?? 0, 2) ?> | <?= number_format($trans_monthly['total_points'] ?? 0) ?> pts</p>
                </div>
            </div>
        </div>

        <!-- Redemptions Summary -->
        <div class="card">
            <div class="card-header">Points Redeemed</div>
            <div class="card-body">
                <div class="stat-box">
                    <h6>Today</h6>
                    <p><?= number_format($redeem_daily ?? 0) ?> pts</p>
                </div>
                <div class="stat-box">
                    <h6>Last 7 Days</h6>
                    <p><?= number_format($redeem_weekly ?? 0) ?> pts</p>
                </div>
                <div class="stat-box">
                    <h6>Last 30 Days</h6>
                    <p><?= number_format($redeem_monthly ?? 0) ?> pts</p>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card">
            <div class="card-header">30-Day Trends</div>
            <div class="card-body">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    <br>
    <script>
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>, // e.g., ['Feb 1', 'Feb 2', ...]
                datasets: [{
                        label: 'Amount(₹)',
                        data: <?= json_encode($chart_trans_paid) ?>,
                        backgroundColor: 'rgba(81, 203, 238, 0.8)',
                        borderColor: '#51cbee',
                        borderWidth: 1
                    },
                    {
                        label: 'Points',
                        data: <?= json_encode($chart_points_given) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: '#ff6384',
                        borderWidth: 1
                    },
                    {
                        label: 'Redeemed',
                        data: <?= json_encode($chart_points_redeemed) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: '#36a2eb',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#00000',
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: 'Values',
                            color: '#000000',
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            color: '#000000',
                            font: {
                                size: 8
                            },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        title: {
                            display: true,
                            text: 'Date',
                            color: '#000000',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#000',
                            font: {
                                size: 10 // Kept at 8 as requested
                            }
                        },
                        position: 'top',
                        padding: {
                            bottom: 15 // Adds 15px of padding below the legend
                        }
                    }
                },
                barThickness: 8,
                maxBarThickness: 10
            }
        });
    </script>
</body>

</html>