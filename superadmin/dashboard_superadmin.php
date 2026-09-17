<?php
session_start();
include __DIR__ . '/../inc_koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Super Admin') {
    header('Location: ../index.php');
    exit;
}

$allowed_pages = ['guru-page', 'siswa-page', 'kelas-page', 'user-page', 'import-page'];
$active_page = $_GET['page'] ?? 'guru-page';
if (!in_array($active_page, $allowed_pages, true)) {
    $active_page = 'guru-page';
}

$per_page = 10;
$pg_guru  = max(1, (int) ($_GET['pg_guru']  ?? 1));
$pg_siswa = max(1, (int) ($_GET['pg_siswa'] ?? 1));
$pg_kelas = max(1, (int) ($_GET['pg_kelas'] ?? 1));
$pg_user  = max(1, (int) ($_GET['pg_user']  ?? 1));

$page_meta = [
    'guru-page' => [
        'title' => 'Kelola Data Guru',
        'desc' => 'Tambah, edit, dan hapus data guru langsung '
    ],
    'siswa-page' => [
        'title' => 'Kelola Data Siswa',
        'desc' => 'Tambah, edit, dan hapus data siswa langsung '
    ],
    'kelas-page' => [
        'title' => 'Kelola Data Kelas',
        'desc' => 'Tambah, edit, dan hapus data kelas pada database.'
    ],
    'user-page' => [
        'title' => 'Management User',
        'desc' => 'Kelola akun login semua user langsung '
    ],
    'import-page' => [
        'title' => 'Import Data Excel / CSV',
        'desc' => 'Import data Siswa, Wali Kelas (Guru), dan Kelas secara instan dari file Excel (.xlsx) atau CSV.'
    ]
];

function redirect_superadmin(string $page): void
{
    header('Location: dashboard_superadmin.php?page=' . urlencode($page));
    exit;
}

