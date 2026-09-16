-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 11 Agu 2026 pada 14.39
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inc`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `tingkat` varchar(10) NOT NULL,
  `jurusan` varchar(100) NOT NULL,
  `wali_kelas` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `wali_kelas`, `created_at`, `updated_at`) VALUES
(1, 'X PH AKA', 'X', 'Perhotelan Akademik', 'Mawarlia, SST.Par', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(2, 'X PH WIRAUSAHA', 'X', 'Perhotelan Wirausaha', 'Mahardika Dian Alifta R, S.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(3, 'X PH INDUSTRI 1', 'X', 'Perhotelan Industri 1', 'Rizal Leni Godo Saraswati, S.ST.Par, MM', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(4, 'X PH INDUSTRI 2', 'X', 'Perhotelan Industri 2', 'Emy Dwi Widiarti, S.Tr.Par', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(5, 'X TKCKR-A', 'X', 'Tata Kec. Kulit & Rambut Akademik', 'Sri Wuryaningsih, M.Pd.', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(6, 'X TKCKR-W', 'X', 'Tata Kec. Kulit & Rambut Wirausaha', 'Anna Meliastanti, M.Pd.', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(7, 'X  KUL AKA', 'X', 'Kuliner Akademik ', 'Hadi Sasmianto,  S.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(8, 'X KUL WU', 'X', 'Kuliner Wirausaha', 'Tholi\'ah, M.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(9, 'X KUL INDUSTRI 1', 'X', 'Kuliner Industri 1', 'Rendra Dwicahya, S.Pd.', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(10, 'X KUL INDUSTRI 2', 'X', 'Kuliner Industri 2', 'Ir. Chotmaniyah, M.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(11, 'X DPB  1', 'X', 'Desain dan Produksi Busana 1', 'Ira Pramita Sari, S.Pd.', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(12, 'X DPB 2', 'X', 'Desain dan Produksi Busana 2', 'Surti Sri Wahyuni, S.Ag', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(13, 'X DPB 3', 'X', 'Desain dan Produksi Busana 3', 'Estik Susilowati, S.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(14, 'X DPB 4', 'X', 'Desain dan Produksi Busana 4', 'Cyintia Dewi Handari, S.Si', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(15, 'X TKJ AKA', 'X', 'Teknik Komputer dan Jaringan Akademik', 'Mohammad Erfan Efendi Z,S.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58'),
(16, 'X TKJ WU', 'X', 'Teknik Komputer dan Jaringan Wirausaha', 'Amir Hamzah, S.Pd', '2026-06-25 00:43:58', '2026-06-25 00:43:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penilaian_guru`
--

CREATE TABLE `penilaian_guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `resume_id` int(10) UNSIGNED NOT NULL,
  `guru_id` int(10) UNSIGNED NOT NULL,
  `score` int(11) DEFAULT NULL,
  `feedback` longtext NOT NULL,
  `saran` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penilaian_guru`
--

