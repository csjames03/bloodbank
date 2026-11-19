<?php /** @var string|null $title */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#be123c">
    <title><?= e(isset($title) ? $title . ' · ' . config('app.name') : config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
