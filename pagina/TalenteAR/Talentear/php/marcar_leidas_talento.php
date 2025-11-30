<?php
session_name("TALENTEAR_SESION");
session_start();

// 1. Conexión a la Base de Datos
$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    http_response_code(500); // Error de servidor
    exit();
}

// 2. Validar la Sesión del Talento
if (!isset($_SESSION['email'])) {
    http_response_code(401); // No autorizado
    mysqli_close($conn);
    exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION["email"]);

// 3. Obtener el ID del Talento logueado
$query_talento_id = "SELECT id_talento FROM talentos WHERE email = '$email'";
$result_talento_id = mysqli_query($conn, $query_talento_id);

if (!$result_talento_id || mysqli_num_rows($result_talento_id) === 0) {
    http_response_code(404); // Talento no encontrado
    mysqli_close($conn);
    exit();
}
$id_talento = (int)mysqli_fetch_assoc($result_talento_id)['id_talento'];

// 4. Marcar como LEÍDAS todas las notificaciones pendientes de este Talento
if ($id_talento > 0) {
    // Usamos JOIN para asegurar que solo se actualizan las notificaciones vinculadas al ID del Talento
    $query = "
        UPDATE notificaciones n
        JOIN curriculums c ON n.curriculum_id = c.curriculum_id
        SET n.leida = 1
        WHERE c.usuario_id = $id_talento AND n.leida = 0
    ";

    if (mysqli_query($conn, $query)) {
        http_response_code(200); // Éxito
    } else {
        http_response_code(500); // Error de actualización
    }
} else {
    http_response_code(400); // Petición inválida
}

mysqli_close($conn);
?>