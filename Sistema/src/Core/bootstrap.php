<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

const SESSION_LIFETIME = 3600;
const APP_NAME = 'Clube das Patas';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");

if (isset($_SESSION['user_id'])) {
    $now = time();
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    $createdAt = (int) ($_SESSION['created_at'] ?? 0);
    if ($lastActivity === 0 || $createdAt === 0 || $now - $lastActivity >= SESSION_LIFETIME || $now - $createdAt >= SESSION_LIFETIME) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Sua sessão expirou. Entre novamente para continuar.'];
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function homeForRole(): string
{
    return ($_SESSION['user_role'] ?? '') === 'cliente' ? 'cliente.php' : 'index.php';
}

function requireStaff(): void
{
    requireAuth();
    if (($_SESSION['user_role'] ?? 'equipe') !== 'equipe') {
        redirect(homeForRole());
    }
}

function requireCustomer(): void
{
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== 'cliente') {
        redirect(homeForRole());
    }
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrfToken(), $submittedToken)) {
        http_response_code(400);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function takeFlash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function cpfDigits(string $cpf): string
{
    return preg_replace('/\D+/', '', $cpf) ?? '';
}

function encryptCpf(string $cpf): string
{
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(cpfDigits($cpf), 'aes-256-gcm', cpfEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Não foi possível proteger o CPF.');
    }

    return implode(':', [base64_encode($iv), base64_encode($ciphertext), base64_encode($tag)]);
}

function decryptCpf(string $encryptedCpf): string
{
    $parts = explode(':', $encryptedCpf);
    if (count($parts) !== 3) {
        throw new RuntimeException('O CPF armazenado não está em um formato válido.');
    }
    $iv = base64_decode($parts[0], true);
    $ciphertext = base64_decode($parts[1], true);
    $tag = base64_decode($parts[2], true);
    if ($iv === false || $ciphertext === false || $tag === false) {
        throw new RuntimeException('O CPF armazenado não está em um formato válido.');
    }

    $cpf = openssl_decrypt($ciphertext, 'aes-256-gcm', cpfEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cpf === false) {
        throw new RuntimeException('Não foi possível descriptografar o CPF. Confira a chave APP_KEY.');
    }

    return $cpf;
}

function cpfSearchHash(string $cpf): string
{
    return hash_hmac('sha256', cpfDigits($cpf), cpfEncryptionKey());
}

function userInitial(string $name): string
{
    preg_match('/^./u', $name, $matches);
    return strtoupper($matches[0] ?? '?');
}

function validDate(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function renderHeader(string $title, string $activePage = ''): void
{
    $userName = (string) ($_SESSION['user_name'] ?? '');
    $flashMessage = takeFlash();
    $isCustomer = ($_SESSION['user_role'] ?? '') === 'cliente';
    $links = $isCustomer
        ? [
            'cliente.php' => ['Meu espaço', 'portal'],
            'cliente.php#pets' => ['Meus pets', 'pets'],
            'cliente.php#consultas' => ['Consultas', 'appointments'],
        ]
        : [
            'index.php' => ['Painel', 'dashboard'],
            'tutores.php' => ['Clientes', 'tutores'],
            'pets.php' => ['Pets', 'pets'],
            'agendamentos.php' => ['Agenda', 'agendamentos'],
        ];
    $brandName = APP_NAME;
    $homePage = $isCustomer ? 'cliente.php' : 'index.php';
    require __DIR__ . '/../Views/layout/header.php';
}

function renderFooter(): void
{
    require __DIR__ . '/../Views/layout/footer.php';
}

function dbFailure(Throwable $error, string $message = 'Não foi possível concluir a operação. Verifique os dados e tente novamente.'): void
{
    error_log((string) $error);
    flash('error', $message);
}
