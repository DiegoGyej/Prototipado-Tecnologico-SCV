<?php
// tools/convertir_contraseña.php
// Versión WEB: convierte contraseñas en texto plano a password_hash().
// Requiere sesión y rol de administrador (idRol = 1).

require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';

// Proteger la página: exigir login
if (session_status() === PHP_SESSION_NONE) session_start();
require_login_pagina('/public/login.php');

// Comprobar rol (1 = Administrador según seed)
$usuario = usuario_actual();
if (!$usuario || !isset($usuario['rol']) || (int)$usuario['rol'] !== 1) {
    http_response_code(403);
    echo "<h1>403 - Acceso denegado</h1><p>Se requiere ser administrador para ejecutar esta herramienta.</p>";
    exit;
}

// CSRF simple
if (empty($_SESSION['tools_csrf'])) {
    $_SESSION['tools_csrf'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['tools_csrf'];

$mensaje = '';
$errores = [];

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $errores[] = 'Token CSRF inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            // Seleccionamos las filas sospechosas de no estar hasheadas
            // Consideramos hashed si comienzan con $2y$, $2b$, $2a$ o $argon2
            $sqlCheck = "SELECT idUsuario, nombre, correo, contrasena
                         FROM Usuario
                         WHERE contrasena NOT LIKE '$2y$%' 
                           AND contrasena NOT LIKE '$2b$%'
                           AND contrasena NOT LIKE '$2a$%'
                           AND contrasena NOT LIKE '$argon2%'";
            $stmt = $pdo->query($sqlCheck);
            $usuarios_no_hash = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($action === 'convert_all') {
                if (empty($usuarios_no_hash)) {
                    $mensaje = "No se encontraron contraseñas a convertir.";
                } else {
                    $pdo->beginTransaction();
                    $upd = $pdo->prepare("UPDATE Usuario SET contrasena = :hash WHERE idUsuario = :id");
                    $count = 0;
                    foreach ($usuarios_no_hash as $u) {
                        $plain = $u['contrasena'];
                        // si por alguna razón está vacío, saltar
                        if ($plain === null || trim($plain) === '') continue;
                        $hash = password_hash($plain, PASSWORD_DEFAULT);
                        $upd->execute([':hash' => $hash, ':id' => $u['idUsuario']]);
                        $count += $upd->rowCount();
                    }
                    $pdo->commit();
                    $mensaje = "Conversión completa. Se actualizaron {$count} usuario(s).";
                }
            } elseif ($action === 'convert_one' && !empty($_POST['idUsuario'])) {
                $idUsuario = (int)$_POST['idUsuario'];
                // Buscar usuario y su contrasena actual
                $s = $pdo->prepare("SELECT idUsuario, correo, contrasena FROM Usuario WHERE idUsuario = :id LIMIT 1");
                $s->execute([':id' => $idUsuario]);
                $u = $s->fetch(PDO::FETCH_ASSOC);
                if (!$u) {
                    $errores[] = "Usuario no encontrado (id: $idUsuario).";
                } else {
                    $plain = $u['contrasena'];
                    if ($plain === null || trim($plain) === '') {
                        $errores[] = "La contraseña del usuario está vacía. No se realizó conversión.";
                    } else {
                        $hash = password_hash($plain, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare("UPDATE Usuario SET contrasena = :hash WHERE idUsuario = :id");
                        $upd->execute([':hash' => $hash, ':id' => $idUsuario]);
                        $mensaje = "Usuario (id {$idUsuario}) actualizado correctamente.";
                    }
                }
            } else {
                $errores[] = "Acción no reconocida.";
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = "Error DB: " . $ex->getMessage();
        } catch (Throwable $t) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = "Error inesperado: " . $t->getMessage();
        }
    }

    // Regenerar token (opcional)
    $_SESSION['tools_csrf'] = bin2hex(random_bytes(24));
    $csrf = $_SESSION['tools_csrf'];
}

// Leer lista de usuarios con contraseñas no hasheadas (para mostrar en UI)
try {
    $sqlCheck = "SELECT idUsuario, nombre, correo, contrasena
                 FROM Usuario
                 WHERE contrasena NOT LIKE '$2y$%' 
                   AND contrasena NOT LIKE '$2b$%'
                   AND contrasena NOT LIKE '$2a$%'
                   AND contrasena NOT LIKE '$argon2%'
                 ORDER BY idUsuario ASC";
    $stmt = $pdo->query($sqlCheck);
    $usuarios_no_hash = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $usuarios_no_hash = [];
    $errores[] = "Error cargando usuarios: " . $ex->getMessage();
}

// HTML de salida (simple, limpio)
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tools - Convertir contraseñas</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f6f8;padding:20px}
    .card{max-width:980px;margin:0 auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.06)}
    h1{margin:0 0 12px;color:#333}
    pre{background:#f7f7f9;padding:12px;border-radius:8px;overflow:auto}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
    th{background:#fafafa}
    .btn{padding:8px 12px;border-radius:8px;border:0;cursor:pointer;background:#5b4a76;color:#fff;font-weight:700}
    .danger{background:#c82333}
    .muted{color:#666;font-size:0.9rem}
    .msg{padding:10px;border-radius:8px;margin-bottom:12px}
    .ok{background:#e7f7ee;color:#114b24;border:1px solid #c6eed4}
    .err{background:#fff2f2;color:#7a1515;border:1px solid #f0c6c6}
    form.inline{display:inline}
  </style>
</head>
<body>
  <div class="card">
    <h1>Herramienta: Convertir contraseñas a hash seguro</h1>
    <p class="muted">Usuario: <strong><?= htmlspecialchars($usuario['nombre'] ?? $usuario['correo']) ?></strong> — Solo administradores pueden usar esto.</p>

    <?php if ($mensaje): ?>
      <div class="msg ok"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
      <div class="msg err"><strong>Errores:</strong>
        <ul>
        <?php foreach ($errores as $er): ?>
          <li><?= htmlspecialchars(is_array($er) ? json_encode($er) : $er) ?></li>
        <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <p>Usuarios detectados con contraseñas que <em>parecen</em> no estar hasheadas: <strong><?= count($usuarios_no_hash) ?></strong></p>

    <?php if (count($usuarios_no_hash) > 0): ?>
      <form method="post" onsubmit="return confirm('Convertir todas las contraseñas detectadas en hashes seguros? Esta acción es irreversible.');" style="margin-bottom:12px">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="convert_all">
        <button class="btn danger" type="submit">Convertir todas (recomendado)</button>
      </form>

      <table>
        <thead>
          <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Contraseña (texto actual)</th><th>Acción</th></tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios_no_hash as $u): ?>
          <tr>
            <td><?= (int)$u['idUsuario'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>
            <td><pre style="margin:0;max-width:320px;white-space:pre-wrap;"><?= htmlspecialchars($u['contrasena']) ?></pre></td>
            <td>
              <form method="post" class="inline" onsubmit="return confirm('Convertir contraseña de este usuario?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="convert_one">
                <input type="hidden" name="idUsuario" value="<?= (int)$u['idUsuario'] ?>">
                <button class="btn" type="submit">Convertir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

    <?php else: ?>
      <p class="muted">No se encontraron contraseñas sospechosas de estar en texto plano.</p>
    <?php endif; ?>

    <hr style="margin-top:18px">

    <p class="muted">Cuando termines, elimina este archivo o restringe su acceso. Mantener herramientas administrativas accesibles por web puede ser un riesgo de seguridad si el servidor está expuesto.</p>
  </div>
</body>
</html>
