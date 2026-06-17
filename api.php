<?php
// ============================================================
//  VELINDA — API BACKEND UNIFICADA
//  Archivo: api.php
//  Adaptado para AeoFree / hosting gratuito con PHP + MySQL
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── CONFIG — EDITÁ ESTOS DATOS CON LOS DE TU HOSTING ─────────
// En AeoFree: entrá a Control Panel → MySQL Databases
// y copiá los datos que te dan al crear la base.
define('DB_HOST', 'localhost');                // Generalmente "localhost" en AeoFree
define('DB_NAME', 'icei_42091155');            // Nombre de tu base de datos
define('DB_USER', 'icei_42091155');            // Usuario de la base de datos
define('DB_PASS', 'velinda12345');             // Contraseña de la base de datos
define('ADMIN_KEY', 'velinda_admin_2025');     // Clave secreta para el panel admin — ¡cambiala!

// ── CONEXIÓN ─────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES  => false]
            );
        } catch (PDOException $e) {
            out(false, 'Error de conexión: ' . $e->getMessage(), null, 500); exit;
        }
    }
    return $pdo;
}

function out(bool $ok, string $msg = '', $data = null, int $code = 200): void {
    http_response_code($code);
    $r = ['ok' => $ok, 'mensaje' => $msg];
    if ($data !== null) {
        if (is_array($data) && isset($data[0])) {
            $r['total'] = count($data);
            $r['data']  = $data;
        } else {
            $r['data'] = $data;
        }
    }
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
}

function isAdmin(): bool {
    $key = $_SERVER['HTTP_X_ADMIN_KEY'] ?? $_GET['admin_key'] ?? '';
    return $key === ADMIN_KEY;
}

