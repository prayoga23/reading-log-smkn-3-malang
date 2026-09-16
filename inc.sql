-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 24 Jun 2026 pada 19.51
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

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
  `feedback` longtext NOT NULL,
  `saran` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'Amir Hamzah, S.Pd', 'guru003@example.com', '$2y$12$oQoGzzZQYk26FjrH2Vpt6uX8WYMee5HP.tDFTZL6o6SNHgVSkdVlK', 'Guru', 'GURU003', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(4, 'Sri Wuryaningsih, M.Pd.', 'guru004@example.com', '$2y$12$oQoGzzZQYk26FjrH2Vpt6uX8WYMee5HP.tDFTZL6o6SNHgVSkdVlK', 'Guru', 'GURU004', 'X TKJ A', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(5, 'ADELYA GRISSELDA', 'adelya.grisselda@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA001', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(6, 'AGENG PERMADI', 'ageng.permadi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA002', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(7, 'AIRA ZALIANA PUTRI', 'aira.zaliana.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA003', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(8, 'ANDIKA SLAMET RIYADI', 'andika.slamet.riyadi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA004', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(9, 'ANDIRA SANDRA PRATIWI', 'andira.sandra.pratiwi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA005', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(10, 'AUREL LETNISYAH DWI OKTAVIA', 'aurel.letnisyah.dwi.oktavia@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA006', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(11, 'AVIVA DEA APRILYA', 'aviva.dea.aprilya@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA007', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(12, 'BOMA RADITYA LINGGA NATA', 'boma.raditya.lingga.nata@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA008', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(13, 'CALVIN MAULANA PUTRA JUVENTIO', 'calvin.maulana.putra.juventio@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA009', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(14, 'DANENDRA LINTAR MAHOGRA', 'danendra.lintar.mahogra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA010', 'X PA', 'Perhotelan Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(15, 'ACHMAD AWALUDIN MULYA', 'achmad.awaludin.mulya@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA011', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(16, 'AHMAD ANDRIAN', 'ahmad.andrian@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA012', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(17, 'AJENG ARFINA KHOIRUNNISA PUTRI', 'ajeng.arfina.khoirunnisa.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA013', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(18, 'AKBAR RAGIL PAMUNGKAS', 'akbar.ragil.pamungkas@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA014', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(19, 'ANASTASYA ZULFA ALFIRANI', 'anastasya.zulfa.alfirani@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA015', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(20, 'BAGAS AJI PRASETYO', 'bagas.aji.prasetyo@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA016', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(21, 'BORNIE HAMONANGAN PANGARIBUAN', 'bornie.hamonangan.pangaribuan@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA017', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(22, 'CALLULA ALVIENA ZIZI', 'callula.alviena.zizi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA018', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(23, 'CHIKA INTAN PRATIWI', 'chika.intan.pratiwi@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA019', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17'),
(24, 'DENIS CHISBILAH SUNHAJI', 'denis.chisbilah.sunhaji@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', 'SISWA020', 'X TKJ A', 'Teknik Komputer Akademik', 1, '2026-06-25 00:26:17', '2026-06-25 00:26:17');

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `resume_literasi`
--
ALTER TABLE `resume_literasi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
