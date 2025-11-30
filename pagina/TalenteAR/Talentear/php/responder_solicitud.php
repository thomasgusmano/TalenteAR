<?php
session_name("TALENTEAR_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

if (!isset($_SESSION["email"])) {
    header("Location: ../login.php");
    exit;
}

$email = $_SESSION["email"];

// Obtener id_talento desde la base usando email
$query = "SELECT id_talento FROM talentos WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $id_talento = $row['id_talento'];
} else {
    header("Location: ../login.php");
    exit;
}

// Procesar la respuesta
$notificacion_id = $_POST['id_solicitud'] ?? null;
$respuesta = $_POST['respuesta'] ?? null;

if ($notificacion_id && in_array($respuesta, ['aceptada', 'rechazada'])) {
    $notificacion_id = intval($notificacion_id);
    $respuesta = mysqli_real_escape_string($conn, $respuesta);

    $update = "
        UPDATE notificaciones
        SET estado = '$respuesta', fecha_respuesta = NOW()
        WHERE notificacion_id = '$notificacion_id'
    ";
    mysqli_query($conn, $update);
}

header("Location: solicitudes.php");
exit;

?>
