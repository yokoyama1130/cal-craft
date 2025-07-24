<!-- templates/layout/admin.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Admin - <?= $this->fetch('title') ?></title>
    <?= $this->Html->css(['bootstrap.min']) ?>
</head>
<body>
    <div class="container mt-5">
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>
