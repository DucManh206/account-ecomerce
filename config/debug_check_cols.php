<?php
require_once __DIR__ . '/config/db.php';
$r = mysqli_query($conn, 'DESCRIBE products');
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ': ' . $row['Type'] . "\n";
}
