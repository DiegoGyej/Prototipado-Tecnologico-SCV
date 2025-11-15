<?php
// public/api/count_productos.php
require_once __DIR__ . '/../../src/conexion.php';
require_once __DIR__ . '/../../src/autenticacion.php';

require_login_api();

try {
    $sql = "SELECT COUNT(*) AS total FROM Producto";
    $count = $pdo->query($sql)->fetchColumn();

    echo json_encode(['ok' => true, 'count' => (int)$count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
