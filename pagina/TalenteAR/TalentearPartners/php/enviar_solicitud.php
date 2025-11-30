<?php
session_name("PARTNERS_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Si no hay sesión de empresa por email -> no logueado
if (!isset($_SESSION["email_agencia"])) {
    die("No estás logueado como empresa.");
}

$email_agencia = $_SESSION["email_agencia"];

// Obtener el id_agencia desde la tabla agencias
$q = "SELECT id_agencia FROM agencias WHERE email_agencia = '" . mysqli_real_escape_string($conn, $email_agencia) . "' LIMIT 1";
$r = mysqli_query($conn, $q);
if (!$r || mysqli_num_rows($r) === 0) {
    die("Empresa no encontrada.");
}
$row = mysqli_fetch_assoc($r);
$id_agencia = $row['id_agencia'];

// Recibir curriculum_id
$curriculum_id = isset($_POST['curriculum_id']) ? intval($_POST['curriculum_id']) : 0;
if ($curriculum_id <= 0) {
    header("Location: index-empresa.php");
    exit;
}

// Asegurarnos de que la tabla notificaciones exista (opcional si ya la creaste)
$createNotif = "
CREATE TABLE IF NOT EXISTS notificaciones (
  id_talento INT AUTO_INCREMENT PRIMARY KEY,
  curriculum_id INT NOT NULL,
  agencia_id INT NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  leida TINYINT(1) DEFAULT 0,
  estado ENUM('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
  fecha_respuesta DATETIME NULL,
  UNIQUE KEY unica_solicitud (curriculum_id, agencia_id),
  FOREIGN KEY (curriculum_id) REFERENCES curriculums(curriculum_id) ON DELETE CASCADE,
  FOREIGN KEY (agencia_id) REFERENCES agencias(id_agencia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($conn, $createNotif);

// Evitar duplicados (por si falla la constraint UNIQUE)
// ✅ Se cambió "SELECT id_talento" por "SELECT 1" para evitar error si no existe esa columna
$check = mysqli_query($conn, "SELECT 1 FROM notificaciones WHERE curriculum_id = $curriculum_id AND agencia_id = $id_agencia LIMIT 1");

if ($check && mysqli_num_rows($check) == 0) {
    $insert = "INSERT INTO notificaciones (curriculum_id, agencia_id, fecha, estado)
               VALUES ($curriculum_id, $id_agencia, NOW(), 'pendiente')";
    mysqli_query($conn, $insert);
}

// Volver a la página de candidatos (ajustá la ruta si hace falta)
header("Location: index-logged.php");
exit;
?>