INSERT INTO `penilaian_guru` (`id`, `resume_id`, `guru_id`, `score`, `feedback`, `saran`, `created_at`, `updated_at`) VALUES
(2, 2, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(3, 3, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(4, 5, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(5, 6, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(6, 7, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(7, 8, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(8, 10, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(9, 11, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(10, 14, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(11, 15, 2, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(12, 21, 4, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(13, 25, 4, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28'),
(14, 27, 4, NULL, 'Bagus! Pertahankan. Resume Anda sudah sangat bagus.', 'Lanjutkan membaca dan menulis resume secara rutin.', '2026-06-26 00:11:28', '2026-06-26 00:11:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `resume_literasi`
--

CREATE TABLE `resume_literasi` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `tanggal` datetime NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `kegiatan` varchar(100) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `nama_pemateri_penerbit` varchar(255) DEFAULT NULL,
  `resume_text` longtext NOT NULL,
  `resume_html` longtext DEFAULT NULL,
  `dokumentasi_link` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `resume_literasi`
--

INSERT INTO `resume_literasi` (`id`, `user_id`, `nama`, `tanggal`, `kelas`, `kegiatan`, `judul`, `nama_pemateri_penerbit`, `resume_text`, `resume_html`, `dokumentasi_link`, `created_at`) VALUES
(2, 5, 'ADELYA GRISSELDA', '2026-06-05 17:11:28', 'X PA', 'Membaca Artikel', 'Teknologi AI Masa Depan', 'Tech News', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(3, 6, 'AGENG PERMADI', '2026-05-28 17:11:28', 'X PA', 'Membaca Berita', 'Berita Nasional Hari Ini', 'Kompas', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(4, 7, 'AIRA ZALIANA PUTRI', '2026-06-13 17:11:28', 'X PA', 'Membaca Buku', 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(5, 7, 'AIRA ZALIANA PUTRI', '2026-06-17 17:11:28', 'X PA', 'Membaca Buku', 'The 7 Habits of Highly Effective People', 'Stephen R. Covey', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(6, 8, 'ANDIKA SLAMET RIYADI', '2026-05-30 17:11:28', 'X PA', 'Membaca Buku', 'The 7 Habits of Highly Effective People', 'Stephen R. Covey', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(7, 9, 'ANDIRA SANDRA PRATIWI', '2026-06-13 17:11:28', 'X PA', 'Menyimak Materi', 'Bahasa Indonesia Sintaks', 'Bu Ani', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(8, 9, 'ANDIRA SANDRA PRATIWI', '2026-06-10 17:11:28', 'X PA', 'Membaca Artikel', 'Teknologi AI Masa Depan', 'Tech News', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(9, 10, 'AUREL LETNISYAH DWI OKTAVIA', '2026-06-02 17:11:28', 'X PA', 'Membaca Buku', 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(10, 11, 'AVIVA DEA APRILYA', '2026-05-30 17:11:28', 'X PA', 'Seminar', 'Seminar Teknologi', 'IT Community', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(11, 11, 'AVIVA DEA APRILYA', '2026-06-15 17:11:28', 'X PA', 'Diskusi', 'Diskusi Proyek Akhir', 'Kelompok 2', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(12, 12, 'BOMA RADITYA LINGGA NATA', '2026-05-28 17:11:28', 'X PA', 'Membaca Berita', 'Berita Nasional Hari Ini', 'Kompas', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(13, 12, 'BOMA RADITYA LINGGA NATA', '2026-06-17 17:11:28', 'X PA', 'Membaca Novel', 'Negeri 5 Menara', 'Ahmad Fuadi', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(14, 13, 'CALVIN MAULANA PUTRA JUVENTIO', '2026-06-03 17:11:28', 'X PA', 'Membaca Berita', 'Berita Nasional Hari Ini', 'Kompas', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(15, 14, 'DANENDRA LINTAR MAHOGRA', '2026-06-25 17:11:28', 'X PA', 'Membaca Berita', 'Update Pendidikan', 'Tribun News', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(16, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-25 17:11:28', 'X TKJ A', 'Menyimak Materi', 'Materi Matematika Kelas X', 'Pak Budi', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(17, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-15 17:11:28', 'X TKJ A', 'Diskusi', 'Diskusi Proyek Akhir', 'Kelompok 2', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(18, 16, 'AHMAD ANDRIAN', '2026-06-05 17:11:28', 'X TKJ A', 'Membaca Novel', 'Negeri 5 Menara', 'Ahmad Fuadi', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(19, 17, 'AJENG ARFINA KHOIRUNNISA PUTRI', '2026-06-04 17:11:28', 'X TKJ A', 'Menyimak Materi', 'Materi Matematika Kelas X', 'Pak Budi', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(20, 17, 'AJENG ARFINA KHOIRUNNISA PUTRI', '2026-05-29 17:11:28', 'X TKJ A', 'Menyimak Materi', 'Bahasa Indonesia Sintaks', 'Bu Ani', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(21, 18, 'AKBAR RAGIL PAMUNGKAS', '2026-06-16 17:11:28', 'X TKJ A', 'Diskusi', 'Diskusi Tugas Kelompok', 'Kelompok 1', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(22, 19, 'ANASTASYA ZULFA ALFIRANI', '2026-06-08 17:11:28', 'X TKJ A', 'Membaca Artikel', 'Tips Produktivitas', 'Life Hack Magazine', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(23, 20, 'BAGAS AJI PRASETYO', '2026-06-21 17:11:28', 'X TKJ A', 'Membaca Buku', 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(24, 20, 'BAGAS AJI PRASETYO', '2026-06-02 17:11:28', 'X TKJ A', 'Diskusi', 'Diskusi Proyek Akhir', 'Kelompok 2', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(25, 21, 'BORNIE HAMONANGAN PANGARIBUAN', '2026-06-06 17:11:28', 'X TKJ A', 'Menyimak Materi', 'Bahasa Indonesia Sintaks', 'Bu Ani', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(26, 22, 'CALLULA ALVIENA ZIZI', '2026-05-30 17:11:28', 'X TKJ A', 'Membaca Berita', 'Update Pendidikan', 'Tribun News', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(27, 22, 'CALLULA ALVIENA ZIZI', '2026-06-06 17:11:28', 'X TKJ A', 'Membaca Artikel', 'Tips Produktivitas', 'Life Hack Magazine', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(28, 23, 'CHIKA INTAN PRATIWI', '2026-06-04 17:11:28', 'X TKJ A', 'Membaca Buku', 'Atomic Habits', 'James Clear', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(29, 23, 'CHIKA INTAN PRATIWI', '2026-06-10 17:11:28', 'X TKJ A', 'Seminar', 'Seminar Teknologi', 'IT Community', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(30, 24, 'DENIS CHISBILAH SUNHAJI', '2026-06-06 17:11:28', 'X TKJ A', 'Membaca Novel', 'Negeri 5 Menara', 'Ahmad Fuadi', '\nSaya telah membaca materi ini dan mendapatkan banyak pengetahuan baru. Materi ini sangat bermanfaat untuk pengembangan saya ke depannya. Beberapa poin penting yang saya pelajari:\n1. Pentingnya konsistensi dalam belajar\n2. Cara mengatur waktu dengan baik\n3. Pentingnya berkolaborasi dengan orang lain\n\nSaya akan mencoba mengimplementasikan pengetahuan ini dalam kehidupan sehari-hari.\n', NULL, NULL, '2026-06-26 00:11:28'),
(31, 5, 'ADELYA GRISSELDA', '2026-06-26 00:19:00', 'X PA', 'Membaca Buku', 'Seporsi Mie Ayam Sebelum Mati', 'Prayoga Nugroho Pangestu', 'Seporsi Mie Ayam Sebelum Mati adalah buku ketiga Brian Krisna yang saya baca sekaligus buku kedua yang saya tamatkan (saya belum lanjut membaca Bandung Menjelang Pagi).\r\n\r\n\r\nAlasan saya membeli buku ini adalah karena saya suka pengalaman membaca buku &quot;Sisi Tergelap Surga&quot;. Oleh karena itu, saya mencoba untuk lanjut membaca karya-karyanya dan ternyata, saya juga suka dengan buku yang sangat viral ini: Seporsi Mie Ayam Sebelum Mati.\r\n\r\nSeporsi Mie ayam sebelum mati\r\nBuku ini menjelaskan tentang seorang laki - laki bernama Ale yang ingin bunuh diri. baginya, tidak ada satu aspek pun dalam hidupnya yang berjalan dengan baik. Fisiknya Wah Dia Jelek sekali.\r\n\r\nPekerjaan ? punya sih, tapi dia gk punya teman di kantornya', 'Seporsi Mie Ayam Sebelum Mati adalah buku ketiga Brian Krisna yang saya baca sekaligus buku kedua yang saya tamatkan (saya belum lanjut membaca Bandung Menjelang Pagi).<br><br><div>Alasan saya membeli buku ini adalah karena saya suka pengalaman membaca buku \"Sisi Tergelap Surga\". Oleh karena itu, saya mencoba untuk lanjut membaca karya-karyanya dan ternyata, saya juga suka dengan buku yang sangat viral ini: Seporsi Mie Ayam Sebelum Mati.<br><br>Seporsi Mie ayam sebelum mati<br>Buku ini menjelaskan tentang seorang laki - laki bernama Ale yang ingin bunuh diri. baginya, tidak ada satu aspek pun dalam hidupnya yang berjalan dengan baik. Fisiknya Wah Dia Jelek sekali.<br><br>Pekerjaan ? punya sih, tapi dia gk punya teman di kantornya</div>', '', '2026-06-26 00:28:19'),
(32, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-26 15:52:00', 'X TKJ AKA', 'Diskusi', 'diskusi', 'miss lia', 'nzbzbz ns sns d dnd xnd dn dnd dnd dnd nd ddnd dnd d. d d nd n n. ncn ncn. n. nn nn n n n n n m m mk k n n n n n n. nn. n n n n n n nn n n n n n n n n n nn n n. nn n n. nn n n n n n n n n n. n nn nn m m m. m mma a a a a a a a. a a a a a a m m m m m m m. m m m e e e e e. e e.  l l l l l l. l la ana. a a. a a a a a a', 'nzbzbz ns sns d dnd xnd dn dnd dnd dnd nd ddnd dnd d. d d nd n n. ncn ncn. n. nn nn n n n n n m m mk k n n n n n n. nn. n n n n n n nn n n n n n n n n n nn n n. nn n n. nn n n n n n n n n n. n nn nn m m m. m mma a a a a a a a. a a a a a a m m m m m m m. m m m e e e e e. e e.&nbsp; l l l l l l. l la ana. a a. a a a a a a', 'https://drive.google.com/file/d/1OTtPKq6WMkMiL55tngrS1g11JSXu6N7g/view?usp=drivesdk', '2026-06-26 15:59:24'),
(33, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-26 18:11:00', 'X TKJ AKA', 'Membaca Buku', 'aku', 'dia', 'sbs s s s e. e e e e e e e e. e r r r r r r r. r r r r r r r r. r r r r r d. f f f f d d. d d f f r r. r d d d d d f. f d d d d f f f. d d d d d d. d d d d d d d d d d d. d d d d d d d. d d d d d d. d d d d d d d d d. d', 'sbs s s s e. e e e e e e e e. e r r r r r r r. r r r r r r r r. r r r r r d. f f f f d d. d d f f r r. r d d d d d f. f d d d d f f f. d d d d d d. d d d d d d d d d d d. d d d d d d d. d d d d d d. d d d d d d d d d. d', '', '2026-06-26 18:12:26'),
(34, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-26 20:00:00', 'X TKJ AKA', 'Membaca Berita', 'h', 'h', 'h h h h h h hh h h h h h h h jj j j j j j j jj j j j j j j ju j j j j j j jj j j j j j j j jj j j j j j j j j j j u uj u u u h h h h y yu u u i uj j h h h h j u j u ju j u u u u u u u u u u u u u y yy u u u u u u u y uu u', 'h h h h h h hh h h h h h h h jj j j j j j j jj j j j j j j ju j j j j j j jj j j j j j j j jj j j j j j j j j j j u uj u u u h h h h y yu u u i uj j h h h h j u j u ju j u u u u u u u u u u u u u y yy u u u u u u u y uu u&nbsp;', '', '2026-06-26 20:01:42'),
(35, 15, 'ACHMAD AWALUDIN MULYA', '2026-06-26 20:27:00', 'X TKJ AKA', 'Membaca Berita', 'hai', 'ibu', 's s s s s. s s s s s s s. s s s s s s. s d d. d s s s. a a a a a s. s s s a s. s s d d. d d d d d d s. s s s s s s. s s s s s s s. s s s s s s s s. s s s s s s s. ss. s s s s s. s s s s s s s s s s. se. s s s s s. s s s s s s s. ses s. s', 's s s s s. s s s s s s s. s s s s s s. s d d. d s s s. a a a a a s. s s s a s. s s d d. d d d d d d s. s s s s s s. s s s s s s s. s s s s s s s s. s s s s s s s. ss. s s s s s. s s s s s s s s s s. se. s s s s s. s s s s s s s. ses s. s', '', '2026-06-26 20:28:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jabatan` enum('Super Admin','Guru','Siswa') NOT NULL,
  `nis_nip` varchar(50) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `jurusan` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@example.com', '$2y$12$QLzuFxmXxiExISo0j3.JFeev403gwf0bZCU0Uo11k0EpJtXEIiq/a', 'Super Admin', 'SA0001', '-', '-', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(2, 'Mawarlia, SST.Par', 'guru002@example.com', '$2y$10$7ehf9UIclph7QsywMBph/eswrNme4uYsvsDXbEjpY41kaF7qRGYFi', 'Guru', 'GURU002', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:49:04'),
(3, 'Amir Hamzah, S.Pd', 'guru003@example.com', '$2y$12$J3d2o3fnqVFV5QpYlifObOTbOZAUVV/jojJQiBQkCYHa6qKaoHrd2', 'Guru', 'GURU003', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-26 15:51:33'),
(4, 'Sri Wuryaningsih, M.Pd.', 'guru004@example.com', '$2y$12$oQoGzzZQYk26FjrH2Vpt6uX8WYMee5HP.tDFTZL6o6SNHgVSkdVlK', 'Guru', 'GURU004', 'X TKJ A', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(5, 'ADELYA GRISSELDA', 'adelya.grisselda@example.com', '$2y$10$zpAle77ESzfPYGd4tlbLZ.hxIysVmrvR1qO/a/SO9XwiBFj0F2HI.', 'Siswa', '00110', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 23:48:19'),
(6, 'AGENG PERMADI', 'ageng.permadi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA002', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(7, 'AIRA ZALIANA PUTRI', 'aira.zaliana.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA003', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(8, 'ANDIKA SLAMET RIYADI', 'andika.slamet.riyadi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA004', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(9, 'ANDIRA SANDRA PRATIWI', 'andira.sandra.pratiwi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA005', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(10, 'AUREL LETNISYAH DWI OKTAVIA', 'aurel.letnisyah.dwi.oktavia@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA006', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(11, 'AVIVA DEA APRILYA', 'aviva.dea.aprilya@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA007', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(12, 'BOMA RADITYA LINGGA NATA', 'boma.raditya.lingga.nata@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA008', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(13, 'CALVIN MAULANA PUTRA JUVENTIO', 'calvin.maulana.putra.juventio@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA009', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(14, 'DANENDRA LINTAR MAHOGRA', 'danendra.lintar.mahogra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA010', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(15, 'ACHMAD AWALUDIN MULYA', 'achmad.awaludin.mulya@example.com', '$2y$12$yn7G3xzjlojKdAQSJJHl1.70ObO5L3KVf8vuG2J5BaaE1eFVW70Tm', 'Siswa', 'SISWA011', 'X TKJ AKA', 'Teknik Komputer dan Jaringan Akademik', 1, '2026-06-25 00:26:17', '2026-06-26 13:36:17'),
(16, 'AHMAD ANDRIAN', 'ahmad.andrian@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA012', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(17, 'AJENG ARFINA KHOIRUNNISA PUTRI', 'ajeng.arfina.khoirunnisa.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA013', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(18, 'AKBAR RAGIL PAMUNGKAS', 'akbar.ragil.pamungkas@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA014', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(19, 'ANASTASYA ZULFA ALFIRANI', 'anastasya.zulfa.alfirani@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA015', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(20, 'BAGAS AJI PRASETYO', 'bagas.aji.prasetyo@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA016', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(21, 'BORNIE HAMONANGAN PANGARIBUAN', 'bornie.hamonangan.pangaribuan@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA017', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(22, 'CALLULA ALVIENA ZIZI', 'callula.alviena.zizi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA018', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(23, 'CHIKA INTAN PRATIWI', 'chika.intan.pratiwi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA019', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(24, 'DENIS CHISBILAH SUNHAJI', 'denis.chisbilah.sunhaji@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA020', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(25, 'Mohammad Erfan Efendi Z,S.Pd', 'guru@example.com', '$2y$12$L6vVmsRrZEqlFvX236GgwuMAhwP9ST3yf3PeGi6qG3svK2KX3uYhq', 'Guru', 'GURU001', 'X TKJ A', 'Teknik Komputer dan Jaringan Akademik', 1, '2026-06-26 15:58:37', '2026-06-26 15:58:37');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kelas` (`tingkat`,`nama_kelas`);

--
-- Indeks untuk tabel `penilaian_guru`
--
ALTER TABLE `penilaian_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_resume_guru` (`resume_id`,`guru_id`),
  ADD KEY `guru_id` (`guru_id`);

--
-- Indeks untuk tabel `resume_literasi`
--
ALTER TABLE `resume_literasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_email` (`email`),
  ADD UNIQUE KEY `uniq_nis_nip` (`nis_nip`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `penilaian_guru`
--
ALTER TABLE `penilaian_guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `resume_literasi`
--
ALTER TABLE `resume_literasi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `penilaian_guru`
--
ALTER TABLE `penilaian_guru`
  ADD CONSTRAINT `penilaian_guru_ibfk_1` FOREIGN KEY (`resume_id`) REFERENCES `resume_literasi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penilaian_guru_ibfk_2` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `resume_literasi`
--
ALTER TABLE `resume_literasi`
  ADD CONSTRAINT `resume_literasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
