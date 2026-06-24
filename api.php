<?php
// ============================================================
//  VELINDA — API BACKEND UNIFICADA
// ============================================================

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── CONFIG ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'icei_42091155');
define('DB_USER', 'icei_42091155');
define('DB_PASS', 'velinda12345');

// ── CONEXIÓN ──────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            out(false, 'Error de conexión: ' . $e->getMessage(), null, 500);
            exit;
        }
    }
    return $pdo;
}

// ── RESPUESTA ─────────────────────────────────────────────
function out(bool $ok, string $msg = '', $data = null, int $code = 200): void {
    http_response_code($code);
    $r = ['ok' => $ok, 'mensaje' => $msg];

    if ($data !== null) {
        if (is_array($data) && isset($data[0])) {
            $r['total'] = count($data);
            $r['data'] = $data;
        } else {
            $r['data'] = $data;
        }
    }

    echo json_encode($r, JSON_UNESCAPED_UNICODE);
}

// ── AUTH ────────────────────────────────────────────────
function isAdmin(): bool {
    return isset($_SESSION['admin']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        out(false, 'No autorizado', null, 401);
        exit;
    }
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ── LOGIN ADMIN ─────────────────────────────────────────
$auth = $_GET['auth'] ?? null;

if ($auth) {
    routeAuth($auth);
    exit;
}

function routeAuth(string $accion): void {
    $db = db();

    if ($accion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $d = body();

        if (empty($d['username']) || empty($d['password'])) {
            out(false, 'Faltan datos', null, 422);
            return;
        }

        $s = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $s->execute([trim($d['username'])]);
        $user = $s->fetch();

        if (!$user || !password_verify($d['password'], $user['password'])) {
            out(false, 'Credenciales incorrectas', null, 401);
            return;
        }

        $_SESSION['admin'] = [
            'id' => $user['id'],
            'username' => $user['username']
        ];

        out(true, 'Login exitoso', [
            'username' => $user['username']
        ]);
        return;
    }

    if ($accion === 'logout') {
        session_destroy();
        out(true, 'Sesión cerrada');
        return;
    }

    out(false, 'Acción inválida', null, 400);
}

// ── ROUTER ───────────────────────────────────────────────
$tabla  = $_GET['tabla'] ?? '';
$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    match(true) {
        $tabla === 'productos'  => routeProductos($accion, $id, $method),
        $tabla === 'categorias' => routeCategorias($accion, $id, $method),
        $tabla === 'clientes'   => routeClientes($accion, $id, $method),
        $tabla === 'opiniones'  => routeOpiniones($accion, $id, $method),
        $tabla === 'compras'    => routeCompras($accion, $id, $method),
        $tabla === 'stats'      => routeStats($accion, $method),
        default                 => out(false, 'Tabla no válida', null, 400)
    };
} catch (PDOException $e) {
    out(false, 'Error de base de datos: ' . $e->getMessage(), null, 500);
}

// ════════════════════════════════════════════════════════════
//  PRODUCTOS
// ════════════════════════════════════════════════════════════
function routeProductos(string $accion, ?int $id, string $method): void {
    $db = db();

    if ($accion === 'listar' && $method === 'GET') {
        $s = $db->query("SELECT * FROM productos");
        out(true, '', $s->fetchAll());
        return;
    }

    if ($accion === 'crear' && $method === 'POST') {
        requireAdmin();
        $d = body();

        $s = $db->prepare("INSERT INTO productos (nombre, precio) VALUES (?, ?)");
        $s->execute([$d['nombre'], $d['precio']]);

        out(true, 'Producto creado');
        return;
    }

    out(false, 'Acción no válida', null, 400);
}

// ════════════════════════════════════════════════════════════
//  CATEGORIAS
// ════════════════════════════════════════════════════════════
function routeCategorias(string $accion, ?int $id, string $method): void {
    requireAdmin();
    $db = db();

    $s = $db->query("SELECT * FROM categorias");
    out(true, '', $s->fetchAll());
}

// ════════════════════════════════════════════════════════════
//  CLIENTES
// ════════════════════════════════════════════════════════════
function routeClientes(string $accion, ?int $id, string $method): void {
    requireAdmin();
    $db = db();

    $s = $db->query("SELECT * FROM clientes");
    out(true, '', $s->fetchAll());
}

// ════════════════════════════════════════════════════════════
//  OPINIONES
// ════════════════════════════════════════════════════════════
function routeOpiniones(string $accion, ?int $id, string $method): void {
    requireAdmin();
    $db = db();

    $s = $db->query("SELECT * FROM opiniones");
    out(true, '', $s->fetchAll());
}

// ════════════════════════════════════════════════════════════
//  COMPRAS
// ════════════════════════════════════════════════════════════
function routeCompras(string $accion, ?int $id, string $method): void {
    requireAdmin();
    $db = db();

    $s = $db->query("SELECT * FROM compras");
    out(true, '', $s->fetchAll());
}

// ════════════════════════════════════════════════════════════
//  STATS
// ════════════════════════════════════════════════════════════
function routeStats(string $accion, string $method): void {
    requireAdmin();
    $db = db();

    $productos = $db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $clientes  = $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $compras   = $db->query("SELECT COUNT(*) FROM compras")->fetchColumn();

    out(true, '', [
        'productos' => (int)$productos,
        'clientes'  => (int)$clientes,
        'compras'   => (int)$compras
    ]);
}
?>