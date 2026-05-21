<?php
declare(strict_types=1);

session_start();

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('DB_PATH', STORAGE_PATH . '/soporte360.sqlite');

if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0775, true);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function initialize_database(): void
{
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin','tecnico','cliente'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        device TEXT NOT NULL,
        priority TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Abierto',
        description TEXT NOT NULL,
        created_by INTEGER NOT NULL,
        assigned_to INTEGER,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        FOREIGN KEY(created_by) REFERENCES users(id),
        FOREIGN KEY(assigned_to) REFERENCES users(id)
    )");

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO users(name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Janeth Admin', 'admin@soporte360.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $stmt->execute(['Técnico de Campo', 'tecnico@soporte360.local', password_hash('tecnico123', PASSWORD_DEFAULT), 'tecnico']);
        $stmt->execute(['Cliente Demo', 'cliente@soporte360.local', password_hash('cliente123', PASSWORD_DEFAULT), 'cliente']);

        $ticket = $pdo->prepare('INSERT INTO tickets(title, device, priority, status, description, created_by, assigned_to, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('c');
        $ticket->execute(['Laptop sin conexión WiFi', 'HP Laptop 15', 'Alta', 'En progreso', 'Equipo detecta red, pero no obtiene IP. Revisar controlador y configuración.', 3, 2, $now, $now]);
        $ticket->execute(['Mantenimiento preventivo', 'Impresora Epson', 'Media', 'Abierto', 'Limpieza general, prueba de impresión y revisión de rodillos.', 3, 2, $now, $now]);
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /?route=login');
        exit;
    }
    return $user;
}

function login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function logout(): void
{
    session_destroy();
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
