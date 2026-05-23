<?php require_once __DIR__ . '/../../config/db.php'; 
function get_all_deposit_requests() { global $conn; return mysqli_query($conn, 'SELECT * FROM deposit_requests'); } ?>