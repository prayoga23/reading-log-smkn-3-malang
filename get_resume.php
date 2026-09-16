<?php
session_start();
include __DIR__ . '/inc_koneksi.php';

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
    
    // Tambah kolom score jika belum ada (untuk tabel yang sudah ada)
    $koneksi->query("ALTER TABLE penilaian_guru ADD COLUMN IF NOT EXISTS score INT DEFAULT NULL AFTER guru_id");
    
    return $result;
}

// Cek apakah user adalah guru
if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Guru') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'data' => null];

if (isset($_GET['id'])) {
    $resume_id = (int) $_GET['id'];

    if ($resume_id <= 0) {
        $response['message'] = 'Resume ID tidak valid';
    } else {
        $guru_id = (int) $_SESSION['user_id'];
        $guru_kelas = '';

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
            $response['message'] = 'Data kelas guru tidak ditemukan';
        } elseif (!ensure_penilaian_table($koneksi)) {
            $response['message'] = 'Gagal menyiapkan tabel penilaian: ' . $koneksi->error;
        } else {
            $stmt = $koneksi->prepare(
                "SELECT
                    r.*,
                    u.nama AS siswa_nama,
                    u.email,
                    u.kelas,
                    u.nis_nip,
                    pg.feedback
                 FROM resume_literasi r
                 JOIN users u ON r.user_id = u.id
                 LEFT JOIN penilaian_guru pg ON pg.resume_id = r.id AND pg.guru_id = ?
                 WHERE r.id = ? AND u.kelas = ? AND u.jabatan = 'Siswa'
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param('iis', $guru_id, $resume_id, $guru_kelas);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $data = $result->fetch_assoc();
                    $data['tanggal_formatted'] = date('d F Y H:i', strtotime($data['tanggal']));
                    $data['feedback'] = (string) ($data['feedback'] ?? '');
                    $response['success'] = true;
                    $response['data'] = $data;
                } else {
                    $response['message'] = 'Resume tidak ditemukan atau bukan milik kelas Anda';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Gagal menyiapkan query resume';
            }
        }
    }
} else {
    $response['message'] = 'Resume ID tidak diberikan';
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($response);
?>
