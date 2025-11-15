<?php
// src/configuracion.php
return [
    // Datos de conexión MySQL
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'sistema_vencimiento',
    'db_user' => '',
    'db_pass' => '', // pon tu contraseña si corresponde
    'db_charset' => 'utf8mb4',

    // Ajustes de la aplicación
    'umbral_por_defecto_dias' => 15, // valor por defecto para endpoints que lo requieran
];

// ACTIVAR AL FINAL PARA PROBAR

// SMTP (para enviar correos). Si no se usa SMTP dejalos vacíos y el sistema usará mail()
//'smtp_host' => '',
//'smtp_port' => ,
//'smtp_user' => '',
//'smtp_pass' => '',
//'smtp_secure' => 'tls', // 'tls' o 'ssl'
//'from_email' => '',
//'from_name' => '',