function requireAdmin(): void {
    if (!isAdmin()) { out(false, 'No autorizado', null, 401); exit; }
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ── ROUTER ───────────────────────────────────────────────────
$tabla  = $_GET['tabla']  ?? '';
$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    match(true) {
        $tabla === 'productos'   => routeProductos($accion, $id, $method),
        $tabla === 'categorias'  => routeCategorias($accion, $id, $method),
        $tabla === 'clientes'    => routeClientes($accion, $id, $method),
        $tabla === 'opiniones'   => routeOpiniones($accion, $id, $method),
        $tabla === 'compras'     => routeCompras($accion, $id, $method),
        $tabla === 'stats'       => routeStats($accion, $method),
        default                  => out(false, 'Tabla no válida', null, 400)
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
        $where = ["p.activo = 1"];
        $params = [];
        if (!empty($_GET['categoria']))  { $where[] = "c.slug = ?";          $params[] = $_GET['categoria']; }
        if (!empty($_GET['buscar']))     { $where[] = "p.nombre LIKE ?";      $params[] = '%' . $_GET['buscar'] . '%'; }
        if (!empty($_GET['destacado']))  { $where[] = "p.destacado = 1"; }
        if (!empty($_GET['nuevo']))      { $where[] = "p.nuevo = 1"; }
        if (!empty($_GET['oferta']))     { $where[] = "p.precio_oferta IS NOT NULL"; }
        $orden = match($_GET['orden'] ?? 'nuevo') {
            'precio_asc'  => 'p.precio ASC',
            'precio_desc' => 'p.precio DESC',
            'ventas'      => 'p.ventas DESC',
            default       => 'p.fecha_carga DESC'
        };
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug,
                       ROUND(COALESCE((SELECT AVG(estrellas) FROM opiniones WHERE producto_id = p.id AND aprobada = 1),0),1) AS rating,
                       (SELECT COUNT(*) FROM opiniones WHERE producto_id = p.id AND aprobada = 1) AS n_opiniones
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                WHERE " . implode(" AND ", $where) . " ORDER BY $orden";
        if (!empty($_GET['limit'])) { $sql .= " LIMIT " . (int)$_GET['limit']; }
        $s = $db->prepare($sql);
        $s->execute($params);
        out(true, '', $s->fetchAll());
        return;
    }

    if ($accion === 'uno' && $method === 'GET' && $id) {
        $s = $db->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug,
                    ROUND(COALESCE((SELECT AVG(estrellas) FROM opiniones WHERE producto_id = p.id AND aprobada = 1),0),1) AS rating,
                    (SELECT COUNT(*) FROM opiniones WHERE producto_id = p.id AND aprobada = 1) AS n_opiniones
             FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?"
        );
        $s->execute([$id]);
        $p = $s->fetch();
        $p ? out(true, '', $p) : out(false, 'Producto no encontrado', null, 404);
        return;
    }

    // Solo admin: listar TODOS (incluyendo inactivos)
    if ($accion === 'listar_admin' && $method === 'GET') {
        requireAdmin();
        $s = $db->query("SELECT p.*, c.nombre AS categoria_nombre FROM productos p JOIN categorias c ON p.categoria_id = c.id ORDER BY p.fecha_carga DESC");
        out(true, '', $s->fetchAll()); return;
    }

    if ($accion === 'crear' && $method === 'POST') {
        requireAdmin();
        $d = body();
        if (empty($d['nombre']) || empty($d['precio']) || empty($d['categoria_id']))
            { out(false, 'nombre, precio y categoria_id son obligatorios', null, 422); return; }
        $s = $db->prepare("INSERT INTO productos
            (nombre,descripcion,categoria_id,precio,precio_oferta,stock,talles,colores,imagen_url,imagen2_url,imagen3_url,destacado,nuevo,activo)
            VALUES(:n,:desc,:cat,:p,:po,:st,:ta,:co,:img,:img2,:img3,:dest,:nvo,:act)");
        $s->execute([
            ':n'=>trim($d['nombre']),':desc'=>$d['descripcion']??null,':cat'=>(int)$d['categoria_id'],
            ':p'=>(float)$d['precio'],':po'=>isset($d['precio_oferta']) && $d['precio_oferta']!=='' ?(float)$d['precio_oferta']:null,
            ':st'=>(int)($d['stock']??0),':ta'=>$d['talles']??null,':co'=>$d['colores']??null,
            ':img'=>$d['imagen_url']??null,':img2'=>$d['imagen2_url']??null,':img3'=>$d['imagen3_url']??null,
            ':dest'=>(int)($d['destacado']??0),':nvo'=>(int)($d['nuevo']??0),':act'=>(int)($d['activo']??1),
        ]);
        out(true, 'Producto creado', ['id' => $db->lastInsertId()], 201);
        return;
    }

    if ($accion === 'editar' && $method === 'PUT' && $id) {
        requireAdmin();
        $d = body();
        $s = $db->prepare("UPDATE productos SET
            nombre=COALESCE(:n,nombre), descripcion=COALESCE(:desc,descripcion),
            categoria_id=COALESCE(:cat,categoria_id), precio=COALESCE(:p,precio),
            precio_oferta=:po, stock=COALESCE(:st,stock), talles=COALESCE(:ta,talles),
            colores=COALESCE(:co,colores), imagen_url=COALESCE(:img,imagen_url),
            imagen2_url=COALESCE(:img2,imagen2_url), imagen3_url=COALESCE(:img3,imagen3_url),
            destacado=COALESCE(:dest,destacado), nuevo=COALESCE(:nvo,nuevo), activo=COALESCE(:act,activo)
            WHERE id=:id");
        $s->execute([
            ':n'=>$d['nombre']??null,':desc'=>$d['descripcion']??null,
            ':cat'=>isset($d['categoria_id'])?(int)$d['categoria_id']:null,
            ':p'=>isset($d['precio'])?(float)$d['precio']:null,
            ':po'=>isset($d['precio_oferta']) && $d['precio_oferta']!=='' ?(float)$d['precio_oferta']:null,
            ':st'=>isset($d['stock'])?(int)$d['stock']:null,':ta'=>$d['talles']??null,':co'=>$d['colores']??null,
            ':img'=>$d['imagen_url']??null,':img2'=>$d['imagen2_url']??null,':img3'=>$d['imagen3_url']??null,
            ':dest'=>isset($d['destacado'])?(int)$d['destacado']:null,
            ':nvo'=>isset($d['nuevo'])?(int)$d['nuevo']:null,
            ':act'=>isset($d['activo'])?(int)$d['activo']:null,':id'=>$id,
        ]);
        out(true, 'Producto actualizado');
        return;
    }

    if ($accion === 'eliminar' && $method === 'DELETE' && $id) {
        requireAdmin();
        $s = $db->prepare("DELETE FROM productos WHERE id=?");
        $s->execute([$id]);
        $s->rowCount() ? out(true,'Producto eliminado') : out(false,'No encontrado',null,404);
        return;
    }

    out(false, 'Acción no válida', null, 400);
}

// ════════════════════════════════════════════════════════════
//  CATEGORIAS
// ════════════════════════════════════════════════════════════
function routeCategorias(string $accion, ?int $id, string $method): void {
    $db = db();
    if ($accion === 'listar') {
        $s = $db->query("SELECT c.*, COUNT(p.id) AS total_productos
                          FROM categorias c
                          LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo = 1
                          WHERE c.activa = 1 GROUP BY c.id ORDER BY c.nombre");
        out(true, '', $s->fetchAll()); return;
    }
    if ($accion === 'crear' && $method === 'POST') {
        requireAdmin(); $d = body();
        if (empty($d['nombre'])) { out(false,'nombre es obligatorio',null,422); return; }
        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-',iconv('UTF-8','ASCII//TRANSLIT',$d['nombre'])));
        $s = $db->prepare("INSERT INTO categorias (nombre,slug,icono) VALUES(?,?,?)");
        $s->execute([trim($d['nombre']),$slug,$d['icono']??'👗']);
        out(true,'Categoría creada',['id'=>$db->lastInsertId()],201); return;
    }
    if ($accion === 'eliminar' && $method === 'DELETE' && $id) {
        requireAdmin();
        $s = $db->prepare("DELETE FROM categorias WHERE id=?");
        $s->execute([$id]);
        out(true,'Categoría eliminada'); return;
    }
    out(false,'Acción no válida',null,400);
}

// ════════════════════════════════════════════════════════════
//  CLIENTES
// ════════════════════════════════════════════════════════════
function routeClientes(string $accion, ?int $id, string $method): void {
    requireAdmin();
    $db = db();
    if ($accion === 'listar') {
        $q = isset($_GET['buscar']) ? '%'.$_GET['buscar'].'%' : null;
        if ($q) {
            $s = $db->prepare("SELECT * FROM clientes WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ? ORDER BY apellido");
            $s->execute([$q,$q,$q]);
        } else {
            $s = $db->query("SELECT * FROM clientes ORDER BY apellido, nombre");
        }
        out(true, '', $s->fetchAll()); return;
    }
    if ($accion === 'uno' && $id) {
        $s = $db->prepare("SELECT * FROM clientes WHERE id=?"); $s->execute([$id]);
        $r = $s->fetch(); $r ? out(true,'',$r) : out(false,'No encontrado',null,404); return;
    }
    if ($accion === 'crear' && $method === 'POST') {
        $d = body();
        if (empty($d['nombre'])||empty($d['apellido'])||empty($d['email'])) { out(false,'Campos obligatorios faltantes',null,422); return; }
        $s = $db->prepare("INSERT INTO clientes (nombre,apellido,email,telefono,direccion,ciudad,provincia) VALUES(?,?,?,?,?,?,?)");
        $s->execute([trim($d['nombre']),trim($d['apellido']),strtolower(trim($d['email'])),$d['telefono']??null,$d['direccion']??null,$d['ciudad']??null,$d['provincia']??null]);
        out(true,'Cliente creado',['id'=>$db->lastInsertId()],201); return;
    }
    if ($accion === 'editar' && $method === 'PUT' && $id) {
        $d = body();
        $s = $db->prepare("UPDATE clientes SET nombre=COALESCE(?,nombre),apellido=COALESCE(?,apellido),email=COALESCE(?,email),telefono=COALESCE(?,telefono),ciudad=COALESCE(?,ciudad),provincia=COALESCE(?,provincia),activo=COALESCE(?,activo) WHERE id=?");
        $s->execute([$d['nombre']??null,$d['apellido']??null,isset($d['email'])?strtolower(trim($d['email'])):null,$d['telefono']??null,$d['ciudad']??null,$d['provincia']??null,isset($d['activo'])?(int)$d['activo']:null,$id]);
        out(true,'Cliente actualizado'); return;
    }
    if ($accion === 'eliminar' && $method === 'DELETE' && $id) {
        $s = $db->prepare("DELETE FROM clientes WHERE id=?"); $s->execute([$id]);
        out(true,'Cliente eliminado'); return;
    }
    out(false,'Acción no válida',null,400);
}

// ════════════════════════════════════════════════════════════
//  OPINIONES
// ════════════════════════════════════════════════════════════
function routeOpiniones(string $accion, ?int $id, string $method): void {
    $db = db();
    if ($accion === 'listar' && $method === 'GET') {
        $pid = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : null;
        $pendientes = !empty($_GET['pendientes']) && isAdmin();
        if ($pid) {
            $s = $db->prepare("SELECT * FROM opiniones WHERE producto_id=? AND aprobada=1 ORDER BY fecha DESC");
            $s->execute([$pid]);
        } elseif ($pendientes) {
            $s = $db->query("SELECT o.*, p.nombre AS producto_nombre FROM opiniones o JOIN productos p ON o.producto_id=p.id WHERE o.aprobada=0 ORDER BY o.fecha DESC");
        } else {
            requireAdmin();
            $s = $db->query("SELECT o.*, p.nombre AS producto_nombre FROM opiniones o JOIN productos p ON o.producto_id=p.id ORDER BY o.fecha DESC");
        }
        out(true, '', $s->fetchAll()); return;
    }
    if ($accion === 'crear' && $method === 'POST') {
        $d = body();
        if (empty($d['producto_id'])||empty($d['cliente_nombre'])||empty($d['comentario'])||empty($d['estrellas']))
            { out(false,'Campos obligatorios faltantes',null,422); return; }
        if ($d['estrellas'] < 1 || $d['estrellas'] > 5) { out(false,'Estrellas debe ser 1-5',null,422); return; }
        $s = $db->prepare("INSERT INTO opiniones (producto_id,cliente_nombre,cliente_email,estrellas,comentario,aprobada) VALUES(?,?,?,?,?,0)");
        $s->execute([(int)$d['producto_id'],trim($d['cliente_nombre']),$d['cliente_email']??null,(int)$d['estrellas'],trim($d['comentario'])]);
        out(true,'Opinión enviada, pendiente de aprobación',['id'=>$db->lastInsertId()],201); return;
    }
    if ($accion === 'aprobar' && $method === 'PUT' && $id) {
        requireAdmin();
        $s = $db->prepare("UPDATE opiniones SET aprobada=1 WHERE id=?"); $s->execute([$id]);
        out(true,'Opinión aprobada'); return;
    }
    if ($accion === 'rechazar' && $method === 'PUT' && $id) {
        requireAdmin();
        $s = $db->prepare("UPDATE opiniones SET aprobada=0 WHERE id=?"); $s->execute([$id]);
        out(true,'Opinión rechazada'); return;
    }
    if ($accion === 'eliminar' && $method === 'DELETE' && $id) {
        requireAdmin();
        $s = $db->prepare("DELETE FROM opiniones WHERE id=?"); $s->execute([$id]);
        out(true,'Opinión eliminada'); return;
    }
    out(false,'Acción no válida',null,400);
}

// ════════════════════════════════════════════════════════════
//  COMPRAS
// ════════════════════════════════════════════════════════════
function routeCompras(string $accion, ?int $id, string $method): void {
    $db = db();

    if ($accion === 'crear' && $method === 'POST') {
        $d = body();
        if (empty($d['cliente_nombre'])||empty($d['cliente_email'])||empty($d['items']))
            { out(false,'Datos incompletos',null,422); return; }
        $items = $d['items'];
        if (!is_array($items)||!count($items)) { out(false,'El carrito está vacío',null,422); return; }
        $total = 0;
        foreach ($items as $item) {
            $s = $db->prepare("SELECT precio, precio_oferta, stock FROM productos WHERE id=? AND activo=1");
            $s->execute([(int)$item['producto_id']]);
            $p = $s->fetch();
            if (!$p) { out(false,"Producto #{$item['producto_id']} no encontrado",null,422); return; }
            if ($p['stock'] < (int)$item['cantidad']) { out(false,"Stock insuficiente para producto #{$item['producto_id']}",null,422); return; }
            $precio = $p['precio_oferta'] ?? $p['precio'];
            $total += $precio * (int)$item['cantidad'];
        }
        $codigo = 'VEL-' . strtoupper(substr(md5(uniqid()),0,8));
        $s = $db->prepare("INSERT INTO compras (codigo,cliente_nombre,cliente_email,cliente_tel,cliente_dir,total,metodo_pago,notas) VALUES(?,?,?,?,?,?,?,?)");
        $s->execute([$codigo,trim($d['cliente_nombre']),strtolower(trim($d['cliente_email'])),$d['cliente_tel']??null,$d['cliente_dir']??null,$total,$d['metodo_pago']??null,$d['notas']??null]);
        $compra_id = $db->lastInsertId();
        foreach ($items as $item) {
            $s2 = $db->prepare("SELECT nombre,precio,precio_oferta,stock FROM productos WHERE id=?");
            $s2->execute([(int)$item['producto_id']]);
            $p = $s2->fetch();
            $precio = $p['precio_oferta'] ?? $p['precio'];
            $si = $db->prepare("INSERT INTO compra_items (compra_id,producto_id,nombre,precio,cantidad,talle,color) VALUES(?,?,?,?,?,?,?)");
            $si->execute([$compra_id,(int)$item['producto_id'],$p['nombre'],$precio,(int)$item['cantidad'],$item['talle']??null,$item['color']??null]);
            $su = $db->prepare("UPDATE productos SET stock=stock-?, ventas=ventas+? WHERE id=?");
            $su->execute([(int)$item['cantidad'],(int)$item['cantidad'],(int)$item['producto_id']]);
        }
        // Registrar/actualizar cliente automáticamente
        if (!empty($d['cliente_email'])) {
            $sc = $db->prepare("SELECT id FROM clientes WHERE email=?");
            $sc->execute([strtolower(trim($d['cliente_email']))]);
            if (!$sc->fetch()) {
                $parts = explode(' ', trim($d['cliente_nombre']), 2);
                $nom = $parts[0]; $ape = $parts[1] ?? '';
                $ins = $db->prepare("INSERT IGNORE INTO clientes (nombre,apellido,email,telefono,direccion) VALUES(?,?,?,?,?)");
                $ins->execute([$nom,$ape,strtolower(trim($d['cliente_email'])),$d['cliente_tel']??null,$d['cliente_dir']??null]);
            }
        }
        out(true,'Compra registrada',['codigo'=>$codigo,'total'=>$total,'compra_id'=>$compra_id],201); return;
    }

    if ($accion === 'listar' && $method === 'GET') {
        requireAdmin();
        $estado = $_GET['estado'] ?? null;
        if ($estado) {
            $s = $db->prepare("SELECT * FROM compras WHERE estado=? ORDER BY fecha DESC"); $s->execute([$estado]);
        } else {
            $s = $db->query("SELECT * FROM compras ORDER BY fecha DESC");
        }
        out(true,'',$s->fetchAll()); return;
    }

    if ($accion === 'detalle' && $id) {
        requireAdmin();
        $s = $db->prepare("SELECT * FROM compras WHERE id=?"); $s->execute([$id]);
        $c = $s->fetch(); if (!$c) { out(false,'No encontrada',null,404); return; }
        $si = $db->prepare("SELECT ci.*, p.imagen_url FROM compra_items ci LEFT JOIN productos p ON ci.producto_id=p.id WHERE ci.compra_id=?");
        $si->execute([$id]);
        $c['items'] = $si->fetchAll();
        out(true,'',$c); return;
    }

    if ($accion === 'estado' && $method === 'PUT' && $id) {
        requireAdmin();
        $d = body();
        $validos = ['pendiente','confirmado','enviado','entregado','cancelado'];
        $estado = in_array($d['estado']??'',$validos) ? $d['estado'] : 'pendiente';
        $s = $db->prepare("UPDATE compras SET estado=? WHERE id=?");
        $s->execute([$estado,$id]);
        out(true,'Estado actualizado'); return;
    }

    out(false,'Acción no válida',null,400);
}

// ════════════════════════════════════════════════════════════
//  ESTADÍSTICAS (Dashboard)
// ════════════════════════════════════════════════════════════
function routeStats(string $accion, string $method): void {
    requireAdmin();
    $db = db();
    $prods     = $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
    $clientes  = $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $compras   = $db->query("SELECT COUNT(*) FROM compras")->fetchColumn();
    $pendOp    = $db->query("SELECT COUNT(*) FROM opiniones WHERE aprobada=0")->fetchColumn();
    $totalVentas = $db->query("SELECT COALESCE(SUM(total),0) FROM compras WHERE estado NOT IN ('cancelado')")->fetchColumn();
    $ultimasCompras = $db->query("SELECT codigo,cliente_nombre,total,estado,fecha FROM compras ORDER BY fecha DESC LIMIT 5")->fetchAll();
    out(true, '', [
        'productos'  => (int)$prods,
        'clientes'   => (int)$clientes,
        'compras'    => (int)$compras,
        'pendientes_opiniones' => (int)$pendOp,
        'total_ventas' => (float)$totalVentas,
        'ultimas_compras' => $ultimasCompras,
    ]);
}
?>
