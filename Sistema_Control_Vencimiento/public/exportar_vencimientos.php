<?php
// public/exportar_vencimientos.php
require_once __DIR__ . '/../src/autenticacion.php';
require_login_pagina(); // se necesita sesión activa
require_once __DIR__ . '/../src/conexion.php';
require_rol(1);

try {
    // Cabeceras para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vencimientos_' . date('Ymd_His') . '.csv"');

    // Abrir salida
    $salida = fopen('php://output', 'w');

    // Escribir BOM UTF-8 para compatibilidad con Excel
    fwrite($salida, "\xEF\xBB\xBF");

    // Encabezado de columnas
    fputcsv($salida, [
        'ID Lote',
        'Código Lote',
        'Producto',
        'Fecha Ingreso',
        'Fecha Vencimiento',
        'Días Restantes',
        'Estado Alerta',
        'Mensaje Alerta'
    ]);

    // Consulta: obtenemos la última alerta (si existe) por lote para evitar duplicados
    $sql = "
        SELECT
            l.idLote             AS id_lote,
            l.codigoLote         AS codigo_lote,
            p.nombre             AS producto,
            l.fechaIngreso       AS fecha_ingreso,
            l.fechaVencimiento   AS fecha_vencimiento,
            DATEDIFF(l.fechaVencimiento, CURDATE()) AS dias_restantes,
            COALESCE(a.estado, 'sin alerta')          AS estado_alerta,
            COALESCE(a.mensaje, '')                   AS mensaje_alerta
        FROM Lote l
        JOIN Producto p ON l.idProducto = p.idProducto
        LEFT JOIN Alerta a
            ON a.idLote = l.idLote
            AND a.idAlerta = (
                SELECT MAX(idAlerta) FROM Alerta aa WHERE aa.idLote = l.idLote
            )
        WHERE l.fechaVencimiento >= CURDATE()
        ORDER BY l.fechaVencimiento ASC
    ";

    $stmt = $pdo->query($sql);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Escribir filas
    foreach ($resultados as $fila) {
        // Asegurar valores
        fputcsv($salida, [
            $fila['id_lote'] ?? '',
            $fila['codigo_lote'] ?? '',
            $fila['producto'] ?? '',
            $fila['fecha_ingreso'] ?? '',
            $fila['fecha_vencimiento'] ?? '',
            $fila['dias_restantes'] ?? '',
            $fila['estado_alerta'] ?? '',
            $fila['mensaje_alerta'] ?? ''
        ]);
    }

    fclose($salida);
    exit;

} catch (Throwable $e) {
    // En caso de error, limpiar buffer y devolver error legible
    if (ob_get_length()) ob_end_clean();
    http_response_code(500);
    echo "Error al exportar: " . $e->getMessage();
    exit;
}
