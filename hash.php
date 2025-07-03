<?php
// public_html/hash.php

require_once __DIR__ . '/includes/hash.php'; // ✅ correct path based on your setup

$plain_password = 'BenTech@#5428'; // 🔒 change to your desired password
$hashed_password = hash_password($plain_password);

echo "Hashed Password: " . $hashed_password;
?>