function set_flash_message(string $type, string $message): void
{
    $_SESSION['superadmin_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message(): ?array
{
    if (!isset($_SESSION['superadmin_flash'])) {
        return null;
    }

    $flash = $_SESSION['superadmin_flash'];
    unset($_SESSION['superadmin_flash']);
    return $flash;
}

function esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fetch_rows(mysqli $koneksi, string $sql): array
{
    $result = $koneksi->query($sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function find_user_by_id(mysqli $koneksi, int $id): ?array
{
    $stmt = $koneksi->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function get_display_password(?string $hashed_password, string $jabatan = ''): string
{
    if (empty($hashed_password)) {
        return '';
    }

    if (!str_starts_with($hashed_password, '$2')) {
        return $hashed_password;
    }

    $defaults = [
        'Siswa' => 'siswa123',
        'Guru' => 'guru123',
        'Super Admin' => 'admin123'
    ];

    if ($jabatan !== '' && isset($defaults[$jabatan]) && password_verify($defaults[$jabatan], $hashed_password)) {
        return $defaults[$jabatan];
    }

    foreach (['siswa123', 'guru123', 'admin123'] as $def) {
        if (password_verify($def, $hashed_password)) {
            return $def;
        }
    }

    return $hashed_password;
}

function find_kelas_by_id(mysqli $koneksi, int $id): ?array
{
    $stmt = $koneksi->prepare('SELECT * FROM kelas WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

$koneksi->query(
    "CREATE TABLE IF NOT EXISTS kelas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nama_kelas VARCHAR(100) NOT NULL,
        tingkat VARCHAR(10) NOT NULL,
        jurusan VARCHAR(100) NOT NULL,
        wali_kelas VARCHAR(150) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_kelas (tingkat, nama_kelas)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = $_POST['form_action'] ?? '';

    if ($form_action === 'save_guru') {
        $id = (int) ($_POST['guru_id'] ?? 0);
        $nama = bersihkan_input($_POST['nama'] ?? '');
        $email = bersihkan_input($_POST['email'] ?? '');
        $nis_nip = bersihkan_input($_POST['nis_nip'] ?? '');
        $kelas = bersihkan_input($_POST['kelas'] ?? '');
        $mapel = bersihkan_input($_POST['jurusan'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        if ($nama === '' || $email === '' || $nis_nip === '' || $kelas === '' || $mapel === '') {
            set_flash_message('error', 'Semua field guru wajib diisi.');
            redirect_superadmin('guru-page');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Format email guru tidak valid.');
            redirect_superadmin('guru-page');
        }

        if ($id > 0) {
            $current_guru = find_user_by_id($koneksi, $id);
            $is_password_changed = ($password !== '' && (!$current_guru || (!password_verify($password, $current_guru['password']) && $password !== $current_guru['password'])));

            if ($is_password_changed) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, password = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ? AND jabatan = ?');
                $jabatan = 'Guru';
                $stmt->bind_param('sssssssiss', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $mapel, $is_active, $id, $jabatan);
            } else {
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ? AND jabatan = ?');
                $jabatan = 'Guru';
                $stmt->bind_param('ssssssiss', $nama, $email, $jabatan, $nis_nip, $kelas, $mapel, $is_active, $id, $jabatan);
            }

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data guru berhasil diperbarui.');
            } else {
                set_flash_message('error', 'Gagal memperbarui data guru. Pastikan email dan NIP tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        } else {
            if ($password === '') {
                set_flash_message('error', 'Password guru wajib diisi saat menambah data baru.');
                redirect_superadmin('guru-page');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $jabatan = 'Guru';
            $stmt = $koneksi->prepare('INSERT INTO users (nama, email, password, jabatan, nis_nip, kelas, jurusan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssi', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $mapel, $is_active);

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data guru berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambahkan data guru. Pastikan email dan NIP tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        redirect_superadmin('guru-page');
    }

    if ($form_action === 'save_siswa') {
        $id = (int) ($_POST['siswa_id'] ?? 0);
        $nama = bersihkan_input($_POST['nama'] ?? '');
        $email = bersihkan_input($_POST['email'] ?? '');
        $nis_nip = bersihkan_input($_POST['nis_nip'] ?? '');
        $kelas = bersihkan_input($_POST['kelas'] ?? '');
        $jurusan = bersihkan_input($_POST['jurusan'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        if ($nama === '' || $email === '' || $nis_nip === '' || $kelas === '' || $jurusan === '') {
            set_flash_message('error', 'Semua field siswa wajib diisi.');
            redirect_superadmin('siswa-page');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Format email siswa tidak valid.');
            redirect_superadmin('siswa-page');
        }

        if ($id > 0) {
            $current_siswa = find_user_by_id($koneksi, $id);
            $is_password_changed = ($password !== '' && (!$current_siswa || (!password_verify($password, $current_siswa['password']) && $password !== $current_siswa['password'])));

            if ($is_password_changed) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, password = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ? AND jabatan = ?');
                $jabatan = 'Siswa';
                $stmt->bind_param('sssssssiss', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $jurusan, $is_active, $id, $jabatan);
            } else {
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ? AND jabatan = ?');
                $jabatan = 'Siswa';
                $stmt->bind_param('ssssssiss', $nama, $email, $jabatan, $nis_nip, $kelas, $jurusan, $is_active, $id, $jabatan);
            }

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data siswa berhasil diperbarui.');
            } else {
                set_flash_message('error', 'Gagal memperbarui data siswa. Pastikan email dan NISN tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        } else {
            if ($password === '') {
                set_flash_message('error', 'Password siswa wajib diisi saat menambah data baru.');
                redirect_superadmin('siswa-page');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $jabatan = 'Siswa';
            $stmt = $koneksi->prepare('INSERT INTO users (nama, email, password, jabatan, nis_nip, kelas, jurusan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssi', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $jurusan, $is_active);

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data siswa berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambahkan data siswa. Pastikan email dan NISN tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        redirect_superadmin('siswa-page');
    }

    if ($form_action === 'save_kelas') {
        $id = (int) ($_POST['kelas_id'] ?? 0);
        $nama_kelas = bersihkan_input($_POST['nama_kelas'] ?? '');
        $tingkat = bersihkan_input($_POST['tingkat'] ?? '');
        $jurusan = bersihkan_input($_POST['jurusan'] ?? '');
        $wali_kelas = bersihkan_input($_POST['wali_kelas'] ?? '');

        if ($nama_kelas === '' || $tingkat === '' || $jurusan === '' || $wali_kelas === '') {
            set_flash_message('error', 'Semua field kelas wajib diisi.');
            redirect_superadmin('kelas-page');
        }

        if ($id > 0) {
            $stmt = $koneksi->prepare('UPDATE kelas SET nama_kelas = ?, tingkat = ?, jurusan = ?, wali_kelas = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $nama_kelas, $tingkat, $jurusan, $wali_kelas, $id);

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data kelas berhasil diperbarui.');
            } else {
                set_flash_message('error', 'Gagal memperbarui data kelas. Kombinasi tingkat dan nama kelas harus unik.');
            }
            if ($stmt) {
                $stmt->close();
            }
        } else {
            $stmt = $koneksi->prepare('INSERT INTO kelas (nama_kelas, tingkat, jurusan, wali_kelas) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $nama_kelas, $tingkat, $jurusan, $wali_kelas);

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data kelas berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambahkan data kelas. Kombinasi tingkat dan nama kelas harus unik.');
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        redirect_superadmin('kelas-page');
    }

    if ($form_action === 'save_user') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $nama = bersihkan_input($_POST['nama'] ?? '');
        $email = bersihkan_input($_POST['email'] ?? '');
        $jabatan = bersihkan_input($_POST['jabatan'] ?? '');
        $nis_nip = bersihkan_input($_POST['nis_nip'] ?? '');
        $kelas = bersihkan_input($_POST['kelas'] ?? '');
        $jurusan = bersihkan_input($_POST['jurusan'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        if ($nama === '' || $email === '' || $jabatan === '' || $nis_nip === '') {
            set_flash_message('error', 'Field utama user wajib diisi.');
            redirect_superadmin('user-page');
        }

        if (!in_array($jabatan, ['Guru', 'Siswa', 'Super Admin'], true)) {
            set_flash_message('error', 'Role user tidak valid.');
            redirect_superadmin('user-page');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Format email user tidak valid.');
            redirect_superadmin('user-page');
        }

        if ($jabatan === 'Super Admin') {
            $kelas = '-';
            $jurusan = '-';
        }

        if ($id > 0) {
            $current_user = find_user_by_id($koneksi, $id);
            $is_password_changed = ($password !== '' && (!$current_user || (!password_verify($password, $current_user['password']) && $password !== $current_user['password'])));

            if ($is_password_changed) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, password = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ?');
                $stmt->bind_param('sssssssii', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $jurusan, $is_active, $id);
            } else {
                $stmt = $koneksi->prepare('UPDATE users SET nama = ?, email = ?, jabatan = ?, nis_nip = ?, kelas = ?, jurusan = ?, is_active = ? WHERE id = ?');
                $stmt->bind_param('ssssssii', $nama, $email, $jabatan, $nis_nip, $kelas, $jurusan, $is_active, $id);
            }

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data user berhasil diperbarui.');
            } else {
                set_flash_message('error', 'Gagal memperbarui data user. Pastikan email dan ID login tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        } else {
            if ($password === '') {
                set_flash_message('error', 'Password user wajib diisi saat menambah data baru.');
                redirect_superadmin('user-page');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare('INSERT INTO users (nama, email, password, jabatan, nis_nip, kelas, jurusan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssi', $nama, $email, $hashed_password, $jabatan, $nis_nip, $kelas, $jurusan, $is_active);

            if ($stmt && $stmt->execute()) {
                set_flash_message('success', 'Data user berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambahkan data user. Pastikan email dan ID login tidak duplikat.');
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        redirect_superadmin('user-page');
    }

    if ($form_action === 'import_excel') {
        require_once __DIR__ . '/../includes/simple_xlsx_importer.php';

        $mode = $_POST['import_mode'] ?? 'school_format';
        $target_sheet = trim($_POST['target_sheet'] ?? 'all');
        $source_type = $_POST['source_type'] ?? 'upload';

        $file_path = '';

        if ($source_type === 'server_file') {
            $server_file = dirname(__DIR__) . '/A. KELAS X 2026-2027.xlsx';
            if (file_exists($server_file)) {
                $file_path = $server_file;
            } else {
                set_flash_message('error', 'File di server tidak ditemukan.');
                redirect_superadmin('import-page');
            }
        } else {
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                set_flash_message('error', 'Silakan pilih file Excel (.xlsx) atau CSV yang valid untuk diunggah.');
                redirect_superadmin('import-page');
            }

            $orig_name = $_FILES['excel_file']['name'];
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'csv', 'xls'], true)) {
                set_flash_message('error', 'Format file tidak didukung. Harap gunakan file Excel (.xlsx) atau CSV (.csv).');
                redirect_superadmin('import-page');
            }

            $file_path = $_FILES['excel_file']['tmp_name'];
        }

        try {
            $is_csv = false;
            if ($source_type === 'upload') {
                $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
                $is_csv = ($ext === 'csv');
            } else {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                $is_csv = ($ext === 'csv');
            }

            if ($is_csv) {
                $sheets = SimpleXlsxImporter::parseCsv($file_path);
            } else {
                $sheets = SimpleXlsxImporter::parseXlsx($file_path);
            }

            if ($mode === 'standard_format') {
                $firstSheet = reset($sheets);
                $summary = SimpleXlsxImporter::importStandardTable($firstSheet, $koneksi);
            } else {
                $summary = SimpleXlsxImporter::importSchoolSheets($sheets, $koneksi, ['target_sheet' => $target_sheet]);
            }

            $_SESSION['last_import_summary'] = $summary;
            $msg = "Import Berhasil! {$summary['kelas_count']} Kelas, {$summary['guru_count']} Wali Kelas, dan {$summary['siswa_count']} Siswa berhasil diproses.";
            set_flash_message('success', $msg);
        } catch (Throwable $e) {
            set_flash_message('error', 'Gagal memproses file: ' . $e->getMessage());
        }

        redirect_superadmin('import-page');
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'download_template') {
        require_once __DIR__ . '/../includes/simple_xlsx_importer.php';
        SimpleXlsxImporter::downloadTemplateCsv();
        exit;
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = (int) $_GET['id'];

    if ($action === 'delete_guru' && $id > 0) {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ? AND jabatan = 'Guru'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data guru berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data guru.');
        }
        $stmt->close();
        redirect_superadmin('guru-page');
    }

    if ($action === 'delete_siswa' && $id > 0) {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ? AND jabatan = 'Siswa'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data siswa berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data siswa.');
        }
        $stmt->close();
        redirect_superadmin('siswa-page');
    }

    if ($action === 'delete_kelas' && $id > 0) {
        $stmt = $koneksi->prepare('DELETE FROM kelas WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data kelas berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data kelas.');
        }
        $stmt->close();
        redirect_superadmin('kelas-page');
    }

    if ($action === 'delete_user' && $id > 0) {
        if ($id === (int) $_SESSION['user_id']) {
            set_flash_message('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
            redirect_superadmin('user-page');
        }

        $stmt = $koneksi->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data user berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data user.');
        }
        $stmt->close();
        redirect_superadmin('user-page');
    }
}

$edit_guru = null;
$edit_siswa = null;
$edit_kelas = null;
$edit_user = null;

if (isset($_GET['edit'], $_GET['id'])) {
    $edit_id = (int) $_GET['id'];

    if ($_GET['edit'] === 'guru') {
        $edit_guru = find_user_by_id($koneksi, $edit_id);
        $active_page = 'guru-page';
    } elseif ($_GET['edit'] === 'siswa') {
        $edit_siswa = find_user_by_id($koneksi, $edit_id);
        $active_page = 'siswa-page';
    } elseif ($_GET['edit'] === 'kelas') {
        $edit_kelas = find_kelas_by_id($koneksi, $edit_id);
        $active_page = 'kelas-page';
    } elseif ($_GET['edit'] === 'user') {
        $edit_user = find_user_by_id($koneksi, $edit_id);
        $active_page = 'user-page';
    }
}

$flash = get_flash_message();
$import_summary = $_SESSION['last_import_summary'] ?? null;
unset($_SESSION['last_import_summary']);

$server_xlsx_file = dirname(__DIR__) . '/A. KELAS X 2026-2027.xlsx';
$server_file_exists = file_exists($server_xlsx_file);
$server_rosters = [];
if ($active_page === 'import-page' && $server_file_exists) {
    require_once __DIR__ . '/../includes/simple_xlsx_importer.php';
    try {
        $server_sheets = SimpleXlsxImporter::parseXlsx($server_xlsx_file);
        $server_rosters = SimpleXlsxImporter::inspectRosterSheets($server_sheets);
    } catch (Throwable $e) {
        $server_rosters = [];
    }
}

// Total counts for summary cards
$count_guru  = (int) ($koneksi->query("SELECT COUNT(*) AS c FROM users WHERE jabatan = 'Guru'")->fetch_assoc()['c'] ?? 0);
$count_siswa = (int) ($koneksi->query("SELECT COUNT(*) AS c FROM users WHERE jabatan = 'Siswa'")->fetch_assoc()['c'] ?? 0);
$count_kelas = (int) ($koneksi->query("SELECT COUNT(*) AS c FROM kelas")->fetch_assoc()['c'] ?? 0);
$count_user  = (int) ($koneksi->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0);

// Calculate total pages
$total_pages_guru  = max(1, (int) ceil($count_guru  / $per_page));
$total_pages_siswa = max(1, (int) ceil($count_siswa / $per_page));
$total_pages_kelas = max(1, (int) ceil($count_kelas / $per_page));
$total_pages_user  = max(1, (int) ceil($count_user  / $per_page));

// Clamp current page
$pg_guru  = min($pg_guru,  $total_pages_guru);
$pg_siswa = min($pg_siswa, $total_pages_siswa);
$pg_kelas = min($pg_kelas, $total_pages_kelas);
$pg_user  = min($pg_user,  $total_pages_user);

// Calculate offsets
$offset_guru  = ($pg_guru  - 1) * $per_page;
$offset_siswa = ($pg_siswa - 1) * $per_page;
$offset_kelas = ($pg_kelas - 1) * $per_page;
$offset_user  = ($pg_user  - 1) * $per_page;

// Paginated data queries
$guru_rows  = fetch_rows($koneksi, "SELECT id, nama, email, nis_nip, kelas, jurusan, is_active FROM users WHERE jabatan = 'Guru' ORDER BY nama ASC LIMIT $per_page OFFSET $offset_guru");
$siswa_rows = fetch_rows($koneksi, "SELECT id, nama, email, nis_nip, kelas, jurusan, is_active FROM users WHERE jabatan = 'Siswa' ORDER BY nama ASC LIMIT $per_page OFFSET $offset_siswa");
$kelas_rows_all = fetch_rows($koneksi, "SELECT id, nama_kelas, tingkat, jurusan, wali_kelas FROM kelas ORDER BY tingkat ASC, nama_kelas ASC");
$kelas_rows = array_slice($kelas_rows_all, $offset_kelas, $per_page);
$user_rows  = fetch_rows($koneksi, "SELECT id, nama, email, jabatan, nis_nip, kelas, jurusan, is_active FROM users ORDER BY created_at DESC LIMIT $per_page OFFSET $offset_user");

// Helper: build pagination URL preserving existing params
function pagination_url(string $page, string $param, int $pg_num): string {
    $params = $_GET;
    $params['page'] = $page;
    $params[$param] = $pg_num;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="../logo/1. logo smk negeri 3 malang.png">
    <title>Dashboard Super Admin Reading Log SMKN 3 Malang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f1f5f9;
            color: #1e293b;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #0f172a;
            color: #fff;
            padding: 24px 18px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }

        .logo {
            padding: 14px 16px;
            border-radius: 14px;
            background: #1e293b;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        /* Sidebar logo box — shared via include */
        .sidebar-logo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 10px 16px;
            margin-bottom: 16px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.25);
        }

        .sidebar-logo-box img {
            height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .sidebar-logo-divider {
            width: 1px;
            height: 38px;
            background: rgba(0,0,0,0.15);
            border-radius: 2px;
        }

        .sidebar-text {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 20px;
            text-align: center;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            background: #1e293b;
            color: #e2e8f0;
            text-align: left;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .nav-button:hover,
        .nav-button.active {
            background: #2563eb;
            color: #fff;
            transform: translateX(4px);
        }

        .main {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 28px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            background: #fff;
            border-radius: 18px;
            padding: 22px 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        .header-badge {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
            white-space: nowrap;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .logout-link {
            display: inline-block;
            text-decoration: none;
            background: #dc2626;
            color: #fff;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
        }

        .logout-link:hover {
            background: #b91c1c;
        }

        .flash {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 600;
        }

        .flash.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            border-left: 5px solid #2563eb;
        }

        .summary-card span {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .summary-card strong {
            font-size: 28px;
            color: #0f172a;
        }

        .page {
            display: none;
        }

        .page.active {
            display: block;
        }

        .section-card {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .section-head h2 {
            color: #0f172a;
            font-size: 24px;
            margin-bottom: 4px;
        }

        .section-head p {
            color: #64748b;
            font-size: 14px;
        }

        .status-note {
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(320px, 380px) minmax(0, 1fr);
            gap: 22px;
        }

        .form-card,
        .table-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
        }

        .form-card h3,
        .table-card h3 {
            margin-bottom: 16px;
            color: #0f172a;
        }

        .form-note {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Password wrapper styling */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            padding-right: 40px;
            width: 100%;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #64748b;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #2563eb;
        }

        .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
            border-radius: 4px;
        }

        .help-text {
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
        }

        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-edit {
            background: #f59e0b;
            color: #fff;
        }

        .btn-delete {
            background: #dc2626;
            color: #fff;
        }

        /* Import page styling */
        .upload-dropzone {
            border: 2px dashed #93c5fd;
            border-radius: 14px;
            padding: 26px 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .upload-dropzone:hover,
        .upload-dropzone.dragover {
            border-color: #2563eb;
            background: #eff6ff;
            transform: translateY(-1px);
        }

        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            color: #2563eb;
            margin-bottom: 8px;
            display: flex;
            justify-content: center;
        }

        .server-file-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .result-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .result-card {
            background: #fff;
            border-radius: 10px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .result-number {
            font-size: 26px;
            font-weight: 700;
            color: #2563eb;
        }

        .result-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }

        th {
            background: #1d4ed8;
            color: #fff;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-muted {
            background: #e2e8f0;
            color: #475569;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination a {
            background: #e2e8f0;
            color: #334155;
        }

        .pagination a:hover {
            background: #2563eb;
            color: #fff;
            transform: translateY(-1px);
        }

        .pagination .active-page {
            background: #2563eb;
            color: #fff;
            pointer-events: none;
        }

        .pagination .disabled {
            background: #f1f5f9;
            color: #94a3b8;
            pointer-events: none;
        }

        .pagination .page-info {
            font-size: 13px;
            color: #64748b;
            padding: 0 8px;
        }

        .empty-state {
            padding: 18px;
            text-align: center;
            color: #64748b;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }

        @media (max-width: 1100px) {
            .summary-grid,
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 820px) {
            .layout {
                display: block;
            }

            .sidebar {
                position: static;
                width: 100%;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 18px;
            }

            .header,
            .section-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <?php include __DIR__ . '/../includes/sidebar_logo.php'; ?>
            <div class="logo">SUPER ADMIN</div>
            <p class="sidebar-text">Kelola seluruh data utama sistem Aplikasi Reading Log Untuk Meningkatkan Literasi Rajin Membaca Siswa SMKN 3 Malang</p>
            <div class="nav-menu">
                <a class="nav-button <?php echo $active_page === 'guru-page' ? 'active' : ''; ?>" href="?page=guru-page">Menu Guru</a>
                <a class="nav-button <?php echo $active_page === 'siswa-page' ? 'active' : ''; ?>" href="?page=siswa-page">Menu Siswa</a>
                <a class="nav-button <?php echo $active_page === 'kelas-page' ? 'active' : ''; ?>" href="?page=kelas-page">Menu Kelas</a>
                <a class="nav-button <?php echo $active_page === 'user-page' ? 'active' : ''; ?>" href="?page=user-page">Management User</a>
                <a class="nav-button <?php echo $active_page === 'import-page' ? 'active' : ''; ?>" href="?page=import-page">Import Data Excel</a>
            </div>
        </aside>

        <main class="main">
            <div class="header">
                <div>
                    <h1><?php echo esc($page_meta[$active_page]['title']); ?></h1>
                    <p><?php echo esc($page_meta[$active_page]['desc']); ?></p>
                </div>
                <div class="header-actions">
                    <div class="header-badge">Super Admin Panel</div>
                    <a class="logout-link" href="../logout.php">Log Out</a>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="flash <?php echo esc($flash['type']); ?>"><?php echo esc($flash['message']); ?></div>
            <?php endif; ?>

            <div class="summary-grid">
                <div class="summary-card">
                    <span>Total Guru</span>
                    <strong><?php echo $count_guru; ?></strong>
                </div>
                <div class="summary-card">
                    <span>Total Siswa</span>
                    <strong><?php echo $count_siswa; ?></strong>
                </div>
                <div class="summary-card">
                    <span>Total Kelas</span>
                    <strong><?php echo $count_kelas; ?></strong>
                </div>
                <div class="summary-card">
                    <span>Total User</span>
                    <strong><?php echo $count_user; ?></strong>
                </div>
            </div>

            <section class="page <?php echo $active_page === 'guru-page' ? 'active' : ''; ?>">
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Menu Guru</h2>
                        </div>
                        <div class="status-note"></div>
                    </div>
                    <div class="content-grid">
                        <div class="form-card">
                            <h3><?php echo $edit_guru ? 'Edit Guru' : 'Tambah Guru'; ?></h3>
                            <?php if ($edit_guru): ?>
                                <div class="form-note">Mode edit aktif untuk `<?php echo esc($edit_guru['nama']); ?>`.</div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="form_action" value="save_guru">
                                <input type="hidden" name="guru_id" value="<?php echo $edit_guru ? (int) $edit_guru['id'] : 0; ?>">
                                <div class="form-group">
                                    <label for="guru-nama">Nama Guru</label>
                                    <input type="text" id="guru-nama" name="nama" value="<?php echo esc($edit_guru['nama'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guru-email">Email</label>
                                    <input type="email" id="guru-email" name="email" value="<?php echo esc($edit_guru['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guru-nis_nip">NIP / ID Guru</label>
                                    <input type="text" id="guru-nis_nip" name="nis_nip" value="<?php echo esc($edit_guru['nis_nip'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guru-kelas">Kelas Ajar</label>
                                    <input type="text" id="guru-kelas" name="kelas" value="<?php echo esc($edit_guru['kelas'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guru-jurusan">Mata Pelajaran / Bidang</label>
                                    <input type="text" id="guru-jurusan" name="jurusan" value="<?php echo esc($edit_guru['jurusan'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guru-password">Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="guru-password" name="password" value="<?php echo esc(get_display_password($edit_guru['password'] ?? '', 'Guru')); ?>" <?php echo $edit_guru ? '' : 'required'; ?>>
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('guru-password')">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="help-text">Password terisi dari database. Ubah jika ingin mengganti.</div>
                                </div>
                                <div class="form-group">
                                    <label for="guru-status">Status Akun</label>
                                    <select id="guru-status" name="is_active" required>
                                        <option value="1" <?php echo (($edit_guru['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="0" <?php echo (($edit_guru['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="action-row">
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_guru ? 'Update Guru' : 'Simpan Guru'; ?></button>
                                    <a class="btn btn-secondary" href="?page=guru-page">Bersihkan</a>
                                </div>
                            </form>
                        </div>
                        <div class="table-card">
                            <h3>Daftar Guru</h3>
                            <?php if (!$guru_rows): ?>
                                <div class="empty-state">Belum ada data guru di database.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th>NIP</th>
                                                <th>Kelas Ajar</th>
                                                <th>Bidang</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($guru_rows as $index => $guru): ?>
                                                <tr>
                                                    <td><?php echo $offset_guru + $index + 1; ?></td>
                                                    <td><?php echo esc($guru['nama']); ?></td>
                                                    <td><?php echo esc($guru['email']); ?></td>
                                                    <td><?php echo esc($guru['nis_nip']); ?></td>
                                                    <td><?php echo esc($guru['kelas']); ?></td>
                                                    <td><?php echo esc($guru['jurusan']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ((int) $guru['is_active'] === 1) ? 'badge-success' : 'badge-muted'; ?>">
                                                            <?php echo ((int) $guru['is_active'] === 1) ? 'Aktif' : 'Nonaktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-edit" href="?page=guru-page&edit=guru&id=<?php echo (int) $guru['id']; ?>">Edit</a>
                                                        <a class="btn btn-delete" href="?page=guru-page&action=delete_guru&id=<?php echo (int) $guru['id']; ?>" onclick="return confirm('Hapus data guru ini?');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages_guru > 1): ?>
                                <nav class="pagination">
                                    <a class="<?php echo $pg_guru <= 1 ? 'disabled' : ''; ?>" href="<?php echo pagination_url('guru-page', 'pg_guru', $pg_guru - 1); ?>">&laquo; Prev</a>
                                    <?php for ($i = 1; $i <= $total_pages_guru; $i++): ?>
                                        <?php if ($i === $pg_guru): ?>
                                            <span class="active-page"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo pagination_url('guru-page', 'pg_guru', $i); ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <a class="<?php echo $pg_guru >= $total_pages_guru ? 'disabled' : ''; ?>" href="<?php echo pagination_url('guru-page', 'pg_guru', $pg_guru + 1); ?>">Next &raquo;</a>
                                    <span class="page-info">Halaman <?php echo $pg_guru; ?> dari <?php echo $total_pages_guru; ?> (<?php echo $count_guru; ?> data)</span>
                                </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="page <?php echo $active_page === 'siswa-page' ? 'active' : ''; ?>">
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Menu Siswa</h2>
                        </div>
                        <div class="status-note">
                            <a class="btn btn-primary" href="?page=import-page" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:14px; padding:8px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Import Excel
                            </a>
                        </div>
                    </div>
                    <div class="content-grid">
                        <div class="form-card">
                            <h3><?php echo $edit_siswa ? 'Edit Siswa' : 'Tambah Siswa'; ?></h3>
                            <?php if ($edit_siswa): ?>
                                <div class="form-note">Mode edit aktif untuk `<?php echo esc($edit_siswa['nama']); ?>`.</div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="form_action" value="save_siswa">
                                <input type="hidden" name="siswa_id" value="<?php echo $edit_siswa ? (int) $edit_siswa['id'] : 0; ?>">
                                <div class="form-group">
                                    <label for="siswa-nama">Nama Siswa</label>
                                    <input type="text" id="siswa-nama" name="nama" value="<?php echo esc($edit_siswa['nama'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="siswa-email">Email</label>
                                    <input type="email" id="siswa-email" name="email" value="<?php echo esc($edit_siswa['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="siswa-nis_nip">NISN</label>
                                    <input type="text" id="siswa-nis_nip" name="nis_nip" value="<?php echo esc($edit_siswa['nis_nip'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="siswa-kelas">Kelas</label>
                                    <?php
                                    $current_siswa_kelas = trim((string)($edit_siswa['kelas'] ?? ''));
                                    $kelas_in_list = false;
                                    foreach ($kelas_rows_all as $k) {
                                        if ($current_siswa_kelas !== '' && strcasecmp($current_siswa_kelas, $k['nama_kelas']) === 0) {
                                            $kelas_in_list = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <select id="siswa-kelas" name="kelas" required onchange="updateJurusanWaliKelas()">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php if ($current_siswa_kelas !== '' && !$kelas_in_list): ?>
                                            <option value="<?php echo esc($current_siswa_kelas); ?>" 
                                                data-jurusan="<?php echo esc($edit_siswa['jurusan'] ?? ''); ?>"
                                                selected>
                                                <?php echo esc($current_siswa_kelas); ?>
                                            </option>
                                        <?php endif; ?>
                                        <?php foreach ($kelas_rows_all as $kelas): ?>
                                            <option value="<?php echo esc($kelas['nama_kelas']); ?>" 
                                                data-jurusan="<?php echo esc($kelas['jurusan']); ?>"
                                                data-wali-kelas="<?php echo esc($kelas['wali_kelas']); ?>"
                                                <?php echo ($kelas_in_list && strcasecmp($current_siswa_kelas, $kelas['nama_kelas']) === 0) ? 'selected' : ''; ?>>
                                                <?php echo esc($kelas['nama_kelas']); ?> (<?php echo esc($kelas['tingkat']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="siswa-jurusan">Jurusan</label>
                                    <input type="text" id="siswa-jurusan" name="jurusan" value="<?php echo esc($edit_siswa['jurusan'] ?? ''); ?>" required readonly style="background: #f0f0f0;">
                                </div>
                                <input type="hidden" id="wali-kelas" name="wali_kelas" value="">
                                <div class="form-group">
                                    <label for="siswa-password">Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="siswa-password" name="password" value="<?php echo esc(get_display_password($edit_siswa['password'] ?? '', 'Siswa')); ?>" <?php echo $edit_siswa ? '' : 'required'; ?>>
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('siswa-password')">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="help-text">Password terisi dari database. Ubah jika ingin mengganti.</div>
                                </div>
                                <div class="form-group">
                                    <label for="siswa-status">Status Akun</label>
                                    <select id="siswa-status" name="is_active" required>
                                        <option value="1" <?php echo (($edit_siswa['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="0" <?php echo (($edit_siswa['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="action-row">
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_siswa ? 'Update Siswa' : 'Simpan Siswa'; ?></button>
                                    <a class="btn btn-secondary" href="?page=siswa-page">Bersihkan</a>
                                </div>
                            </form>
                        </div>
                        <div class="table-card">
                            <h3>Daftar Siswa</h3>
                            <?php if (!$siswa_rows): ?>
                                <div class="empty-state">Belum ada data siswa di database.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th>NISN</th>
                                                <th>Kelas</th>
                                                <th>Jurusan</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($siswa_rows as $index => $siswa): ?>
                                                <tr>
                                                    <td><?php echo $offset_siswa + $index + 1; ?></td>
                                                    <td><?php echo esc($siswa['nama']); ?></td>
                                                    <td><?php echo esc($siswa['email']); ?></td>
                                                    <td><?php echo esc($siswa['nis_nip']); ?></td>
                                                    <td><?php echo esc($siswa['kelas']); ?></td>
                                                    <td><?php echo esc($siswa['jurusan']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ((int) $siswa['is_active'] === 1) ? 'badge-success' : 'badge-muted'; ?>">
                                                            <?php echo ((int) $siswa['is_active'] === 1) ? 'Aktif' : 'Nonaktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-edit" href="?page=siswa-page&edit=siswa&id=<?php echo (int) $siswa['id']; ?>">Edit</a>
                                                        <a class="btn btn-delete" href="?page=siswa-page&action=delete_siswa&id=<?php echo (int) $siswa['id']; ?>" onclick="return confirm('Hapus data siswa ini?');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages_siswa > 1): ?>
                                <nav class="pagination">
                                    <a class="<?php echo $pg_siswa <= 1 ? 'disabled' : ''; ?>" href="<?php echo pagination_url('siswa-page', 'pg_siswa', $pg_siswa - 1); ?>">&laquo; Prev</a>
                                    <?php for ($i = 1; $i <= $total_pages_siswa; $i++): ?>
                                        <?php if ($i === $pg_siswa): ?>
                                            <span class="active-page"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo pagination_url('siswa-page', 'pg_siswa', $i); ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <a class="<?php echo $pg_siswa >= $total_pages_siswa ? 'disabled' : ''; ?>" href="<?php echo pagination_url('siswa-page', 'pg_siswa', $pg_siswa + 1); ?>">Next &raquo;</a>
                                    <span class="page-info">Halaman <?php echo $pg_siswa; ?> dari <?php echo $total_pages_siswa; ?> (<?php echo $count_siswa; ?> data)</span>
                                </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="page <?php echo $active_page === 'kelas-page' ? 'active' : ''; ?>">
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Menu Kelas</h2>
                        </div>
                        <div class="status-note">
                            <a class="btn btn-primary" href="?page=import-page" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:14px; padding:8px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Import Excel
                            </a>
                        </div>
                    </div>
                    <div class="content-grid">
                        <div class="form-card">
                            <h3><?php echo $edit_kelas ? 'Edit Kelas' : 'Tambah Kelas'; ?></h3>
                            <?php if ($edit_kelas): ?>
                                <div class="form-note">Mode edit aktif untuk kelas `<?php echo esc($edit_kelas['tingkat'] . ' ' . $edit_kelas['nama_kelas']); ?>`.</div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="form_action" value="save_kelas">
                                <input type="hidden" name="kelas_id" value="<?php echo $edit_kelas ? (int) $edit_kelas['id'] : 0; ?>">
                                <div class="form-group">
                                    <label for="kelas-tingkat">Tingkat</label>
                                    <select id="kelas-tingkat" name="tingkat" required onchange="generateNamaKelas()">
                                        <option value="">Pilih Tingkat</option>
                                        <option value="X" <?php echo (($edit_kelas['tingkat'] ?? '') === 'X') ? 'selected' : ''; ?>>X</option>
                                        <option value="XI" <?php echo (($edit_kelas['tingkat'] ?? '') === 'XI') ? 'selected' : ''; ?>>XI</option>
                                        <option value="XII" <?php echo (($edit_kelas['tingkat'] ?? '') === 'XII') ? 'selected' : ''; ?>>XII</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="kelas-jurusan">Jurusan</label>
                                    <input type="text" id="kelas-jurusan" name="jurusan" value="<?php echo esc($edit_kelas['jurusan'] ?? ''); ?>" required placeholder="Contoh: Teknik Komputer Jaringan">
                                </div>
                                <div class="form-group">
                                    <label for="kelas-nama">Nama Kelas <small style="color:#64748b;font-weight:400;">(otomatis)</small></label>
                                    <input type="text" id="kelas-nama" name="nama_kelas" value="<?php echo esc($edit_kelas['nama_kelas'] ?? ''); ?>" required readonly style="background: #f0f0f0; cursor: not-allowed; font-weight: 700; font-size: 16px; color: #2563eb;">
                                    <div class="help-text">Nama kelas dibuat otomatis berdasarkan Tingkat. Huruf (A, B, C, ...) bertambah sesuai jumlah kelas yang sudah ada di tingkat tersebut.</div>
                                </div>
                                <div class="form-group">
                                    <label for="kelas-wali">Wali Kelas</label>
                                    <input type="text" id="kelas-wali" name="wali_kelas" value="<?php echo esc($edit_kelas['wali_kelas'] ?? ''); ?>" required placeholder="Nama wali kelas">
                                </div>
                                <div class="action-row">
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_kelas ? 'Update Kelas' : 'Simpan Kelas'; ?></button>
                                    <a class="btn btn-secondary" href="?page=kelas-page">Bersihkan</a>
                                </div>
                            </form>
                        </div>
                        <div class="table-card">
                            <h3>Daftar Kelas</h3>
                            <?php if (!$kelas_rows): ?>
                                <div class="empty-state">Belum ada data kelas di database.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tingkat</th>
                                                <th>Nama Kelas</th>
                                                <th>Jurusan</th>
                                                <th>Wali Kelas</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kelas_rows as $index => $kelas): ?>
                                                <tr>
                                                    <td><?php echo $offset_kelas + $index + 1; ?></td>
                                                    <td><?php echo esc($kelas['tingkat']); ?></td>
                                                    <td><?php echo esc($kelas['nama_kelas']); ?></td>
                                                    <td><?php echo esc($kelas['jurusan']); ?></td>
                                                    <td><?php echo esc($kelas['wali_kelas']); ?></td>
                                                    <td>
                                                        <a class="btn btn-edit" href="?page=kelas-page&edit=kelas&id=<?php echo (int) $kelas['id']; ?>">Edit</a>
                                                        <a class="btn btn-delete" href="?page=kelas-page&action=delete_kelas&id=<?php echo (int) $kelas['id']; ?>" onclick="return confirm('Hapus data kelas ini?');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages_kelas > 1): ?>
                                <nav class="pagination">
                                    <a class="<?php echo $pg_kelas <= 1 ? 'disabled' : ''; ?>" href="<?php echo pagination_url('kelas-page', 'pg_kelas', $pg_kelas - 1); ?>">&laquo; Prev</a>
                                    <?php for ($i = 1; $i <= $total_pages_kelas; $i++): ?>
                                        <?php if ($i === $pg_kelas): ?>
                                            <span class="active-page"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo pagination_url('kelas-page', 'pg_kelas', $i); ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <a class="<?php echo $pg_kelas >= $total_pages_kelas ? 'disabled' : ''; ?>" href="<?php echo pagination_url('kelas-page', 'pg_kelas', $pg_kelas + 1); ?>">Next &raquo;</a>
                                    <span class="page-info">Halaman <?php echo $pg_kelas; ?> dari <?php echo $total_pages_kelas; ?> (<?php echo $count_kelas; ?> data)</span>
                                </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="page <?php echo $active_page === 'user-page' ? 'active' : ''; ?>">
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Management User</h2>
                        </div>
                        <div class="status-note"></div>
                    </div>
                    <div class="content-grid">
                        <div class="form-card">
                            <h3><?php echo $edit_user ? 'Edit User' : 'Tambah User'; ?></h3>
                            <?php if ($edit_user): ?>
                                <div class="form-note">Mode edit aktif untuk `<?php echo esc($edit_user['nama']); ?>`.</div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="form_action" value="save_user">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user ? (int) $edit_user['id'] : 0; ?>">
                                <div class="form-group">
                                    <label for="user-nama">Nama User</label>
                                    <input type="text" id="user-nama" name="nama" value="<?php echo esc($edit_user['nama'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="user-email">Email</label>
                                    <input type="email" id="user-email" name="email" value="<?php echo esc($edit_user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="user-role">Role</label>
                                    <select id="user-role" name="jabatan" required>
                                        <option value="">Pilih Role</option>
                                        <option value="Guru" <?php echo (($edit_user['jabatan'] ?? '') === 'Guru') ? 'selected' : ''; ?>>Guru</option>
                                        <option value="Siswa" <?php echo (($edit_user['jabatan'] ?? '') === 'Siswa') ? 'selected' : ''; ?>>Siswa</option>
                                        <option value="Super Admin" <?php echo (($edit_user['jabatan'] ?? '') === 'Super Admin') ? 'selected' : ''; ?>>Super Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="user-login">NISN / NIP / ID Login</label>
                                    <input type="text" id="user-login" name="nis_nip" value="<?php echo esc($edit_user['nis_nip'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="user-kelas">Kelas</label>
                                    <input type="text" id="user-kelas" name="kelas" value="<?php echo esc($edit_user['kelas'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="user-jurusan">Jurusan / Bidang</label>
                                    <input type="text" id="user-jurusan" name="jurusan" value="<?php echo esc($edit_user['jurusan'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="user-password">Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="user-password" name="password" value="<?php echo esc(get_display_password($edit_user['password'] ?? '', $edit_user['jabatan'] ?? '')); ?>" <?php echo $edit_user ? '' : 'required'; ?>>
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('user-password')">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="help-text">Password terisi dari database. Ubah jika ingin mengganti.</div>
                                </div>
                                <div class="form-group">
                                    <label for="user-status">Status Akun</label>
                                    <select id="user-status" name="is_active" required>
                                        <option value="1" <?php echo (($edit_user['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="0" <?php echo (($edit_user['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="action-row">
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'Update User' : 'Simpan User'; ?></button>
                                    <a class="btn btn-secondary" href="?page=user-page">Bersihkan</a>
                                </div>
                            </form>
                        </div>
                        <div class="table-card">
                            <h3>Daftar User</h3>
                            <?php if (!$user_rows): ?>
                                <div class="empty-state">Belum ada user di database.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Role</th>
                                                <th>Email</th>
                                                <th>ID Login</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Password</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($user_rows as $index => $user): ?>
                                                <tr>
                                                    <td><?php echo $offset_user + $index + 1; ?></td>
                                                    <td><?php echo esc($user['nama']); ?></td>
                                                    <td><?php echo esc($user['jabatan']); ?></td>
                                                    <td><?php echo esc($user['email']); ?></td>
                                                    <td><?php echo esc($user['nis_nip']); ?></td>
                                                    <td><?php echo esc($user['kelas']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ((int) $user['is_active'] === 1) ? 'badge-success' : 'badge-muted'; ?>">
                                                            <?php echo ((int) $user['is_active'] === 1) ? 'Aktif' : 'Nonaktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>Tersimpan Aman</td>
                                                    <td>
                                                        <a class="btn btn-edit" href="?page=user-page&edit=user&id=<?php echo (int) $user['id']; ?>">Edit</a>
                                                        <a class="btn btn-delete" href="?page=user-page&action=delete_user&id=<?php echo (int) $user['id']; ?>" onclick="return confirm('Hapus data user ini?');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages_user > 1): ?>
                                <nav class="pagination">
                                    <a class="<?php echo $pg_user <= 1 ? 'disabled' : ''; ?>" href="<?php echo pagination_url('user-page', 'pg_user', $pg_user - 1); ?>">&laquo; Prev</a>
                                    <?php for ($i = 1; $i <= $total_pages_user; $i++): ?>
                                        <?php if ($i === $pg_user): ?>
                                            <span class="active-page"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo pagination_url('user-page', 'pg_user', $i); ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <a class="<?php echo $pg_user >= $total_pages_user ? 'disabled' : ''; ?>" href="<?php echo pagination_url('user-page', 'pg_user', $pg_user + 1); ?>">Next &raquo;</a>
                                    <span class="page-info">Halaman <?php echo $pg_user; ?> dari <?php echo $total_pages_user; ?> (<?php echo $count_user; ?> data)</span>
                                </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- IMPORT DATA EXCEL SECTION -->
            <section class="page <?php echo $active_page === 'import-page' ? 'active' : ''; ?>">
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Import Data Excel / CSV</h2>
                        </div>
                        <div class="status-note">
                            <a class="btn btn-secondary" href="?action=download_template" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none; font-size:14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Unduh Template CSV
                            </a>
                        </div>
                    </div>

                    <?php if ($import_summary): ?>
                        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:14px; padding:20px; margin-bottom:24px;">
                            <h3 style="color:#166534; font-size:17px; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                <span>🎉</span> Hasil Import Data Terakhir
                            </h3>
                            <div class="result-summary-grid">
                                <div class="result-card">
                                    <div class="result-number" style="color:#16a34a;"><?php echo (int) $import_summary['kelas_count']; ?></div>
                                    <div class="result-label">Kelas Diperbarui</div>
                                </div>
                                <div class="result-card">
                                    <div class="result-number" style="color:#2563eb;"><?php echo (int) $import_summary['guru_count']; ?></div>
                                    <div class="result-label">Wali Kelas (Guru)</div>
                                </div>
                                <div class="result-card">
                                    <div class="result-number" style="color:#9333ea;"><?php echo (int) $import_summary['siswa_count']; ?></div>
                                    <div class="result-label">Siswa Terdaftar</div>
                                </div>
                                <?php if (($import_summary['skipped'] ?? 0) > 0): ?>
                                <div class="result-card">
                                    <div class="result-number" style="color:#ea580c;"><?php echo (int) $import_summary['skipped']; ?></div>
                                    <div class="result-label">Dilewati</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($import_summary['details'])): ?>
                                <div style="margin-top:14px; padding:12px; background:#fff; border-radius:10px; border:1px solid #bbf7d0; max-height:200px; overflow-y:auto; font-size:13px; line-height:1.7;">
                                    <?php foreach ($import_summary['details'] as $detail): ?>
                                        <div>✅ <?php echo $detail; ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="content-grid">
                        <div class="form-card">
                            <h3>Form Import File</h3>
                            <form method="POST" enctype="multipart/form-data" id="form-import-excel">
                                <input type="hidden" name="form_action" value="import_excel">

                                <?php if ($server_file_exists): ?>
                                    <div class="server-file-box">
                                        <div style="font-weight:700; color:#166534; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                                            <span>📂</span> File Master Terdeteksi di Server
                                        </div>
                                        <div style="font-size:13px; color:#374151; line-height:1.5; margin-bottom:12px;">
                                            Ditemukan file <strong>A. KELAS X 2026-2027.xlsx</strong> (<?php echo count($server_rosters); ?> kelas terdeteksi). Anda dapat langsung mengimpor dari file server ini tanpa perlu upload ulang.
                                        </div>
                                        <div style="display:flex; flex-direction:column; gap:8px; font-size:14px;">
                                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                                <input type="radio" name="source_type" value="server_file" checked onchange="toggleSourceType()">
                                                <strong>Gunakan File di Server (A. KELAS X 2026-2027.xlsx)</strong>
                                            </label>
                                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                                <input type="radio" name="source_type" value="upload" onchange="toggleSourceType()">
                                                Upload File Baru dari Komputer (.xlsx / .csv)
                                            </label>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="source_type" value="upload">
                                <?php endif; ?>

                                <div class="form-group" id="upload-file-wrapper" style="<?php echo $server_file_exists ? 'display:none;' : ''; ?>">
                                    <label>Pilih File Excel (.xlsx) atau CSV</label>
                                    <div class="upload-dropzone" id="dropzone" onclick="document.getElementById('excel_file').click()">
                                        <div class="upload-icon">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                                        </div>
                                        <div style="font-weight:600; font-size:15px; margin-bottom:4px;" id="file-label-text">
                                            Klik untuk memilih file atau seret file ke sini
                                        </div>
                                        <div style="font-size:12px; color:#64748b;">Mendukung file .xlsx (Excel) atau .csv (Maksimal 10MB)</div>
                                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx, .csv, .xls" onchange="handleFileSelect(this)">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="import_mode">Format Struktur File</label>
                                    <select id="import_mode" name="import_mode" required onchange="toggleModeHelp()">
                                        <option value="school_format" selected>Format Presensi / Buku Roster SMKN 3 (Otomatis Deteksi Kelas, Wali & Siswa)</option>
                                        <option value="standard_format">Format Tabel Standar (Kolom: Nama, NISN, Kelas, Jurusan, dll.)</option>
                                    </select>
                                    <div class="help-text" id="mode-help">
                                        Membaca file roster sekolah seperti <code>A. KELAS X 2026-2027.xlsx</code>. Sistem otomatis mengekstrak nama kelas, jurusan, wali kelas, dan siswa.
                                    </div>
                                </div>

                                <div class="form-group" id="sheet-target-wrapper">
                                    <label for="target_sheet">Pilihan Sheet yang Diimpor</label>
                                    <select id="target_sheet" name="target_sheet">
                                        <option value="all">⚡ Import Semua Sheet Kelas (Semua Roster Terdeteksi)</option>
                                        <?php if (!empty($server_rosters)): ?>
                                            <optgroup label="Sheet Terdeteksi di File Server:">
                                                <?php foreach ($server_rosters as $sName => $rMeta): ?>
                                                    <option value="<?php echo esc($sName); ?>" <?php echo $sName === 'X PH 1' ? 'selected' : ''; ?>>
                                                        <?php echo esc($sName); ?> - <?php echo esc($rMeta['class_name']); ?> (<?php echo $rMeta['student_count']; ?> siswa)
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                    <div class="help-text">Pilih "Semua Sheet" untuk mengimpor seluruh kelas, atau pilih sheet spesifik seperti <strong>X PH 1 (X PERHOTELAN 1)</strong>.</div>
                                </div>

                                <div class="action-row" style="margin-top:20px;">
                                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        Mulai Import Data
                                    </button>
                                    <a class="btn btn-secondary" href="?action=download_template" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                        Unduh Template CSV
                                    </a>
                                </div>
                            </form>
                        </div>

                        <div class="table-card">
                            <h3>Panduan Format File Excel</h3>
                            <div style="font-size:14px; line-height:1.7; color:#334155;">
                                <p style="margin-bottom:12px;">Fitur import ini mendukung 2 jenis struktur file:</p>

                                <div style="background:#f8fafc; border-left:4px solid #2563eb; padding:12px 14px; border-radius:6px; margin-bottom:14px;">
                                    <strong style="color:#1e40af;">1. Format Buku Nilai / Presensi SMKN 3 (Roster Resmi)</strong>
                                    <ul style="margin:8px 0 0 18px; font-size:13px; color:#475569;">
                                        <li>File Excel dengan satu atau banyak sheet (seperti <code>A. KELAS X 2026-2027.xlsx</code>).</li>
                                        <li>Memiliki nama kelas di atas (cth: <code>X PERHOTELAN 1</code>).</li>
                                        <li>Memiliki baris <code>WALI KELAS : [Nama Guru]</code>.</li>
                                        <li>Tabel siswa dengan kolom <code>NO</code>, <code>NIPD</code>, <code>NAMA</code>, <code>L/P</code>.</li>
                                        <li><strong>Hasil:</strong> Kelas, Akun Guru/Wali Kelas, dan Akun seluruh Siswa otomatis dibuat & disinkronkan.</li>
                                    </ul>
                                </div>

                                <div style="background:#f8fafc; border-left:4px solid #10b981; padding:12px 14px; border-radius:6px; margin-bottom:14px;">
                                    <strong style="color:#065f46;">2. Format Tabel Kolom Standar (Excel / CSV)</strong>
                                    <p style="font-size:13px; color:#475569; margin-top:6px;">Baris pertama harus berupa header kolom:</p>
                                    <code style="display:block; background:#fff; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px; font-size:12px; margin-top:6px; color:#0f172a;">
                                        Nama, NISN_NIP, Kelas, Jurusan, Jabatan, Email, Password, Wali_Kelas
                                    </code>
                                </div>

                                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px; font-size:13px; color:#92400e;">
                                    <strong>💡 Ketentuan Akun Otomatis:</strong>
                                    <ul style="margin:4px 0 0 16px;">
                                        <li>Password Siswa otomatis disetel: <code>siswa123</code>.</li>
                                        <li>Password Guru / Wali Kelas otomatis disetel: <code>guru123</code>.</li>
                                        <li>Email siswa otomatis digenerate dari nama lengkap jika tidak diisi.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Fungsi untuk mengisi jurusan dan wali kelas otomatis
        function updateJurusanWaliKelas() {
            const kelasSelect = document.getElementById('siswa-kelas');
            const jurusanInput = document.getElementById('siswa-jurusan');
            const waliKelasInput = document.getElementById('wali-kelas');
            
            const selectedOption = kelasSelect.options[kelasSelect.selectedIndex];
            
            if (selectedOption.value !== '') {
                jurusanInput.value = selectedOption.getAttribute('data-jurusan') || '';
                waliKelasInput.value = selectedOption.getAttribute('data-wali-kelas') || '';
            } else {
                jurusanInput.value = '';
                waliKelasInput.value = '';
            }
        }

        // Fungsi untuk toggle visibility password
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleButton = passwordInput.nextElementSibling;
            const eyeIcon = toggleButton.querySelector('.eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
                toggleButton.setAttribute('aria-label', 'Sembunyikan password');
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
                toggleButton.setAttribute('aria-label', 'Tampilkan password');
            }
        }

        // Data kelas dari PHP untuk menghitung nama kelas otomatis
        const kelasDataFromDB = <?php echo json_encode($kelas_rows_all); ?>;
        const editKelasId = <?php echo $edit_kelas ? (int)$edit_kelas['id'] : 0; ?>;

        // Fungsi untuk menghitung nama kelas otomatis berdasarkan tingkat
        function generateNamaKelas() {
            const tingkatSelect = document.getElementById('kelas-tingkat');
            const namaKelasInput = document.getElementById('kelas-nama');
            const tingkat = tingkatSelect.value;

            if (!tingkat) {
                namaKelasInput.value = '';
                return;
            }

            // Jika mode edit, pertahankan nama kelas asli
            if (editKelasId > 0) {
                return;
            }

            // Hitung berapa kelas yang sudah ada di tingkat ini
            const existingNames = kelasDataFromDB
                .filter(k => k.tingkat === tingkat)
                .map(k => k.nama_kelas);

            // Cari huruf berikutnya yang belum dipakai (A, B, C, ...)
            const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let nextLetter = 'A';
            for (let i = 0; i < alphabet.length; i++) {
                const candidate = tingkat + '-' + alphabet[i];
                if (!existingNames.includes(candidate)) {
                    nextLetter = alphabet[i];
                    break;
                }
            }

            namaKelasInput.value = tingkat + '-' + nextLetter;
        }

        // Jalankan saat halaman dimuat untuk mengisi data jika sudah ada kelas yang dipilih
        document.addEventListener('DOMContentLoaded', function() {
            const kelasSelect = document.getElementById('siswa-kelas');
            if (kelasSelect && kelasSelect.value !== '') {
                updateJurusanWaliKelas();
            }

            // Auto-generate nama kelas jika tingkat sudah dipilih (untuk mode tambah baru)
            const tingkatSelect = document.getElementById('kelas-tingkat');
            if (tingkatSelect && tingkatSelect.value !== '' && editKelasId === 0) {
                generateNamaKelas();
            }
        });

        // Helper fungsi untuk halaman import excel
        function toggleSourceType() {
            const serverRadio = document.querySelector('input[name="source_type"][value="server_file"]');
            const uploadWrapper = document.getElementById('upload-file-wrapper');
            const fileInput = document.getElementById('excel_file');
            if (serverRadio && serverRadio.checked) {
                if (uploadWrapper) uploadWrapper.style.display = 'none';
                if (fileInput) fileInput.required = false;
            } else {
                if (uploadWrapper) uploadWrapper.style.display = 'block';
                if (fileInput) fileInput.required = true;
            }
        }

        function handleFileSelect(input) {
            const labelText = document.getElementById('file-label-text');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                labelText.innerHTML = `📄 <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} KB)`;
            }
        }

        function toggleModeHelp() {
            const modeSelect = document.getElementById('import_mode');
            const helpText = document.getElementById('mode-help');
            const sheetWrapper = document.getElementById('sheet-target-wrapper');
            if (!modeSelect || !helpText) return;

            if (modeSelect.value === 'standard_format') {
                helpText.innerHTML = 'Membaca format tabel standar kolom (Nama, NISN, Kelas, Jurusan, Jabatan, dll). Kolom header wajib ada di baris pertama.';
                if (sheetWrapper) sheetWrapper.style.display = 'none';
            } else {
                helpText.innerHTML = 'Membaca file roster sekolah seperti <code>A. KELAS X 2026-2027.xlsx</code>. Sistem otomatis mengekstrak nama kelas, jurusan, wali kelas, dan siswa.';
                if (sheetWrapper) sheetWrapper.style.display = 'block';
            }
        }

        // Drag and drop event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const dropzone = document.getElementById('dropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('dragover');
                    }, false);
                });

                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    const fileInput = document.getElementById('excel_file');
                    if (files && files.length > 0) {
                        fileInput.files = files;
                        handleFileSelect(fileInput);
                    }
                }, false);
            }
        });
    </script>
</body>
</html>
