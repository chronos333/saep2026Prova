<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3efe5">
    <title><?= e($title) ?> · <?= e($brandName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body class="login-page signup-page">
    <main class="signup-shell">
        <a class="brand signup-brand" href="login.php">
            <span class="brand-mark" aria-hidden="true">🐾</span>
            <span class="brand-name"><?= e($brandName) ?><small>UM LUGAR FEITO PARA PETS</small></span>
        </a>
        <section class="signup-intro">
            <span class="eyebrow">NOSSA TURMA CRESCEU!</span>
            <h1>Um espaço só seu e do seu melhor amigo.</h1>
            <p>Crie sua conta gratuita para guardar os dados do seu pet e acompanhar os atendimentos da clínica.</p>
        </section>
        <section class="content-card signup-card">
            <div class="section-heading">
                <div><span class="eyebrow">VAMOS COMEÇAR?</span><h2>Cadastre sua família</h2></div>
                <span class="signup-paw" aria-hidden="true">🐾</span>
            </div>
            <?php if ($errors !== []): ?>
                <div class="alert alert-error" role="alert">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" class="form-grid signup-grid">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <label class="field-label field-full">Seu nome completo
                    <input class="input" name="nome" maxlength="100" autocomplete="name" value="<?= e($form['nome']) ?>" required>
                </label>
                <label class="field-label">CPF
                    <input class="input" name="cpf" inputmode="numeric" autocomplete="off" maxlength="20" value="<?= e($form['cpf']) ?>" placeholder="000.000.000-00" required>
                    <span class="field-hint">Informado para o cadastro e armazenado com proteção.</span>
                </label>
                <label class="field-label">Celular / WhatsApp
                    <input class="input" name="telefone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($form['telefone']) ?>" placeholder="(11) 99999-9999" required>
                </label>
                <label class="field-label field-full">Seu melhor e-mail
                    <input class="input" name="email" type="email" maxlength="150" autocomplete="email" value="<?= e($form['email']) ?>" placeholder="voce@email.com" required>
                </label>
                <label class="field-label">Crie uma senha
                    <input class="input" name="senha" type="password" minlength="10" maxlength="72" autocomplete="new-password" required>
                    <span class="field-hint">Use de 10 a 72 caracteres.</span>
                </label>
                <label class="field-label">Confirme sua senha
                    <input class="input" name="confirmar_senha" type="password" minlength="10" maxlength="72" autocomplete="new-password" required>
                </label>
                <button class="button button-primary button-wide field-full" type="submit">Criar meu espaço <span aria-hidden="true">→</span></button>
            </form>
            <div class="signup-privacy"><span aria-hidden="true">♡</span> Seus dados são tratados com cuidado e usados para organizar o atendimento do seu pet.</div>
        </section>
        <p class="signup-login">Já tem uma conta? <a class="text-link" href="login.php">Entre por aqui</a></p>
    </main>
</body>
</html>
