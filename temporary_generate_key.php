<?php
// temporary_generate_key.php
$key = openssl_random_pseudo_bytes(32); // 256-bit key for AES-256
echo base64_encode($key);
?>