<?php
// $logo_path_prefix: path relatif ke folder logo/ dari file yang memanggil include ini
// Contoh: '../logo/' (dari subfolder guru/, siswa/, superadmin/)
//         'logo/'    (dari root inc/)
if (!isset($logo_path_prefix)) {
    $logo_path_prefix = '../logo/';
}
?>
<!-- LOGO BOX SIDEBAR -->
<div class="sidebar-logo-box">
    <img src="<?php echo $logo_path_prefix; ?>1. logo smk negeri 3 malang.png" alt="Logo SMKN 3 Malang">
    <div class="sidebar-logo-divider"></div>
    <img src="<?php echo $logo_path_prefix; ?>2. Universitas Negeri Malang Logo.png" alt="Logo Universitas Negeri Malang">
</div>
