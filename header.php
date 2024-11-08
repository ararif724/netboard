<?php require_once('config.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg bg-body-tertiary mb-3">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Netboard</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php addActiveClass('index.php'); ?>" href="index.php">Devices</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php addActiveClass('firewall.php'); ?>" href="firewall.php">Firewall</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php addActiveClass('device-dashboard.php'); ?>" href="device-dashboard.php">Device Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>