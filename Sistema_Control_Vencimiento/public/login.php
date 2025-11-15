<?php
require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $err = 'Completar usuario y contraseña.';
    } else {
        if (login_usuario($usuario, $password, $pdo)) {
            header('Location: inicio.php');
            exit;
        } else {
            // Si la función dejó un mensaje específico en sesión, lo usamos
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!empty($_SESSION['login_error'])) {
                $err = $_SESSION['login_error'];
                // limpiar para no mostrarlo de nuevo en la próxima petición
                unset($_SESSION['login_error']);
            } else {
                $err = 'Credenciales incorrectas.';
            }
        }

    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ingresar - SCV</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="wrap">
        <div class="card" role="main">
            <div class="brand">
                <div class="logo-sm">SCV</div>
                <div class="brand-info">
                    <h1>Sistema Control Vencimientos</h1>
                    <p>Iniciar sesión</p>
                </div>
            </div>

            <?php if ($err): ?>
                <div class="error-box"><?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <form method="post">
                <label for="usuario">Correo o nombre</label>
                <input id="usuario" name="usuario" type="text" required value="<?= isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : '' ?>">

                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" required>

                <button class="btn-login" type="submit">Ingresar</button>
            </form>

            <div class="help-text">
                Usuario demo: <strong>admin@demo.com</strong><br>
                ¿Olvidaste tu contraseña? Contacta al administrador.
            </div>
        </div>
    </div>
</body>
</html>
