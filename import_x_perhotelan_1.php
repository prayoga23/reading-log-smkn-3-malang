<?php
/**
 * Script Import Data X Perhotelan 1, Wali Kelas, dan 35 Siswa
 * Sumber Data: A. KELAS X 2026-2027.xlsx (Sheet: X PH 1)
 *
 * Jalankan via CLI:
 *   php import_x_perhotelan_1.php
 * Atau via Browser:
 *   http://localhost/inc/import_x_perhotelan_1.php
 */

header('Content-Type: text/html; charset=utf-8');

// Coba hubungkan ke database
$koneksi = null;

// Cek koneksi dari inc_koneksi.php jika ada
if (file_exists(__DIR__ . '/inc_koneksi.php')) {
    include_once __DIR__ . '/inc_koneksi.php';
}

// Fallback jika belum terhubung
if (!$koneksi || $koneksi->connect_error) {
    $hosts = ['127.0.0.1', 'localhost', 'mysql-server'];
    $users = ['root', 'readi560_db2'];
    $passwords = ['', 'rootpass', 'cyberbinus0809'];
    $dbs = ['inc', 'readi560_db2'];

    foreach ($hosts as $h) {
        foreach ($users as $u) {
            foreach ($passwords as $p) {
                foreach ($dbs as $d) {
                    try {
                        $test = @new mysqli($h, $u, $p, $d);
                        if (!$test->connect_error) {
                            $koneksi = $test;
                            break 4;
                        }
                    } catch (Throwable $e) {
                        // Lanjutkan coba kombinasi berikutnya
                    }
                }
            }
        }
    }
}

$is_cli = (php_sapi_name() === 'cli');

function output_msg($msg, $is_cli, $color = 'black') {
    if ($is_cli) {
        echo strip_tags($msg) . "\n";
    } else {
        echo "<div style='font-family: monospace; margin: 4px 0; color: {$color};'>{$msg}</div>";
    }
}

if (!$koneksi || $koneksi->connect_error) {
    output_msg("❌ [ERROR] Gagal terhubung ke database MySQL. Pastikan MySQL berjalan di XAMPP / Docker.", $is_cli, 'red');
    output_msg("💡 Anda juga bisa mengimpor file 'data_x_perhotelan_1.sql' langsung via phpMyAdmin.", $is_cli, '#555');
    exit;
}

$koneksi->set_charset("utf8mb4");

output_msg("✅ <strong>Database terkoneksi dengan baik.</strong>", $is_cli, 'green');

// 1. Data Kelas
$nama_kelas = 'X Perhotelan 1';
$tingkat    = 'X';
$jurusan    = 'Perhotelan';
$wali_kelas = 'SOEKARDI ARIF WIDIJAYANTO, S.Pd.';

