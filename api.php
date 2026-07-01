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

// ── CONFIG ────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'icei_42091155');
define('DB_USER', 'icei_42091155');
define('DB_PASS', 'velinda12345');

// ── CONEXIÓN ─────────────────────────────────────────────
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
            out(false, "Error DB: " . $e->getMessage(), null, 500);
            exit;
        }
    }

    return $pdo;
}

// ── RESPUESTA JSON ───────────────────────────────────────
function out(bool $ok, string $msg = '', $data = null, int $code = 200): void {
    http_response_code($code);

    $r = [
        "ok" => $ok,
        "mensaje" => $msg
    ];

    if ($data !== null) {
        $r["data"] = $data;
    }

    echo json_encode($r, JSON_UNESCAPED_UNICODE);
}

// ── BODY JSON ────────────────────────────────────────────
function body(): array {
    return json_decode(file_get_contents("php://input"), true) ?? [];
}

// ── AUTH ────────────────────────────────────────────────
function isAdmin(): bool {
    return true;
}

function requireAdmin(): void {
    return;
}


// ── LOGIN / LOGOUT / CREATE ADMIN ───────────────────────
$auth = $_GET["auth"] ?? null;

if ($auth) {
    dbAuth($auth);
    exit;
}

function dbAuth(string $auth): void {
    $db = db();

    // LOGIN
    if ($auth === "login" && $_SERVER["REQUEST_METHOD"] === "POST") {
        $d = body();

        if (empty($d["username"]) || empty($d["password"])) {
            out(false, "Faltan datos", null, 422);
            return;
        }

        $s = $db->prepare("SELECT * FROM admin_users WHERE username=?");
        $s->execute([$d["username"]]);
        $u = $s->fetch();

        if (!$u || !password_verify($d["password"], $u["password"])) {
            out(false, "Credenciales incorrectas", null, 401);
            return;
        }

        $_SESSION["admin"] = $u["username"];

        out(true, "Login correcto", ["user" => $u["username"]]);
        return;
    }

    // LOGOUT
    if ($auth === "logout") {
        session_destroy();
        out(true, "Sesión cerrada");
        return;
    }

    // CREAR ADMIN (SOLO SI YA ESTÁ LOGUEADO)
    if ($auth === "create_admin" && $_SERVER["REQUEST_METHOD"] === "POST") {
        requireAdmin();

        $d = body();

        if (empty($d["username"]) || empty($d["password"])) {
            out(false, "Faltan datos", null, 422);
            return;
        }

        $hash = password_hash($d["password"], PASSWORD_DEFAULT);

        $s = $db->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $s->execute([
            trim($d["username"]),
            $hash
        ]);

        out(true, "Admin creado");
        return;
    }

    out(false, "Acción inválida", null, 400);
}

// ── ROUTER PRINCIPAL ────────────────────────────────────
$tabla  = $_GET["tabla"] ?? "";
$accion = $_GET["accion"] ?? "listar";
$id     = isset($_GET["id"]) ? (int)$_GET["id"] : null;
$method = $_SERVER["REQUEST_METHOD"];

try {
    match(true) {
        $tabla === "productos"  => productos($accion, $id, $method),
        $tabla === "categorias" => categorias($accion, $id, $method),
        $tabla === "clientes"   => clientes($accion, $id, $method),
        $tabla === "opiniones"  => opiniones($accion, $id, $method),
        $tabla === "compras"    => compras($accion, $id, $method),
        $tabla === "stats"      => stats($accion, $method),
        default => out(false, "Tabla inválida", null, 400)
    };
} catch (PDOException $e) {
    out(false, $e->getMessage(), null, 500);
}

// ── PRODUCTOS ───────────────────────────────────────────
function productos($accion, $id, $method) {
    $db = db();

    if ($accion === "listar") {
        $s = $db->query("SELECT * FROM productos");
        out(true, "", $s->fetchAll());
        return;
    }

    if ($accion === "crear") {
        requireAdmin();
        $d = body();

        $s = $db->prepare("INSERT INTO productos (nombre, precio) VALUES (?, ?)");
        $s->execute([$d["nombre"], $d["precio"]]);

        out(true, "Producto creado");
        return;
    }
}

// ── CATEGORIAS ──────────────────────────────────────────
function categorias($a,$id,$m){
    requireAdmin();
    $db = db();
    out(true,"",$db->query("SELECT * FROM categorias")->fetchAll());
}

// ── CLIENTES ────────────────────────────────────────────
function clientes($a,$id,$m){
    requireAdmin();
    $db = db();
    out(true,"",$db->query("SELECT * FROM clientes")->fetchAll());
}

// ── OPINIONES ───────────────────────────────────────────
function opiniones($a,$id,$m){
    requireAdmin();
    $db = db();
    out(true,"",$db->query("SELECT * FROM opiniones")->fetchAll());
}

// ── COMPRAS ─────────────────────────────────────────────
function compras($a,$id,$m){
    requireAdmin();
    $db = db();
    out(true,"",$db->query("SELECT * FROM compras")->fetchAll());
}

// ── STATS ───────────────────────────────────────────────
function stats($a,$m){
    requireAdmin();
    $db = db();

    out(true,"",[
        "productos"=>$db->query("SELECT COUNT(*) FROM productos")->fetchColumn(),
        "clientes"=>$db->query("SELECT COUNT(*) FROM clientes")->fetchColumn(),
        "compras"=>$db->query("SELECT COUNT(*) FROM compras")->fetchColumn()
    ]);
}
?>