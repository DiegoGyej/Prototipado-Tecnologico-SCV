<?php
// public/api/registrar_producto.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';
    require_login_api();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'Usar POST para crear productos.']);
        exit;
    }

    // Recibir campos tal como vienen del formulario
    $codigo = isset($_POST['codigoProducto']) ? trim($_POST['codigoProducto']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $unidad = isset($_POST['unidad_medida']) ? trim($_POST['unidad_medida']) : null; // opcional
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1; // opcional
    $idCategoria = isset($_POST['idCategoria']) && $_POST['idCategoria'] !== '' ? (int)$_POST['idCategoria'] : null;

    // Validaciones mínimas
    $errors = [];
    if ($codigo === '') $errors[] = 'Código de producto requerido.';
    if ($nombre === '') $errors[] = 'Nombre de producto requerido.';

    if (!empty($errors)) {
        echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
        exit;
    }

    // Evitar duplicados por codigoProducto (nombre exacto de la columna en tu BD)
    $chk = $pdo->prepare("SELECT idProducto FROM Producto WHERE codigoProducto = :cod LIMIT 1");
    $chk->execute([':cod' => $codigo]);
    if ($chk->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Ya existe un producto con ese código.']);
        exit;
    }

    // Insertar (tener en cuenta: unidad/activo no están en la tabla producto por defecto, por eso no se inserta)
    $ins = $pdo->prepare("
        INSERT INTO Producto (idCategoria, codigoProducto, nombre, descripcion)
        VALUES (:idcat, :cod, :nom, :desc)
    ");
    $ins->execute([
        ':idcat' => $idCategoria,
        ':cod' => $codigo,
        ':nom' => $nombre,
        ':desc' => $descripcion
    ]);

    $nuevoId = (int)$pdo->lastInsertId();

    echo json_encode(['ok' => true, 'id_producto' => $nuevoId, 'mensaje' => 'Producto creado correctamente.'], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error DB', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error inesperado', 'detalle' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