$stmt = $koneksi->prepare("
    INSERT INTO `kelas` (`nama_kelas`, `tingkat`, `jurusan`, `wali_kelas`)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        `jurusan` = VALUES(`jurusan`),
        `wali_kelas` = VALUES(`wali_kelas`)
");
$stmt->bind_param('ssss', $nama_kelas, $tingkat, $jurusan, $wali_kelas);
if ($stmt->execute()) {
    output_msg("✅ [KELAS] Data kelas '{$nama_kelas}' (Wali Kelas: {$wali_kelas}) berhasil disimpan.", $is_cli, 'green');
} else {
    output_msg("❌ [KELAS] Gagal menyimpan kelas: " . $stmt->error, $is_cli, 'red');
}
$stmt->close();

// 2. Data Wali Kelas
$wali_email = 'soekardi.arif@example.com';
$wali_pass_hash = '$2y$10$7ehf9UIclph7QsywMBph/eswrNme4uYsvsDXbEjpY41kaF7qRGYFi'; // guru123
$wali_jabatan = 'Guru';
$wali_nip = 'GURU003';
$is_active = 1;

$stmt = $koneksi->prepare("
    INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        `nama` = VALUES(`nama`),
        `jabatan` = VALUES(`jabatan`),
        `kelas` = VALUES(`kelas`),
        `jurusan` = VALUES(`jurusan`),
        `is_active` = VALUES(`is_active`)
");
$stmt->bind_param('sssssssi', $wali_kelas, $wali_email, $wali_pass_hash, $wali_jabatan, $wali_nip, $nama_kelas, $jurusan, $is_active);
if ($stmt->execute()) {
    output_msg("✅ [WALI KELAS] Akun Wali Kelas '{$wali_kelas}' ({$wali_email}) berhasil disimpan.", $is_cli, 'green');
} else {
    output_msg("❌ [WALI KELAS] Gagal menyimpan wali kelas: " . $stmt->error, $is_cli, 'red');
}
$stmt->close();

// 3. Data 35 Siswa
$siswa_list = [
    ['no' => 1,  'nipd' => '145312351092', 'nama' => 'ABYM REYNO TIRTA RAZKY', 'gender' => 'L', 'ket' => ''],
    ['no' => 2,  'nipd' => '150972494092', 'nama' => 'ACHMAD EKA ARDHIANSAH', 'gender' => 'L', 'ket' => ''],
    ['no' => 3,  'nipd' => '150982495092', 'nama' => 'ADE INDIRA ALFIA PUTRI', 'gender' => 'P', 'ket' => ''],
    ['no' => 4,  'nipd' => '151012498092', 'nama' => 'AHMAD FAISHAL BARI', 'gender' => 'L', 'ket' => ''],
    ['no' => 5,  'nipd' => '151062503092', 'nama' => 'ALEXANDRA KEVINA AURORA PUTRI', 'gender' => 'P', 'ket' => ''],
    ['no' => 6,  'nipd' => '151122509092', 'nama' => 'AQILLAH MARSYA MAYGISABILA', 'gender' => 'P', 'ket' => ''],
    ['no' => 7,  'nipd' => '151142511092', 'nama' => 'ARKA REYZA ALAMSHAH', 'gender' => 'L', 'ket' => ''],
    ['no' => 8,  'nipd' => '151232520092', 'nama' => 'BIMA PUTRA RAMADHAN', 'gender' => 'L', 'ket' => ''],
    ['no' => 9,  'nipd' => '151312528092', 'nama' => 'CLARISTA ANGGA ENI', 'gender' => 'P', 'ket' => ''],
    ['no' => 10, 'nipd' => '151362533092', 'nama' => 'DHESTARA ALFAREZI SYAHPUTRA', 'gender' => 'L', 'ket' => ''],
    ['no' => 11, 'nipd' => '151372534092', 'nama' => 'DIAJENG TRI HERNANDA PRAMESWARI', 'gender' => 'P', 'ket' => ''],
    ['no' => 12, 'nipd' => '151422539092', 'nama' => 'FATHIMAH AZZAHRA PUTRI WAHYUDA', 'gender' => 'P', 'ket' => ''],
    ['no' => 13, 'nipd' => '151482545092', 'nama' => 'HAIKAL ARKA NOVANSYAH', 'gender' => 'L', 'ket' => ''],
    ['no' => 14, 'nipd' => '151492546092', 'nama' => 'HAWA QUINEFIRA SHER FATHAYA', 'gender' => 'P', 'ket' => ''],
    ['no' => 15, 'nipd' => '151562553092', 'nama' => 'JOSHUA JULIAN NICO', 'gender' => 'L', 'ket' => 'Kr'],
    ['no' => 16, 'nipd' => '151572554092', 'nama' => 'JUVENA GLORY', 'gender' => 'P', 'ket' => 'Kr'],
    ['no' => 17, 'nipd' => '151632560092', 'nama' => 'LEONA AZARIA CELESTYN', 'gender' => 'P', 'ket' => ''],
    ['no' => 18, 'nipd' => '151682565092', 'nama' => 'MARSYELL', 'gender' => 'P', 'ket' => ''],
    ['no' => 19, 'nipd' => '151692566092', 'nama' => 'MARVEL RADITYA SAPUTRA', 'gender' => 'L', 'ket' => ''],
    ['no' => 20, 'nipd' => '151762573092', 'nama' => 'MUCH RIZKY RAHMAD DHANI', 'gender' => 'L', 'ket' => ''],
    ['no' => 21, 'nipd' => '151802577092', 'nama' => 'MUHAMMAD ALIEF FIRDAUSYAH PUTRA', 'gender' => 'L', 'ket' => ''],
    ['no' => 22, 'nipd' => '151842581092', 'nama' => 'MUHAMMAD FARIS ABDILLAH', 'gender' => 'L', 'ket' => ''],
    ['no' => 23, 'nipd' => '151882585092', 'nama' => 'MUHAMMAD KENZIE CHAMELO PUTRA SUBEKTI', 'gender' => 'L', 'ket' => ''],
    ['no' => 24, 'nipd' => '151932590092', 'nama' => 'NADIN MAULUDIYAH', 'gender' => 'P', 'ket' => ''],
    ['no' => 25, 'nipd' => '151942591092', 'nama' => 'NADYA EKA PUSPITASARI', 'gender' => 'P', 'ket' => ''],
    ['no' => 26, 'nipd' => '151962593092', 'nama' => 'NAJWA ANGGUN AZ ZAHRO', 'gender' => 'P', 'ket' => ''],
    ['no' => 27, 'nipd' => '151972594092', 'nama' => 'NARENDRA SYAHREZA PRABASWARA', 'gender' => 'L', 'ket' => ''],
    ['no' => 28, 'nipd' => '151992596092', 'nama' => 'NICHAZA NAFALIA ANANTA', 'gender' => 'P', 'ket' => ''],
    ['no' => 29, 'nipd' => '152012598092', 'nama' => 'OKTAVIA PUTRI ANGGRAENI', 'gender' => 'P', 'ket' => ''],
    ['no' => 30, 'nipd' => '152072604092', 'nama' => 'RAIHAN AKBAR SAPUTRA', 'gender' => 'L', 'ket' => ''],
    ['no' => 31, 'nipd' => '152102607092', 'nama' => 'RAVENSKA ALKHANSA SALWA', 'gender' => 'P', 'ket' => ''],
    ['no' => 32, 'nipd' => '152122609092', 'nama' => 'REVAN ARYA MAULANA', 'gender' => 'L', 'ket' => ''],
    ['no' => 33, 'nipd' => '152152612092', 'nama' => 'RIZKY BAGUS KRISNA SAPUTRA', 'gender' => 'L', 'ket' => ''],
    ['no' => 34, 'nipd' => '152202617092', 'nama' => 'SEILLA APRILLIA SARI', 'gender' => 'P', 'ket' => 'Hn'],
    ['no' => 35, 'nipd' => '152302627092', 'nama' => 'YULIA WULANDARI', 'gender' => 'P', 'ket' => '']
];

$siswa_pass_hash = '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6'; // siswa123
$siswa_jabatan = 'Siswa';

$stmt_siswa = $koneksi->prepare("
    INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        `nama` = VALUES(`nama`),
        `kelas` = VALUES(`kelas`),
        `jurusan` = VALUES(`jurusan`),
        `is_active` = VALUES(`is_active`)
");

$success_count = 0;
foreach ($siswa_list as $s) {
    $email_prefix = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $s['nama']));
    $email_prefix = trim($email_prefix, '.');
    $email = $email_prefix . '@example.com';
    $nis_nip = $s['nipd'];
    $nama = $s['nama'];

    $stmt_siswa->bind_param('sssssssi', $nama, $email, $siswa_pass_hash, $siswa_jabatan, $nis_nip, $nama_kelas, $jurusan, $is_active);
    if ($stmt_siswa->execute()) {
        $success_count++;
    } else {
        output_msg("⚠️ Gagal insert siswa {$nama}: " . $stmt_siswa->error, $is_cli, 'orange');
    }
}
$stmt_siswa->close();

output_msg("🎉 <strong>Selesai! Berhasil mengimpor {$success_count} dari " . count($siswa_list) . " siswa.</strong>", $is_cli, 'green');
?>
