<?php
session_name("PARTNERS_SESION");
session_start();

// 1. Configuración de Conexión
$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    header("Location: postulaciones.php?msg=fail_db");
    exit();
}

// 2. Verificar Sesión y Método
if (!isset($_SESSION["email_agencia"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    mysqli_close($conn);
    // Asumiendo que esta es la ruta correcta
    header("Location: ../loginempresas.php"); 
    exit();
}

// 3. Obtener y validar datos
$notificacion_id = isset($_POST["notificacion_id"]) ? (int)$_POST["notificacion_id"] : 0;
$accion = isset($_POST["accion"]) ? $_POST["accion"] : ""; // 'aceptar' o 'rechazar'

if ($notificacion_id <= 0 || !in_array($accion, ['aceptar', 'rechazar'])) {
    mysqli_close($conn);
    header("Location: postulaciones.php?msg=invalid_data");
    exit();
}

// Determinar el nuevo estado
$nuevo_estado = ($accion === 'aceptar') ? 'aceptada' : 'rechazada';
$msg_redir = ($accion === 'aceptar') ? 'success_acept' : 'success_reject';

// 4. Obtener id_agencia para seguridad
$email_agencia = $_SESSION["email_agencia"];
$id_agencia = 0;

$query_agencia = "SELECT id_agencia FROM agencias WHERE email_agencia = ?";
$stmt_agencia = mysqli_prepare($conn, $query_agencia);
if ($stmt_agencia) {
    mysqli_stmt_bind_param($stmt_agencia, "s", $email_agencia);
    mysqli_stmt_execute($stmt_agencia);
    $result_agencia = mysqli_stmt_get_result($stmt_agencia);
    if ($result_agencia && $agencia_data = mysqli_fetch_assoc($result_agencia)) {
        $id_agencia = (int)$agencia_data["id_agencia"];
    }
    mysqli_stmt_close($stmt_agencia);
}

if ($id_agencia === 0) {
    mysqli_close($conn);
    header("Location: ../loginempresas.php");
    exit();
}


// 5. Ejecutar la Actualización en la Base de Datos
// ✅ CORRECCIÓN: Se agrega 'leida = 0' para forzar la aparición del "numerito" en el lado del Talento.
$updateQ = "
    UPDATE notificaciones 
    SET estado = ?, fecha_gestion = NOW(), leida = 0 
    WHERE notificacion_id = ? AND agencia_id = ? AND estado = 'pendiente'
";

$stmt_update = mysqli_prepare($conn, $updateQ);

if ($stmt_update) {
    // La 's' es para el string del estado ('aceptada'/'rechazada')
    // Las 'ii' son para los integers (notificacion_id y agencia_id)
    mysqli_stmt_bind_param($stmt_update, "sii", $nuevo_estado, $notificacion_id, $id_agencia); 
    $success = mysqli_stmt_execute($stmt_update);
    
    if ($success && mysqli_stmt_affected_rows($stmt_update) > 0) {
        // Éxito: Redirigir con mensaje de éxito
        mysqli_stmt_close($stmt_update);
        mysqli_close($conn);
        header("Location: postulaciones.php?msg=" . $msg_redir);
        exit();
    } else {
        // Falla: No se encontró la notificación o ya fue gestionada
        mysqli_stmt_close($stmt_update);
        mysqli_close($conn);
        header("Location: postulaciones.php?msg=fail_update");
        exit();
    }
} else {
    // Error al preparar la consulta
    mysqli_close($conn);
    header("Location: postulaciones.php?msg=fail_prepare");
    exit();
}
?>