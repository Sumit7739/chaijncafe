<?php
$password = "11111111";
$stored_hash = "$2y$10$BMnLaMD7ySVq0cOnSYyBX.lEENZEBvFD2uy8ACFapp.90Osqld9lO";

echo "Verification result: " . (password_verify($password, $stored_hash) ? 'true' : 'false') . "<br>";
?>