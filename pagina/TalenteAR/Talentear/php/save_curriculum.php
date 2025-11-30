<?php
session_name("TALENTEAR_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    // Manejo de error de conexión
    header("Location: error.php?code=500&msg=db_connect_error");
    exit();
}

// Validar sesión
if (!isset($_SESSION["email"])) {
    header("Location: error.php?code=401");
    exit();
}
$email = $_SESSION["email"];

// Obtener id_talento
$query = "SELECT id_talento FROM talentos WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: login.php");
    exit();
}
$row = mysqli_fetch_assoc($result);
$id_talento = (int)$row["id_talento"];

// 1. Obtener datos del formulario
$biografia = isset($_POST["biografia"]) ? trim($_POST["biografia"]) : '';
$educacion = isset($_POST["educacion"]) ? trim($_POST["educacion"]) : '';
$ciudad = isset($_POST["ciudad"]) ? trim($_POST["ciudad"]) : '';
$experiencia = isset($_POST["experiencia"]) ? trim($_POST["experiencia"]) : '';
$habilidades = isset($_POST["habilidades"]) ? trim($_POST["habilidades"]) : '';

// 2. Validar que los campos requeridos no estén vacíos
if (empty($biografia) || empty($educacion) || empty($ciudad) || empty($experiencia) || empty($habilidades)) {
    // Redirige al inicio con un mensaje de error si falta algún campo
    header("Location: index-logged.php?status=error&msg=missing_fields");
    exit();
}

// 3. Verificar si el CV ya existe
$query_check = "SELECT curriculum_id FROM curriculums WHERE usuario_id = $id_talento LIMIT 1";
$result_check = mysqli_query($conn, $query_check);
$curriculum_existe = mysqli_num_rows($result_check) > 0;

$success_message = '';
$redirect_url = 'index-logged.php';

if ($curriculum_existe) {
    // UPDATE (Actualizar CV)
    $row_cv = mysqli_fetch_assoc($result_check);
    $curriculum_id = $row_cv['curriculum_id'];
    
    $query_update = "UPDATE curriculums SET 
                     biografia = ?, educacion = ?, ciudad = ?, 
                     experiencia = ?, habilidades = ?
                     WHERE usuario_id = ? AND curriculum_id = ?";
    
    $stmt = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt, "sssssii", 
                            $biografia, $educacion, $ciudad, 
                            $experiencia, $habilidades, $id_talento, $curriculum_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = 'updated';
    } else {
        error_log("Error al actualizar CV: " . mysqli_error($conn));
        $redirect_url = 'index-logged.php?status=error&msg=update_failed';
        header("Location: $redirect_url");
        exit();
    }
    mysqli_stmt_close($stmt);

} else {
    // INSERT (Crear CV)
    $query_insert = "INSERT INTO curriculums (usuario_id, biografia, educacion, ciudad, experiencia, habilidades) 
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query_insert);
    mysqli_stmt_bind_param($stmt, "isssss", 
                            $id_talento, $biografia, $educacion, 
                            $ciudad, $experiencia, $habilidades);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = 'created';
    } else {
        error_log("Error al crear CV: " . mysqli_error($conn));
        $redirect_url = 'index-logged.php?status=error&msg=create_failed';
        header("Location: $redirect_url");
        exit();
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

// Redirigir siempre a index-logged.php con el mensaje de éxito
header("Location: index-logged.php?status=$success_message");
exit();
?>