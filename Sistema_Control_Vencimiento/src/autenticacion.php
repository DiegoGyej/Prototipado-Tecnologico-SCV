<?php
// src/autenticacion.php
// Funciones de autenticación y helpers de sesión.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * LOGIN
 */
function login_usuario($correo_o_nombre, $password, $pdo = null): bool {

    if ($pdo === null) {
        require_once __DIR__ . '/conexion.php';
        if (!isset($pdo)) return false;
    }

    $sql = "SELECT idUsuario, idRol, nombre, correo, contrasena, activo
            FROM Usuario
            WHERE (correo = :u OR nombre = :n)
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $correo_o_nombre, ':n' => $correo_o_nombre]);
    $user = $stmt->fetch();

    if (!$user) return false;
    if ((int)$user['activo'] !== 1) return false;

    // -- Validación de complejidad de contraseña --
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/";
    if (!preg_match($pattern, $password)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['login_error'] = 'La contraseña debe contener al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.';
        return false;
    }

    if (!password_verify($password, $user['contrasena'])) {
        return false;
    }

    // Rehash si corresponde
    if (password_needs_rehash($user['contrasena'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $upd = $pdo->prepare("UPDATE Usuario SET contrasena = :h WHERE idUsuario = :id");
            $upd->execute([':h' => $newHash, ':id' => $user['idUsuario']]);
        } catch (Exception $e) {
            error_log('No se pudo rehash contraseña: ' . $e->getMessage());
        }
    }

    // Guardar usuario en sesión
    $_SESSION['usuario'] = [
        'idUsuario' => (int)$user['idUsuario'],
        'idRol'     => (int)$user['idRol'],
        'nombre'    => $user['nombre'],
        'correo'    => $user['correo']
    ];

    session_regenerate_id(true);
    return true;
}


/**
 * LOGIN requerido (API)
 */
function require_login_api() {
    if (empty($_SESSION['usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * LOGIN requerido (página)
 */
function require_login_pagina($redirect = 'login.php') {
    if (empty($_SESSION['usuario'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * LOGOUT
 */
function logout_usuario() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

/**
 * Usuario actual
 */
function usuario_actual() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * ¿Usuario tiene rol por NOMBRE?
 */
function has_rol(string $rolNombre, $pdo = null): bool {
    $usr = usuario_actual();
    if (!$usr) return false;

    if ($pdo === null) {
        require_once __DIR__ . '/conexion.php';
        if (!isset($pdo)) return false;
    }

    $stmt = $pdo->prepare("SELECT nombre FROM Rol WHERE idRol = :id LIMIT 1");
    $stmt->execute([':id' => $usr['idRol']]);
    $r = $stmt->fetch();

    return $r && mb_strtolower($r['nombre']) === mb_strtolower($rolNombre);
}

/**
 * ¿Usuario tiene alguno de estos roles? (idRol o nombre)
 */
function usuario_tiene_rol($pdo = null, $roles) {
    $u = $_SESSION['usuario'] ?? null;
    if (!$u) return false;

    if (!is_array($roles)) $roles = [$roles];

    // Comparación por idRol
    foreach ($roles as $r) {
        if (is_int($r) && isset($u['idRol']) && (int)$u['idRol'] === $r) {
            return true;
        }
    }

    // Comparación por nombre (solo si hay nombres)
    $nombres = array_filter($roles, 'is_string');
    if (!empty($nombres)) {
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("SELECT nombre FROM Rol WHERE idRol = :id LIMIT 1");
        $stmt->execute([':id' => $u['idRol']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && in_array($row['nombre'], $nombres, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Requiere rol (idRol o array de idRol)
 */
function require_rol($roles, $isApi = false) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $u = $_SESSION['usuario'] ?? null;

    if (!$u) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }

    if (!is_array($roles)) $roles = [$roles];

    $allowed = false;
    foreach ($roles as $r) {
        if ((int)$u['idRol'] === (int)$r) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Rol insuficiente.']);
            exit;
        }

        header('Location: inicio.php');
        exit;
    }
}

