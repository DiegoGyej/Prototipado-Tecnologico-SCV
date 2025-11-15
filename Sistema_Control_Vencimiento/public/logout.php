<?php
// public/logout.php
require_once __DIR__ . '/../src/autenticacion.php';
logout_usuario();
header('Location: login.php');
exit;
