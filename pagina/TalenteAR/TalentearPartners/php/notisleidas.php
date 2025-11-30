<?php
session_name("PARTNERS_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit();
}

// 1. Validar sesión de AGENCIA
if (!isset($_SESSION["email_agencia"])) {
    http_response_code(401);
    echo "No autorizado.";
    exit();
}

$email_agencia = $_SESSION["email_agencia"];

// 2. Obtener el id_agencia
$q = "SELECT id_agencia FROM agencias WHERE email_agencia = '" . mysqli_real_escape_string($conn, $email_agencia) . "' LIMIT 1";
$r = mysqli_query($conn, $q);
if (!$r || mysqli_num_rows($r) === 0) {
    http_response_code(404);
    echo "Agencia no encontrada.";
    exit();
}
$row = mysqli_fetch_assoc($r);
$id_agencia = (int)$row['id_agencia'];

// 3. ✅ CORRECCIÓN FINAL: Actualizar TODAS las notificaciones NO LEÍDAS para esta agencia.
// Esto incluye Postulaciones (pendiente) y Respuestas (aceptada/rechazada).
$updateQuery = "
    UPDATE notificaciones 
    SET leida = 1 
    WHERE agencia_id = $id_agencia 
    AND leida = 0
    /* 🔥 FIX: SE ELIMINA LA RESTRICCIÓN 'AND estado != 'pendiente'' */
";

if (mysqli_query($conn, $updateQuery)) {
    echo "Notificaciones marcadas como leídas.";
} else {
    http_response_code(500);
    echo "Error al actualizar notificaciones: " . mysqli_error($conn);
}

mysqli_close($conn);
?>