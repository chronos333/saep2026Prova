<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;
use VetCare\Models\Usuario;

final class LoginController
{
    public function handle(): void
    {
require_once __DIR__ . '/../Core/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    redirect(homeForRole());
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errorMessage = 'Informe um e-mail válido e sua senha.';
    } else {
        try {
            $user = (new Usuario(getDB()))->findByEmail($email);
            if ($user !== null && password_verify($password, $user['senha'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_role'] = $user['perfil'];
                $_SESSION['created_at'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                redirect('index.php');
            }
            $errorMessage = 'E-mail ou senha incorretos. Confira suas credenciais e tente novamente.';
        } catch (PDOException $error) {
            error_log((string) $error);
            $errorMessage = 'Não foi possível conectar ao banco de dados. Confira a configuração antes de entrar.';
        }
    }
}
$flashMessage = takeFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3efe5">
    <title>Entrar · Clube das Patas</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-story" aria-label="Bem-vindo ao Clube das Patas">
            <a class="brand brand-on-dark" href="login.php"><span class="brand-mark" aria-hidden="true">🐾</span><span class="brand-name">Clube das Patas<small>UM LUGAR FEITO PARA PETS</small></span></a>
            <div class="story-copy">
                <span class="eyebrow eyebrow-on-dark">AMOR, CUIDADO E PETISCOS</span>
                <h1>Seu pet está<br>em boas patas.</h1>
                <p>Um cantinho especial para acompanhar a saúde, os atendimentos e o dia a dia do seu melhor amigo.</p>
            </div>
            <div class="story-foot"><span class="story-dot"></span>Feito com carinho para toda a família.</div>
            <div class="story-orbit story-orbit-one"></div><div class="story-orbit story-orbit-two"></div>
        </section>
        <section class="login-panel">
            <div class="login-form-wrap">
                <span class="eyebrow">ÁREA EXCLUSIVA</span>
                <h2>Que bom ter você por aqui!</h2>
                <p class="muted">Entre para cuidar de tudo, da clínica ao seu pet.</p>
                <?php if ($flashMessage !== null): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= e($errorMessage) ?></div>
                <?php endif; ?>
                <form method="post" class="form-stack">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <label class="field-label" for="email">E-mail</label>
                    <input class="input" type="email" name="email" id="email" autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>" placeholder="voce@clinica.com" required>
                    <label class="field-label" for="password">Senha</label>
                    <input class="input" type="password" name="password" id="password" autocomplete="current-password" placeholder="Sua senha" required>
                    <button class="button button-primary button-wide" type="submit">Entrar na minha conta <span aria-hidden="true">→</span></button>
                </form>
                <p class="login-note">Ainda não faz parte da turma?</p>
                <a class="button button-secondary button-wide" href="cadastro.php">Criar minha conta grátis <span aria-hidden="true">→</span></a>
                <p class="login-note">Acesso da equipe: sessão segura com expiração automática.</p>
            </div>
            <div class="login-footer">Clube das Patas <span>·</span> Quem ama cuida. Quem cuida acompanha.</div>
        </section>
    </main>
</body>
</html>
<?php
    }
}
