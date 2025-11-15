<?php
// public/api/registrar_lote.php

define('DEV_MODE', true);

if (DEV_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';
    require_login_api(); // Exige haber iniciado sesion

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $raw = ob_get_clean();
        echo json_encode(['ok' => false, 'error' => 'Usar POST para registrar lotes.', 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Recibir datos
    $id_producto      = isset($_POST['idProducto']) ? (int)$_POST['idProducto'] : 0;
    $codigo_lote      = isset($_POST['codigoLote']) ? trim($_POST['codigoLote']) : '';
    $id_proveedor     = isset($_POST['idProveedor']) && $_POST['idProveedor'] !== '' ? (int)$_POST['idProveedor'] : null;
    $fecha_ingreso    = isset($_POST['fechaIngreso']) ? trim($_POST['fechaIngreso']) : date('Y-m-d');
    $fecha_vencimiento= isset($_POST['fechaVencimiento']) ? trim($_POST['fechaVencimiento']) : null;
    $cantidad         = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;

    // Validaciones
    $errors = [];
    if ($id_producto <= 0) $errors[] = 'Seleccione un producto.';
    if ($codigo_lote === '') $errors[] = 'Ingrese el código de lote.';
    if (!$fecha_ingreso) $errors[] = 'Ingrese fecha de ingreso.';
    if (!$fecha_vencimiento) $errors[] = 'Ingrese fecha de vencimiento.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_ingreso)) $errors[] = 'Fecha de ingreso inválida (YYYY-MM-DD).';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_vencimiento)) $errors[] = 'Fecha de vencimiento inválida (YYYY-MM-DD).';
    if ($cantidad < 0) $errors[] = 'Cantidad inválida.';

    // Comparar fechas
    if (empty($errors)) {
        $dIng = DateTime::createFromFormat('Y-m-d', $fecha_ingreso);
        $dVto = DateTime::createFromFormat('Y-m-d', $fecha_vencimiento);
        if (!$dIng || $dIng->format('Y-m-d') !== $fecha_ingreso) $errors[] = 'Fecha de ingreso inválida.';
        if (!$dVto || $dVto->format('Y-m-d') !== $fecha_vencimiento) $errors[] = 'Fecha de vencimiento inválida.';
        if ($dIng && $dVto && $dVto < $dIng) $errors[] = 'La fecha de vencimiento debe ser igual o posterior a la fecha de ingreso.';
    }

    if (!empty($errors)) {
        $raw = ob_get_clean();
        echo json_encode(['ok' => false, 'error' => implode(' ', $errors), 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar si producto existe
    $chk = $pdo->prepare("SELECT 1 FROM Producto WHERE idProducto = :id LIMIT 1");
    $chk->execute([':id' => $id_producto]);
    if (!$chk->fetch()) {
        $raw = ob_get_clean();
        echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.', 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar proveedor (si fue enviado)
    if ($id_proveedor !== null) {
        $chk2 = $pdo->prepare("SELECT 1 FROM Proveedor WHERE idProveedor = :id LIMIT 1");
        $chk2->execute([':id' => $id_proveedor]);
        if (!$chk2->fetch()) {
            $raw = ob_get_clean();
            echo json_encode(['ok' => false, 'error' => 'Proveedor no encontrado.', 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Prevenir duplicado (idProducto + codigoLote)
    $dup = $pdo->prepare("SELECT idLote FROM Lote WHERE idProducto = :pid AND codigoLote = :cl LIMIT 1");
    $dup->execute([':pid' => $id_producto, ':cl' => $codigo_lote]);
    if ($dup->fetch()) {
        $raw = ob_get_clean();
        echo json_encode(['ok' => false, 'error' => 'Ya existe un lote con ese código para ese producto.', 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Insertar (incluye estado por defecto; si no, lo seteamos en esta parte)
    $ins = $pdo->prepare("INSERT INTO Lote (idProducto, idProveedor, codigoLote, fechaIngreso, fechaVencimiento, cantidad, estado, fechaCreacion)
                          VALUES (:pid, :prov, :cl, :ing, :vto, :cant, 'activo', NOW())");
    $ins->execute([
        ':pid'  => $id_producto,
        ':prov' => $id_proveedor,
        ':cl'   => $codigo_lote,
        ':ing'  => $fecha_ingreso,
        ':vto'  => $fecha_vencimiento,
        ':cant' => $cantidad
    ]);

    $id_lote = (int)$pdo->lastInsertId();
    $raw = ob_get_clean();
    echo json_encode(['ok' => true, 'id_lote' => $id_lote, 'mensaje' => 'Lote creado correctamente.', 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    $raw = ob_get_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error DB', 'detalle' => $e->getMessage(), 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $t) {
    $raw = ob_get_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error inesperado', 'detalle' => $t->getMessage(), 'debug_output' => DEV_MODE ? $raw : null], JSON_UNESCAPED_UNICODE);
    exit;
}
