<?php
// Menampilkan semua jenis error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Membuat koneksi ke database MySQL (localhost, user: root, tanpa password, database: capstone)
$koneksi = new mysqli("localhost", "root", "", "capstone");

// Mengecek apakah koneksi gagal
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Mengambil parameter dari URL (metode GET)
// Jika ada, nilai akan diambil dan diproses. Jika tidak, akan di-set default.
$waktu      = isset($_GET['waktu']) ? urldecode($_GET['waktu']) : ''; // waktu sebagai string
$volume     = isset($_GET['volume']) ? floatval($_GET['volume']) : null; // volume sebagai float
$ketinggian = isset($_GET['ketinggian']) ? floatval($_GET['ketinggian']) : null; // ketinggian sebagai float

// Mengecek apakah semua data tersedia dan valid
if (!empty($waktu) && $volume !== null && $ketinggian !== null) {

    // Menyiapkan query SQL untuk update data pada baris dengan id = 1
    $stmt = $koneksi->prepare("UPDATE data_air SET waktu = ?, volume = ?, ketinggian = ? WHERE id = 1");

    // Jika prepare berhasil
    if ($stmt) {
        // Mengikat parameter ke query: s = string, d = double
        $stmt->bind_param("sdd", $waktu, $volume, $ketinggian);

        // Menjalankan query
        if ($stmt->execute()) {
            echo "Data diperbarui"; // Jika berhasil, tampilkan pesan ini
        } else {
            echo "Gagal update: " . $stmt->error; // Jika gagal, tampilkan error
        }

        // Menutup statement
        $stmt->close();
    } else {
        echo "Prepare gagal: " . $koneksi->error; // Jika prepare gagal, tampilkan error
    }
} else {
    // Jika data tidak lengkap, tampilkan pesan ini
    echo "Data tidak lengkap";
}

// Menutup koneksi ke database
$koneksi->close();
