<?php
// public/index.php
require_once __DIR__ . '/../src/autenticacion.php';

if (usuario_actual()) {
    header('Location: inicio.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
