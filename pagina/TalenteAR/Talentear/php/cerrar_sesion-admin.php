<?php
session_name("ADMIN_SESION");
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión (si existe)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir a la página de login de administración
header("Location: admin_login.php");
exit();
?>