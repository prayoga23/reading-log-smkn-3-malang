<?php
// Script untuk generate hash password yang benar untuk 'siswa123'
$password = 'siswa123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "<h1>Hash Password untuk 'siswa123'</h1>";
echo "<p>Password: <strong>siswa123</strong></p>";
echo "<p>Hash yang benar:</p>";
echo "<code>" . htmlspecialchars($hash) . "</code>";

// Tampilkan juga untuk verifikasi
echo "<h2>Verifikasi Password</h2>";
if (password_verify($password, $hash)) {
    echo "<p style='color:green; font-weight:bold;'>Password valid!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>Password tidak valid!</p>";
}
?>
