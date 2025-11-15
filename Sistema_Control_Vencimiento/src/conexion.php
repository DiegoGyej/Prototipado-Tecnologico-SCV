<?php
// src/conexion.php
// Crea $pdo para uso en la aplicación — no imprime nada.

$config = require __DIR__ . '/configuracion.php';

try {
    $host = $config['db_host'] ?? '127.0.0.1';
    $port = !empty($config['db_port']) ? $config['db_port'] : '3306';
    $db   = $config['db_name'] ?? '';
    $charset = $config['db_charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Conexión establecida; no hacemos echo para no contaminar salidas.
} catch (PDOException $e) {
    // Logueamos el error para debug (no exponer al usuario)
    error_log('[DB] Connection error: ' . $e->getMessage());

    // Si estamos en CLI mostramos el error para el dev
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Error de conexión a la base de datos: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    // Si la petición espera JSON (por ejemplo un endpoint API que ya estableció Content-Type),
    // no forzamos headers aquí; devolvemos respuesta genérica para evitar mezclar formatos.
    // Decidimos mostrar mensaje amigable y terminar ejecución:
    http_response_code(500);
    // Mostrar mensaje simple (sin detalle técnico) para páginas web
    echo '<h1>Error de servidor</h1><p>No es posible conectar con la base de datos. Consulte los logs.</p>';
    // Alternativa: die('Error de conexión (ver logs).');
    exit;
}
