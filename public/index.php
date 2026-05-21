<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
initialize_database();

$route = $_GET['route'] ?? 'dashboard';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'login') {
    if (login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: /');
        exit;
    }
    $error = 'Credenciales inválidas.';
}

if ($route === 'logout') {
    logout();
    header('Location: /?route=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'tickets.store') {
    $user = require_login();
    $stmt = db()->prepare('INSERT INTO tickets(title, device, priority, description, created_by, assigned_to, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $now = date('c');
    $stmt->execute([
        trim($_POST['title'] ?? ''),
        trim($_POST['device'] ?? ''),
        $_POST['priority'] ?? 'Media',
        trim($_POST['description'] ?? ''),
        $user['id'],
        $_POST['assigned_to'] ?: null,
        $now,
        $now
    ]);
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'tickets.status') {
    $user = require_login();
    if (in_array($user['role'], ['admin', 'tecnico'], true)) {
        $stmt = db()->prepare('UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$_POST['status'] ?? 'Abierto', date('c'), (int) ($_POST['id'] ?? 0)]);
    }
    header('Location: /');
    exit;
}

function layout(string $title, string $content): void
{
    $user = current_user();
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' | Soporte360</title><link rel="stylesheet" href="/assets/style.css"></head><body>';
    echo '<nav><strong>Soporte360</strong><span>Sistema de tickets técnicos</span>';
    if ($user) echo '<a href="/?route=logout">Salir</a>'; else echo '<a href="/?route=login">Ingresar</a>';
    echo '</nav><main>' . $content . '</main></body></html>';
}

if ($route === 'login') {
    ob_start();
    if ($error) echo '<p class="alert">' . h($error) . '</p>';
    echo '<section class="card narrow"><h1>Iniciar sesión</h1><form method="post" action="/?route=login">';
    echo '<label>Email<input name="email" type="email" required value="admin@soporte360.local"></label>';
    echo '<label>Contraseña<input name="password" type="password" required value="admin123"></label>';
    echo '<button>Entrar</button></form></section>';
    layout('Login', ob_get_clean());
    exit;
}

$user = require_login();
$pdo = db();
$stats = $pdo->query("SELECT status, COUNT(*) total FROM tickets GROUP BY status")->fetchAll();
$techs = $pdo->query("SELECT id, name FROM users WHERE role IN ('admin','tecnico') ORDER BY name")->fetchAll();
$tickets = $pdo->query("SELECT t.*, u.name created_name, a.name assigned_name
    FROM tickets t
    JOIN users u ON u.id = t.created_by
    LEFT JOIN users a ON a.id = t.assigned_to
    ORDER BY datetime(t.created_at) DESC")->fetchAll();

ob_start();
echo '<header class="hero"><div><p class="eyebrow">Panel operativo</p><h1>Hola, ' . h($user['name']) . '</h1><p>Gestiona solicitudes de soporte, conectividad, diagnóstico y mantenimiento.</p></div><span class="badge">Rol: ' . h($user['role']) . '</span></header>';

echo '<section class="grid stats">';
foreach ($stats as $s) {
    echo '<article class="card"><p>' . h($s['status']) . '</p><strong>' . h((string) $s['total']) . '</strong></article>';
}
echo '</section>';

echo '<section class="card"><h2>Nuevo ticket</h2><form class="ticket-form" method="post" action="/?route=tickets.store">';
echo '<input name="title" placeholder="Título del problema" required>';
echo '<input name="device" placeholder="Equipo o dispositivo" required>';
echo '<select name="priority"><option>Alta</option><option selected>Media</option><option>Baja</option></select>';
echo '<select name="assigned_to"><option value="">Sin asignar</option>';
foreach ($techs as $tech) echo '<option value="' . h((string) $tech['id']) . '">' . h($tech['name']) . '</option>';
echo '</select><textarea name="description" placeholder="Describe el diagnóstico inicial" required></textarea><button>Crear ticket</button></form></section>';

echo '<section class="card"><h2>Tickets recientes</h2><div class="table">';
echo '<div class="row head"><span>ID</span><span>Problema</span><span>Equipo</span><span>Prioridad</span><span>Estado</span><span>Responsable</span></div>';
foreach ($tickets as $ticket) {
    echo '<div class="row"><span>#' . h((string) $ticket['id']) . '</span><span><strong>' . h($ticket['title']) . '</strong><small>' . h($ticket['description']) . '</small></span><span>' . h($ticket['device']) . '</span><span>' . h($ticket['priority']) . '</span><span>';
    if (in_array($user['role'], ['admin', 'tecnico'], true)) {
        echo '<form method="post" action="/?route=tickets.status"><input type="hidden" name="id" value="' . h((string) $ticket['id']) . '"><select name="status" onchange="this.form.submit()">';
        foreach (['Abierto','En progreso','Resuelto'] as $status) {
            $selected = $ticket['status'] === $status ? 'selected' : '';
            echo '<option ' . $selected . '>' . h($status) . '</option>';
        }
        echo '</select></form>';
    } else {
        echo h($ticket['status']);
    }
    echo '</span><span>' . h($ticket['assigned_name'] ?? 'Sin asignar') . '</span></div>';
}
echo '</div></section>';
layout('Dashboard', ob_get_clean());
