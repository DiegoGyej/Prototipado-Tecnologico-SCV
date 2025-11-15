<?php
require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';

$email = 'admin@demo.com';
$pass = 'admin123'; // ajustá si tu hash corresponde a otra contraseña

if (login_usuario($email, $pass, $pdo)) {
    echo "Login OK\n";
    var_dump(usuario_actual());
} else {
    echo "Login FAILED\n";
}
