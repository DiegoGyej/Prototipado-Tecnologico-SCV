<?php
// tools/crear_usuario.php
// Interfaz web para crear usuarios (solo Administrador).
// Ubicar en tools/crear_usuario.php (el archivo debe eliminarse o protegerse en producción).

require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Requerir login (redirige a /public/login.php si no está logueado)
require_login_pagina('/public/login.php');

// Solo administradores (idRol = 1) pueden usar esta herramienta
$usuario = usuario_actual();
if (!$usuario || !isset($usuario['rol']) || (int)$usuario['rol'] !== 1) {
    http_response_code(403);
    echo "<h1>403 - Acceso denegado</h1><p>Se requiere rol Administrador para usar esta herramienta.</p>";
    exit;
}

// CSRF básico
if (empty($_SESSION['tools_csrf'])) $_SESSION['tools_csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['tools_csrf'];

$errors = [];
$mensaje = '';

// Cargar roles disponibles desde BD para el select
try {
    $rolesStmt = $pdo->query("SELECT idRol, nombre FROM Rol ORDER BY idRol ASC");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $roles = [];
    $errors[] = "No se pudieron cargar roles: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $errors[] = "Token CSRF inválido.";
    } else {
        // Recibir y sanitizar inputs
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $rol = isset($_POST['rol']) ? (int)$_POST['rol'] : 0;
        $activo = isset($_POST['activo']) && ($_POST['activo'] === '1' || $_POST['activo'] === 'on') ? 1 : 0;

        // Si el usuario quiere generar contraseña aleatoria
        if (!empty($_POST['generar_pass']) && $_POST['generar_pass'] === '1' && empty($contrasena)) {
            // Generar contraseña aleatoria segura
            $contrasena = bin2hex(random_bytes(4)); // 8 hex chars -> 8 chars
            $mensaje .= "Contraseña generada: <strong>{$contrasena}</strong>. ";
        }

        // Validaciones
        if ($nombre === '') $errors[] = 'Nombre requerido.';
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo válido requerido.';
        if ($contrasena === '') $errors[] = 'Contraseña requerida (o marca "Generar contraseña").';
        if ($rol <= 0) $errors[] = 'Seleccione un rol válido.';

        // Si no hay errores, intentar crear el usuario
        if (empty($errors)) {
            try {
                // Verificar duplicado por correo
                $chk = $pdo->prepare("SELECT idUsuario FROM Usuario WHERE correo = :correo LIMIT 1");
                $chk->execute([':correo' => $correo]);
                if ($chk->fetch()) {
                    $errors[] = 'Ya existe un usuario con ese correo.';
                } else {
                    // Hashear contraseña
                    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                    // Insertar usuario
                    $ins = $pdo->prepare("INSERT INTO Usuario (idRol, nombre, correo, contrasena, activo, fechaCreacion) VALUES (:rol, :nombre, :correo, :hash, :activo, NOW())");
                    $ins->execute([
                        ':rol' => $rol,
                        ':nombre' => $nombre,
                        ':correo' => $correo,
                        ':hash' => $hash,
                        ':activo' => $activo
                    ]);

                    $nuevoId = (int)$pdo->lastInsertId();
                    $mensaje .= "Usuario creado correctamente. ID: {$nuevoId}. Correo: {$correo}.";
                    // Opcional: mostrar contraseña generada (ya añadida arriba)
                    // Limpiar campos del form
                    $nombre = $correo = '';
                    $contrasena = '';
                    $rol = 0;
                    $activo = 1;
                    // Regenerar token CSRF
                    $_SESSION['tools_csrf'] = bin2hex(random_bytes(24));
                    $csrf = $_SESSION['tools_csrf'];
                }
            } catch (PDOException $ex) {
                $errors[] = 'Error DB: ' . $ex->getMessage();
            } catch (Throwable $t) {
                $errors[] = 'Error inesperado: ' . $t->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tools - Crear usuario</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f6f8;padding:20px}
    .card{max-width:720px;margin:0 auto;background:#fff;padding:18px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.06)}
    h1{margin:0 0 12px;color:#333}
    label{display:block;margin-top:10px;font-weight:600}
    input,select{width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;margin-top:6px}
    .row{display:flex;gap:12px}
    .row > div{flex:1}
    .actions{margin-top:14px;display:flex;gap:8px;align-items:center}
    .btn{padding:10px 14px;border-radius:8px;border:0;cursor:pointer;background:#5b4a76;color:#fff;font-weight:700}
    .btn-ghost{background:#f0f0f2;color:#333;border:1px solid #e0e0e0}
    .msg{margin-top:12px;padding:12px;border-radius:8px}
    .ok{background:#e7f7ee;color:#114b24;border:1px solid #c6eed4}
    .err{background:#fff2f2;color:#7a1515;border:1px solid #f0c6c6}
    .muted{color:#666;font-size:0.9rem}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
    th{background:#fafafa}
  </style>
</head>
<body>
  <div class="card">
    <h1>Herramienta: Crear usuario</h1>
    <p class="muted">Sesión: <strong><?= htmlspecialchars($usuario['nombre'] ?? $usuario['correo']) ?></strong> — Uso restringido a administradores.</p>

    <?php if (!empty($mensaje)): ?>
      <div class="msg ok"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="msg err">
        <strong>Errores:</strong>
        <ul>
          <?php foreach ($errors as $er): ?>
            <li><?= htmlspecialchars($er) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label for="nombre">Nombre completo</label>
      <input id="nombre" name="nombre" type="text" value="<?= isset($nombre) ? htmlspecialchars($nombre) : '' ?>" required>

      <label for="correo">Correo electrónico</label>
      <input id="correo" name="correo" type="email" value="<?= isset($correo) ? htmlspecialchars($correo) : '' ?>" required>

      <div class="row">
        <div>
          <label for="contrasena">Contraseña (texto)</label>
          <input id="contrasena" name="contrasena" type="text" value="<?= isset($contrasena) ? htmlspecialchars($contrasena) : '' ?>" placeholder="Escribe una contraseña o genera una">
        </div>
        <div>
          <label>&nbsp;</label>
          <label style="display:block;font-weight:600">Generar</label>
          <div style="display:flex;gap:8px">
            <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="generar_pass" value="1"> Generar contraseña aleatoria</label>
          </div>
        </div>
      </div>

      <div class="row" style="margin-top:8px">
        <div>
          <label for="rol">Rol</label>
          <select id="rol" name="rol" required>
            <option value="0">-- seleccionar rol --</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['idRol'] ?>" <?= (isset($rol) && $rol == $r['idRol']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="activo">Activo</label>
          <select id="activo" name="activo">
            <option value="1" <?= (isset($activo) && $activo == 1) ? 'selected' : '' ?>>Si</option>
            <option value="0" <?= (isset($activo) && $activo == 0) ? 'selected' : '' ?>>No</option>
          </select>
        </div>
      </div>

      <div class="actions">
        <button class="btn" type="submit">Crear usuario</button>
        <a class="btn btn-ghost" href="/public/inicio.php">Volver al panel</a>
      </div>
    </form>

    <hr style="margin-top:18px">

    <h3>Roles disponibles</h3>
    <?php if (count($roles) === 0): ?>
      <p class="muted">No se encontraron roles en la base de datos.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>ID</th><th>Nombre</th></tr></thead>
        <tbody>
          <?php foreach ($roles as $r): ?>
            <tr><td><?= (int)$r['idRol'] ?></td><td><?= htmlspecialchars($r['nombre']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p class="muted" style="margin-top:12px">Al finalizar, elimina este archivo o muévelo fuera del directorio público. Mantener herramientas administrativas accesibles por web puede representar un riesgo.</p>
  </div>
</body>
</html>
