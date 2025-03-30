<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';

// Fetch user info
$sql = "SELECT user_id, name, email, phone, profile_pic, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$currentName = $user['name'] ?? 'User Name';
$currentEmail = $user['email'] ?? '';
$currentPhone = $user['phone'] ?? '';
$profilePic = $user['profile_pic'] ?? 'profile/default.png';

// Construct the full URL for the profile picture
if ($profilePic !== 'profile/default.png') {
    $profilePic = 'profile/uploads/' . $profilePic;
}
$currentPasswordHash = $user['password'] ?? ''; // Hashed password from DB

// Handle image upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'profile/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['profile_pic'];
    $fileName = basename($file['name']);
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

    if ($fileError === 0 && in_array($fileExt, $allowedExts)) {
        if ($fileSize <= 2 * 1024 * 1024) { // Limit file size to 2MB
            $newFileName = uniqid('profile_', true) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                // Update profile picture in the database
                $sql = "UPDATE users SET profile_pic = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newFileName, $userId);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Profile picture updated successfully.', 'url' => $uploadDir . $newFileName]);
                    exit();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile picture in the database.']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload the file.']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File size exceeds the limit of 2 MB.']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type or upload error.']);
        exit();
    }
}

// Handle image removal via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_image'])) {
    // Get the current profile picture path from the database
    $sql = "SELECT profile_pic FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $currentProfilePic = $user['profile_pic'] ?? '';

    // If there's a profile picture, delete the file from the server
    if ($currentProfilePic !== '' && $currentProfilePic !== 'profile/default.png') {
        $uploadDir = 'profile/uploads/';
        $filePath = $uploadDir . $currentProfilePic;

        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file from the server
        }
    }

    // Update the database with the default image path
    $defaultImagePath = 'profile/default.png';
    $sql = "UPDATE users SET profile_pic = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $defaultImagePath, $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile picture removed successfully.', 'url' => $defaultImagePath]);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove profile picture.']);
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');

    // Validate inputs
    if (empty($newName)) {
        echo json_encode(['status' => 'error', 'message' => 'Name cannot be empty.']);
        exit();
    } elseif ($currentEmail === '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email.']);
        exit();
    } else {
        // Check if the email already exists in the database (excluding the current user)
        if ($currentEmail === '' && $newEmail !== '') {
            $sql = "SELECT COUNT(*) AS count FROM users WHERE email = ? AND user_id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newEmail, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                echo json_encode(['status' => 'error', 'message' => 'This email is already in use.']);
                exit();
            }
        }

        // Update user data
        $updateFields = [];
        $updateParams = [];
        $types = '';

        if ($newName !== $currentName) {
            $updateFields[] = "name = ?";
            $updateParams[] = $newName;
            $types .= 's';
        }
        if ($currentEmail === '' && $newEmail !== '') {
            $updateFields[] = "email = ?";
            $updateParams[] = $newEmail;
            $types .= 's';
        }

        if (!empty($updateFields)) {
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $updateParams[] = $userId;
            $types .= 'i';
            $stmt->bind_param($types, ...$updateParams);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully!']);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating settings.']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No changes to save.']);
            exit();
        }
    }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* Custom Toastify Styles */
        .toastify {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            font-size: 16px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .formm2 {
            display: flex;
            align-items: center;
            flex-direction: column;
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.62);
            padding: 10px;
            border-radius: 30px;
            /* height: 100px; */
        }

        .formm2 h3 {
            font-size: 20px;
            /* margin-bottom: 30px; */
        }

        .formm2 button {
            margin: 0 auto;
            margin-top: 20px;
            margin-bottom: 10px;
            width: 200px;
            background-color: rgba(255, 255, 255, 0.87);
            padding: 5px 20px;
            text-decoration: none;
            color: #007bff;
            font-size: 16px;
            font-weight: bold;
        }

        .formm2 a:hover {
            text-decoration: underline;
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

    <div class="settings-container">
        <!-- Profile Section -->
        <div class="profiles">
            <img id="profile-pic" src="<?php echo htmlspecialchars($profilePic); ?>" alt="User">
            <div class="profile-image">
                <div class="image-actions">
                    <label for="upload-profile-pic" class="upload-btn">Upload</label>
                    <input type="file" id="upload-profile-pic" accept="image/*" style="display: none;">
                    <button id="remove-profile-pic" class="remove-btn">Remove</button>
                </div>
            </div>
            <!-- Preview Section -->
            <div id="image-preview-container" style="display: none; margin-top: 10px;">
                <h4>Preview Image</h4>
                <img id="image-preview" src="#" alt="Preview">
                <br>
                <button id="confirm-upload" class="save-btn">Save</button>
                <button id="cancel-upload" class="cancel-btn">Cancel</button>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST">
            <div class="formm">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($currentName); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($currentEmail); ?>" <?php echo $currentEmail ? 'readonly' : ''; ?>>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" value="<?php echo htmlspecialchars($currentPhone); ?>" readonly>
                </div>
            </div>
            <div class="formm2">
                <h3>Update Password</h3>
                <button onclick="window.location.href = '../forgot.php';">Click Here</button>
            </div>
            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>
    <br>
    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house"></i></a>
            <a href="notification.php" id="bell-icon" class="bell-wrapper">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-indicator"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php"><i class="fa-solid fa-gear active"></i></a>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            const profilePic = $('#profile-pic');
            const uploadInput = $('#upload-profile-pic');
            const removeButton = $('#remove-profile-pic');
            const previewContainer = $('#image-preview-container');
            const imagePreview = $('#image-preview');
            const confirmButton = $('#confirm-upload');
            const cancelButton = $('#cancel-upload');
            let selectedFile = null;

            // Function to show toast notifications
            function showToast(message, type = 'success') {
                Toastify({
                    text: message,
                    duration: 3000, // Duration in milliseconds
                    // close: true,
                    gravity: 'top', // Position: 'top' or 'bottom'
                    position: 'right', // Alignment: 'left', 'center', or 'right'
                    backgroundColor: type === 'error' ? '#f44336' : '#4caf50', // Red for errors, green for success
                }).showToast();
            }

            // Step 1: Handle file selection and show preview
            uploadInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        imagePreview.attr('src', e.target.result);
                        previewContainer.show();
                    };

                    reader.readAsDataURL(file);
                    selectedFile = file;
                }
            });

            // Step 2: Handle cancel action
            cancelButton.on('click', function() {
                previewContainer.hide();
                uploadInput.val(''); // Clear the file input
                selectedFile = null;
                showToast('Image upload canceled.');
            });

            // Step 3: Handle confirm action and upload the file
            confirmButton.on('click', function() {
                if (!selectedFile) {
                    showToast('No image selected.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('profile_pic', selectedFile);

                $.ajax({
                    url: 'settings.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            profilePic.attr('src', data.url);
                            showToast(data.message);
                        } else {
                            showToast(data.message, 'error');
                        }
                        previewContainer.hide();
                        uploadInput.val(''); // Clear the file input
                        selectedFile = null;
                    },
                    error: function() {
                        showToast('An error occurred while uploading the image.', 'error');
                    }
                });
            });

            // Handle image removal
            removeButton.on('click', function() {
                $.ajax({
                    url: 'settings.php',
                    type: 'POST',
                    data: {
                        remove_image: true
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            profilePic.attr('src', data.url); // Update the src attribute with the default image URL
                            showToast(data.message);
                        } else {
                            showToast(data.message, 'error');
                        }
                    },
                    error: function() {
                        showToast('An error occurred while removing the image.', 'error');
                    }
                });
            });

            // Handle form submission (for settings update)
            $('form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                $.ajax({
                    url: 'settings.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            showToast(data.message);
                        } else {
                            showToast(data.message, 'error');
                        }
                    },
                    error: function() {
                        showToast('An error occurred while updating settings.', 'error');
                    }
                });
            });

            $.ajax({
                url: 'settings.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast(data.message);
                    } else {
                        showToast(data.message, 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while updating settings.', 'error');
                }
            });
        });
    </script>
</body>

</html>