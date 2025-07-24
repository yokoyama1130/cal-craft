<!-- templates/layout/admin.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - <?= $this->fetch('title') ?></title>

    <!-- ✅ Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ その他カスタムCSS（必要なら） -->
    <?= $this->Html->css(['admin']) ?>
</head>
<body>
    <div class="container mt-5">
        <?= $this->fetch('content') ?>
    </div>

    <!-- ✅ BootstrapのJSも必要なら -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
