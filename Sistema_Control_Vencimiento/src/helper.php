<?php
// src/helpers.php

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Calcula los días restantes entre hoy y una fecha
 */
function dias_restantes($fechaVencimiento) {
    $hoy = new DateTime();
    $vto = new DateTime($fechaVencimiento);
    $diff = $hoy->diff($vto);
    return (int)$diff->format('%r%a'); // devuelve negativo si ya venció
}

/**
 * Formatea fecha YYYY-MM-DD → DD/MM/YYYY
 */
function fecha_local($fecha) {
    if (!$fecha) return '';
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

/**
 * Devuelve color según días restantes (para interfaz)
 */
function color_alerta($dias) {
    if ($dias <= 0) return '#ff5c5c';      // rojo (vencido)
    if ($dias <= 7) return '#ff9b40';      // naranja
    if ($dias <= 15) return '#ffd740';     // amarillo
    return '#a0e8af';                      // verde
}
