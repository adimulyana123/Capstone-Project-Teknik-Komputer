<?php
$conn = new mysqli("localhost", "root", "", "capstone");

$data = $conn->query("SELECT * FROM data_air ORDER BY id DESC LIMIT 1");

if ($row = $data->fetch_assoc()):
?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Volume: <?= $row['volume'] ?> L</h5>
            <p class="card-text">Tinggi: <?= $row['ketinggian'] ?> cm</p>
            <p class="card-text"><small class="text-muted">Waktu: <?= $row['waktu'] ?></small></p>
        </div>
    </div>
<?php
else:
    echo "<div class='alert alert-warning'>Belum ada data.</div>";
endif;
?>