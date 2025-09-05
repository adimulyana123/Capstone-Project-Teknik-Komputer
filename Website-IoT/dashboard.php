<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Air</title>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS dan Icon -->
    <link rel="stylesheet" href="css/style.css?v=1.1">
    <link rel="icon" type="image/png" href="img/water.png">

    <!-- Auto Refresh -->
    <meta http-equiv="refresh" content="2">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="page-wrapper d-flex flex-column min-vh-100">

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="#">
                    <img src="img/water.png" alt="Logo" width="30" height="30" class="me-2">
                    Dashboard Air
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-house-door-fill me-1"></i> Beranda</a></li>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Konten -->
        <div class="container py-5">
            <h3 class="text-center mb-5">Sistem Pengukur Volume Air</h3>

            <?php
            // Koneksi database
            $conn = new mysqli("localhost", "root", "", "capstone");
            if ($conn->connect_error) {
                die("<div class='alert alert-danger'>coy koneksi beli bisa.</div>");
            }

            // Ambil data terbaru
            $latest = $conn->query("SELECT * FROM data_air ORDER BY waktu DESC LIMIT 1")->fetch_assoc();
            if (!$latest) {
                echo "<div class='alert alert-warning'>langka data sing dikirim.</div>";
                exit;
            }

            $volume = floatval($latest['volume']);
            $tinggi = floatval($latest['ketinggian']);
            $waktu  = $latest['waktu'];
            $persen = min(100, max(0, round(($volume / 15) * 100))); // max 15L
            ?>

            <div class="row justify-content-center">
                <!-- Kartu Data -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-primary mb-4">
                        <div class="card-body">
                            <h5 class="card-title text-primary text-center">Informasi</h5>
                            <p class="card-text">Volume: <strong><?= $volume ?> L</strong></p>
                            <p class="card-text">Tinggi: <strong><?= $tinggi ?> cm</strong></p>
                            <p class="card-text"><small class="text-muted">Waktu: <?= $waktu ?></small></p>
                        </div>
                    </div>
                </div>

                <!-- Grafik Persentase & Liter -->
                <div class="col-md-8">
                    <div class="chart-container d-flex gap-4 flex-column flex-md-row">
                        <div class="flex-fill">
                            <h6 class="text-center">Indikator Persentase (%)</h6>
                            <canvas id="chartPersentase"></canvas>
                        </div>
                        <div class="flex-fill">
                            <h6 class="text-center">Volume dalam Liter (L)</h6>
                            <canvas id="chartLiter"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-light text-center py-3 mt-auto shadow-sm">
            <div class="container">
                <small>&copy; <?= date('Y') ?> Sistem Monitoring Air | TIM Capstone Project</small>
            </div>
        </footer>
    </div>

    <!-- Script Grafik -->
    <script>
        // Grafik Persentase
        new Chart(document.getElementById('chartPersentase'), {
            type: 'bar',
            data: {
                labels: [''],
                datasets: [{
                    data: [<?= $persen ?>],
                    backgroundColor: [<?= $persen >= 80 ? "'#198754'" : ($persen >= 40 ? "'#ffc107'" : "'#dc3545'") ?>]
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Persentase (%)'
                        }
                    },
                    y: {
                        ticks: {
                            display: false
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.raw + '%'
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Grafik Liter
        new Chart(document.getElementById('chartLiter'), {
            type: 'bar',
            data: {
                labels: [''],
                datasets: [{
                    data: [<?= $volume ?>],
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 15,
                        title: {
                            display: true,
                            text: 'Liter'
                        }
                    },
                    y: {
                        ticks: {
                            display: false
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>

</body>

</html>