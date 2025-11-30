<?php
session_name("PARTNERS_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar a la base de datos.");
}

// ----------------------------------------------------
// --- 1. Validaciones Iniciales y Conexión ---
// ----------------------------------------------------
if (!isset($_SESSION["email_agencia"]) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: error.php?code=401");
    exit();
}

$email_agencia = $_SESSION["email_agencia"];
$curriculum_id = isset($_POST['curriculum_id']) ? (int)$_POST['curriculum_id'] : 0;

if ($curriculum_id === 0) {
    header("Location: index-logged.php?status=error&msg=no_cv_id");
    exit();
}

// ----------------------------------------------------
// --- 2. Obtener id_agencia ---
// ----------------------------------------------------
$query_agencia = "SELECT id_agencia FROM agencias WHERE email_agencia = '" . mysqli_real_escape_string($conn, $email_agencia) . "'";
$result_agencia = mysqli_query($conn, $query_agencia);

if (!$result_agencia || mysqli_num_rows($result_agencia) === 0) {
    mysqli_close($conn);
    header("Location: loginempresas.php");
    exit();
}
$id_agencia = (int)mysqli_fetch_assoc($result_agencia)['id_agencia'];

// ----------------------------------------------------
// --- 3. Verificar duplicados (estado 'invitacion') ---
// ----------------------------------------------------
$check_duplicate_query = "SELECT 1 FROM notificaciones 
                         WHERE curriculum_id = $curriculum_id 
                           AND agencia_id = $id_agencia 
                           AND estado = 'invitacion' 
                           LIMIT 1";
$check_duplicate_result = mysqli_query($conn, $check_duplicate_query);
if (mysqli_num_rows($check_duplicate_result) > 0) {
    mysqli_close($conn);
    header("Location: index-logged.php?status=error&msg=already_invited");
    exit();
}

// ----------------------------------------------------
// --- 4. Insertar la notificación (Invitación) ---
// ----------------------------------------------------
// 🔥 CORRECCIÓN: 'leida' es 0 (NO LEÍDA) para que active el contador del Talento.
// 'casting_id' es NULL porque es una invitación directa, no a un casting.
$insert_query = "INSERT INTO notificaciones (curriculum_id, agencia_id, fecha, estado, leida, casting_id) 
                 VALUES ($curriculum_id, $id_agencia, NOW(), 'invitacion', 0, NULL)"; 

if (mysqli_query($conn, $insert_query)) {
    // Éxito
    mysqli_close($conn);
    header("Location: index-logged.php?status=success&msg=invitacion_enviada"); 
    exit();
} else {
    // Error
    error_log("Error al enviar invitación: " . mysqli_error($conn));
    mysqli_close($conn);
    header("Location: index-logged.php?status=error&msg=db_insert_error");
    exit();
}
?>