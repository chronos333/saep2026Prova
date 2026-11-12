<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f2e9">
    <title><?= e($title) ?> · <?= e($brandName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e($homePage) ?>"><span class="brand-mark" aria-hidden="true">🐾</span><span class="brand-name"><?= e($brandName) ?><small><?= $isCustomer ? 'SEU PET, SEMPRE PERTO' : 'GESTÃO DA CLÍNICA' ?></small></span></a>
    <nav class="navigation" aria-label="Navegação principal">
        <?php foreach ($links as $href => [$label, $page]): ?>
            <a href="<?= e($href) ?>"<?= $activePage === $page ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="user-menu">
        <span class="user-avatar"><?= e(userInitial($userName)) ?></span>
        <span class="user-name"><?= e($userName) ?></span>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button class="button button-quiet button-small" type="submit">Sair</button>
        </form>
    </div>
</header>
<main class="main-content">
    <?php if ($flashMessage !== null): ?>
        <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
    <?php endif; ?>
