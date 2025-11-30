<?php
session_name("TALENTEAR_SESION");
session_start();
$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    http_response_code(500);
    exit("Error de conexión");
}

if (!isset($_SESSION["email"])) {
    http_response_code(401);
    exit("No autorizado");
}

$email = $_SESSION["email"];
$q = "SELECT id_talento FROM talentos WHERE email = '$email'";
$r = mysqli_query($conn, $q);
if (!$r || mysqli_num_rows($r) === 0) exit("Usuario no encontrado");
$user = mysqli_fetch_assoc($r);
$usuario_id = $user['id_talento'];

// Marcar notificaciones como leídas
$update = "
UPDATE notificaciones n
JOIN curriculums c ON n.curriculum_id = c.curriculum_id
SET n.leida = 1
WHERE c.usuario_id = '$usuario_id'
";
mysqli_query($conn, $update);

echo "ok";
