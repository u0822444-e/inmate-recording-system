<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Ipil District Jail — Inmate Recording System';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/png" href="assets/images/bjmp-logo.png">
    <meta name="theme-color" content="#092446">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/views.css">
    
</head>
<body>

<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/overview.js" defer></script>
<script src="assets/js/users.js" defer></script>