<?php require_once __DIR__ . '/../../config/db.php'; 
function get_all_categories() { global $conn; return mysqli_query($conn, 'SELECT * FROM categories'); } ?>