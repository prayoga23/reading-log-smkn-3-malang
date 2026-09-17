<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/inc_koneksi.php';

header('Content-Type: application/json; charset=UTF-8');

function ensure_penilaian_table($koneksi)
{
    $result = $koneksi->query(
        "CREATE TABLE IF NOT EXISTS penilaian_guru (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            resume_id INT UNSIGNED NOT NULL,
            guru_id INT UNSIGNED NOT NULL,
            score INT DEFAULT NULL,
            feedback LONGTEXT NOT NULL,
            saran LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_resume_guru (resume_id, guru_id),
            FOREIGN KEY (resume_id) REFERENCES resume_literasi(id) ON DELETE CASCADE,
            FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    // Tambah kolom score jika belum ada
    $koneksi->query("ALTER TABLE penilaian_guru ADD COLUMN IF NOT EXISTS score INT DEFAULT NULL AFTER guru_id");

    return $result;
}

// 1. Cek autentikasi & peran Guru
if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Guru') {
    echo json_encode([
        'success' => false,
        'message' => 'Sesi tidak valid atau Anda bukan Guru. Silakan login kembali.'
    ]);
    exit;
}

// 2. Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid. Hanya menerima POST.'
    ]);
    exit;
}

// 3. Validasi input
$resume_id = isset($_POST['resume_id']) ? (int) $_POST['resume_id'] : 0;
$feedback  = isset($_POST['feedback']) ? trim((string) $_POST['feedback']) : '';
$score     = (isset($_POST['score']) && $_POST['score'] !== '') ? (int) $_POST['score'] : null;
$saran     = isset($_POST['saran']) && trim((string) $_POST['saran']) !== '' ? trim((string) $_POST['saran']) : null;

if ($resume_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Resume ID tidak valid.'
    ]);
    exit;
}

if ($feedback === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Feedback tidak boleh kosong.'
    ]);
    exit;
}

$words = preg_split('/\s+/', $feedback, -1, PREG_SPLIT_NO_EMPTY);
if (count($words) < 4) {
    echo json_encode([
        'success' => false,
        'message' => 'Feedback minimal harus 4 kata!'
    ]);
    exit;
}

$guru_id = (int) $_SESSION['user_id'];
$guru_kelas = '';

// Ambil kelas yang diampu guru
$guru_stmt = $koneksi->prepare('SELECT kelas FROM users WHERE id = ? AND jabatan = ? LIMIT 1');
if ($guru_stmt) {
    $jabatan = 'Guru';
    $guru_stmt->bind_param('is', $guru_id, $jabatan);
    $guru_stmt->execute();
    $guru_result = $guru_stmt->get_result();
    $guru_row = $guru_result ? $guru_result->fetch_assoc() : null;
    $guru_kelas = (string) ($guru_row['kelas'] ?? '');
    $guru_stmt->close();
}

if ($guru_kelas === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Data kelas guru tidak ditemukan.'
    ]);
    exit;
}

// Pastikan tabel penilaian_guru tersedia
if (!ensure_penilaian_table($koneksi)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyiapkan tabel penilaian: ' . $koneksi->error
    ]);
    exit;
}

// 4. Verifikasi bahwa resume milik siswa dalam kelas yang diampu guru
$check_stmt = $koneksi->prepare(
    "SELECT r.id 
     FROM resume_literasi r
     JOIN users u ON r.user_id = u.id
     WHERE r.id = ? AND u.kelas = ? AND u.jabatan = 'Siswa'
     LIMIT 1"
);

if (!$check_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memvalidasi resume: ' . $koneksi->error
    ]);
    exit;
}

$check_stmt->bind_param('is', $resume_id, $guru_kelas);
$check_stmt->execute();
$check_res = $check_stmt->get_result();

if (!$check_res || $check_res->num_rows === 0) {
    $check_stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Resume tidak ditemukan atau bukan milik siswa kelas Anda.'
    ]);
    exit;
}
$check_stmt->close();

// 5. Simpan / Perbarui penilaian
if ($score !== null || $saran !== null) {
    $stmt = $koneksi->prepare(
        "INSERT INTO penilaian_guru (resume_id, guru_id, score, feedback, saran)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            feedback = VALUES(feedback),
            score = COALESCE(VALUES(score), score),
            saran = COALESCE(VALUES(saran), saran),
            updated_at = NOW()"
    );
    if ($stmt) {
        $stmt->bind_param('iiiss', $resume_id, $guru_id, $score, $feedback, $saran);
    }
} else {
    $stmt = $koneksi->prepare(
        "INSERT INTO penilaian_guru (resume_id, guru_id, feedback)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            feedback = VALUES(feedback),
            updated_at = NOW()"
    );
    if ($stmt) {
        $stmt->bind_param('iis', $resume_id, $guru_id, $feedback);
    }
}

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyiapkan query penyimpanan: ' . $koneksi->error
    ]);
    exit;
}

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode([
        'success' => true,
        'message' => 'Penilaian berhasil disimpan!'
    ]);
} else {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan penilaian: ' . $err
    ]);
}
