<?php /** @var string $content */ ?>
<?php require BASE_PATH . '/views/partials/head.php'; ?>
<body>
<?php require BASE_PATH . '/views/partials/icons.php'; ?>
<?= $content ?>
<?php /* Form errors are shown inline on the sign-in card, so skip the duplicate toast. */ ?>
<?php if (!has_errors()) { require BASE_PATH . '/views/partials/toasts.php'; } ?>
</body>
</html>
