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

// Access control (only admin role)
if ($role !== 'admin') {
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
            <p class="denied-message">Only admins can access this page.</p>
            <a href="admindash.php" class="btn">Back</a>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = isset($_POST['priority']) && in_array($_POST['priority'], ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';

    $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, message, priority) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $title, $message, $priority);
    $stmt->execute();
    header("Location: announcements.php?success=1");
    exit();
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $delete_id, $admin_id);
    $stmt->execute();
    header("Location: announcement.php?deleted=1");
    exit();
}

// Fetch existing announcements
$announcements = $conn->query("SELECT id, title, message, priority, status, created_at, expires_at 
    FROM announcements WHERE admin_id = $admin_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Announcements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0e6f6 0%, #e6f0fa 100%);
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            margin-top: 20px;
        }

        .announcement-card {
            border-radius: 0px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .input-section {
            padding: 15px;
            background: linear-gradient(90deg, #ffccd5 0%, #89c4f4 100%);
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
        }

        .title-input {
            width: 100%;
            padding: 12px;
            font-size: 18px;
            font-weight: 500;
            color: #333;
            background: #fff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .title-input:focus {
            outline: none;
            box-shadow: 0 0 8px rgba(137, 196, 244, 0.3);
        }

        .title-input::placeholder {
            color: #777;
            font-weight: 500;
        }

        .message-input {
            width: 100%;
            height: 100px;
            padding: 12px;
            font-size: 16px;
            color: #555;
            background: #f0f7fa;
            border: none;
            border-radius: 10px;
            resize: none;
            margin-bottom: 10px;
        }

        .message-input:focus {
            outline: none;
            box-shadow: 0 0 8px rgba(137, 196, 244, 0.3);
        }

        .message-input::placeholder {
            color: #777;
        }

        .priority-button {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .priority-select {
            width: 70%;
            padding: 10px;
            font-size: 14px;
            color: #555;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .priority-select:focus {
            outline: none;
            border-color: #89c4f4;
        }

        .post-btn {
            width: 30%;
            padding: 10px;
            background: linear-gradient(90deg, #a9dfbf 0%, #d4a5e6 100%);
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .post-btn:hover {
            opacity: 0.9;
        }

        .announcement-list {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }

        .announcement-item {
            background: #f0f7fa;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.1);
            position: relative;
            /* For absolute positioning of delete icon */
        }

        .announcement-item h5 {
            font-size: 16px;
            color: #555;
            margin: 0 0 5px 0;
            font-weight: 500;
        }

        .announcement-item p {
            font-size: 14px;
            color: #666;
            margin: 0 0 5px 0;
        }

        .announcement-item small {
            font-size: 12px;
            color: #888;
        }

        .priority-low {
            border-left: 4px solid #a9dfbf;
        }

        .priority-normal {
            border-left: 4px solid #89c4f4;
        }

        .priority-high {
            border-left: 4px solid #ffccd5;
        }

        .success-message {
            text-align: center;
            color: #a9dfbf;
            font-size: 14px;
            padding: 10px;
            background: #fff;
            border-radius: 10px;
            margin: 10px 0;
        }

        .delete-btn {
            position: absolute;
            top: -10px;
            right: 10px;
            width: 10px;
            background: none;
            border: none;
            color: #ff9999;
            /* Pastel red */
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s ease;
            border-radius: 0;
            box-shadow: none;
        }

        .delete-btn:hover {
            color: #ff4444;
            /* Darker red on hover */
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
        <div class="announcement-card">
            <div class="input-section">
                <form method="POST">
                    <input type="text" name="title" class="title-input" placeholder="Announcement Title" required>
                    <textarea name="message" class="message-input" placeholder="Type your message here" required></textarea>
                    <label for="priority" style="display: block; text-align: left; margin-left: 10px; font-size: 18px; font-weight: 500; color: #555;">Priority:</label>
                    <div class="priority-button">
                        <select name="priority" class="priority-select">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                        </select>
                        <button type="submit" class="post-btn">Post</button>
                    </div>
                </form>
                <?php if (isset($_GET['success'])) { ?>
                    <div class="success-message">Announcement posted successfully!</div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="announcement-list">
        <?php while ($ann = $announcements->fetch_assoc()) { ?>
            <div class="announcement-item priority-<?php echo $ann['priority']; ?>">
                <h5><?php echo htmlspecialchars($ann['title']); ?></h5>
                <p><?php echo htmlspecialchars($ann['message']); ?></p>
                <small>Status: <?php echo $ann['status']; ?></small><br>
                <small>Posted: <?php echo $ann['created_at']; ?> | Expires: <?php echo $ann['expires_at']; ?></small>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $ann['id']; ?>">
                    <button type="submit" class="delete-btn" title="Delete Announcement">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
        <?php } ?>
    </div>
</body>

</html>