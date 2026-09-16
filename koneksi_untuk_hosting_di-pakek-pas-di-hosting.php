<?php

$host = "localhost";
$user = "readi560_db2";
$pass = "cyberbinus0809";
$db   = "readi560_db2";

$koneksi = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi Gagal: " . $koneksi->connect_error);
}

// Set charset untuk UTF-8
$koneksi->set_charset("utf8");

// Fungsi untuk membersihkan input
function bersihkan_input($data) {
    global $koneksi;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk query select
function query($sql) {
    global $koneksi;
    $result = $koneksi->query($sql);
    
    if (!$result) {
        die("Query Error: " . $koneksi->error);
    }
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// Fungsi untuk insert, update, delete
function execute($sql) {
    global $koneksi;
    $result = $koneksi->query($sql);
    
    if (!$result) {
        die("Query Error: " . $koneksi->error);
    }
    
    return $result;
}

// Fungsi untuk menggunakan prepared statement (lebih aman)
function prepared_query($sql, $types, $params) {
    global $koneksi;
    
    $stmt = $koneksi->prepare($sql);
    
    if (!$stmt) {
        die("Prepare Error: " . $koneksi->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        die("Execute Error: " . $stmt->error);
    }
    
    return $stmt;
}

?>
