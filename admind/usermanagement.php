<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../config.php'; // Include DB connection file

// Pagination settings
$limit = 10; // Users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total users count
$total_result = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Fetch users for current page
$sql = "SELECT user_id, name, points_balance FROM users LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            text-align: center;
        }

        .searchbox {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container2 {
            display: flex;
            gap: 5px;
        }

        #searchInput {
            width: 300px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #searchFilter {
            width: 120px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 15px;
        }

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .view-link {
            text-decoration: none;
            color: #007bff;
            font-size: 18px;
        }

        .view-link:hover {
            color: #0056b3;
        }

        .pagination {
            margin: 20px 0;
        }

        .pagination a {
            padding: 4px 8px;
            margin: 5px;
            text-decoration: none;
            background: #007bff;
            color: white;
            border-radius: 5px;
        }

        .pagination a:hover {
            background: #0056b3;
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

    <section>
        <div class="searchbox">
            <div class="container2 container">
                <input type="text" id="searchInput" placeholder="Search..." oninput="searchUsers()">
                <select id="searchFilter">
                    <option value="user_id">User ID</option>
                    <option value="name">Name</option>
                    <option value="all">Points</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        <table id="userTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">User ID ⬍</th>
                    <th onclick="sortTable(1)">Name ⬍</th>
                    <th onclick="sortTable(2)">Total Points ⬍</th>
                    <!-- <th>View</th> -->
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row["user_id"] ?></td>
                        <td><?= $row["name"] ?></td>
                        <td style="text-align: right;"><?= $row["points_balance"] ?></td>
                        <!-- <td>
                            <a href="userprofile.php?user_id=<?= $row['user_id'] ?>" class="view-link">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td> -->
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" <?= ($i == $page) ? 'style="background: #0056b3;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    </section>
    <script>
        // 🔍 Live Search Function
        function searchUsers() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let filter = document.getElementById("searchFilter").value;
            let table = document.getElementById("userTable");
            let rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                let cells = rows[i].getElementsByTagName("td");
                let match = false;

                if (filter === "all") {
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            match = true;
                            break;
                        }
                    }
                } else {
                    let index = filter === "user_id" ? 0 :
                        filter === "name" ? 1 :
                        filter === "email" ? 2 :
                        filter === "phone" ? 3 : -1;

                    if (index !== -1 && cells[index].textContent.toLowerCase().includes(input)) {
                        match = true;
                    }
                }

                rows[i].style.display = match ? "" : "none";
            }
        }

        // 📌 Sorting Function
        function sortTable(columnIndex) {
            let table = document.getElementById("userTable");
            let rows = Array.from(table.rows).slice(1);
            let ascending = table.dataset.sortOrder !== "asc";

            rows.sort((a, b) => {
                let valA = a.cells[columnIndex].textContent.trim().toLowerCase();
                let valB = b.cells[columnIndex].textContent.trim().toLowerCase();

                return isNaN(valA) || isNaN(valB) ?
                    valA.localeCompare(valB) * (ascending ? 1 : -1) :
                    (parseFloat(valA) - parseFloat(valB)) * (ascending ? 1 : -1);
            });

            table.dataset.sortOrder = ascending ? "asc" : "desc";
            rows.forEach(row => table.appendChild(row));
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>