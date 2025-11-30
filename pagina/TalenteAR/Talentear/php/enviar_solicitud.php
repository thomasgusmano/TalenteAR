<?php
session_name("TALENTEAR_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    error_log("Error al conectar a la base de datos: " . mysqli_connect_error());
    header("Location: error.php?code=500");
    exit();
}

// 1. Verificar sesión y método POST
if (!isset($_SESSION["email"]) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: error.php?code=401");
    exit();
}
$email = $_SESSION["email"];
$casting_id = isset($_POST['casting_id']) ? (int)$_POST['casting_id'] : 0;

if ($casting_id === 0) {
    header("Location: index-logged.php?status=error&msg=no_casting_id");
    exit();
}

// 2. Obtener id_talento
$query_talento = "SELECT id_talento FROM talentos WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'";
$result_talento = mysqli_query($conn, $query_talento);

if (!$result_talento || mysqli_num_rows($result_talento) === 0) {
    header("Location: login.php");
    exit();
}
$id_talento = (int)mysqli_fetch_assoc($result_talento)['id_talento'];


// =========================================================
// LÓGICA FREEMIUM: OBTENER ESTADO Y VERIFICAR LÍMITE
// =========================================================
$LIMITE_GRATUITO = 5; 
$query_check_limit = "SELECT es_premium, postulaciones_mes FROM talentos WHERE id_talento = $id_talento";
$result_check_limit = mysqli_query($conn, $query_check_limit);

if (!$result_check_limit) {
    header("Location: index-logged.php?status=error&msg=db_limit_error");
    exit();
}

$data_limit = mysqli_fetch_assoc($result_check_limit);
$es_premium = (bool)$data_limit['es_premium'];
$postulaciones_mes = (int)$data_limit['postulaciones_mes'];

// Verificar si el límite gratuito ha sido alcanzado y el usuario NO es premium
if (!$es_premium && $postulaciones_mes >= $LIMITE_GRATUITO) {
    header("Location: index-logged.php?status=error&msg=limit_reached");
    exit();
}
// =========================================================


// 3. Obtener curriculum_id (necesario para la postulación)
$query_cv = "SELECT curriculum_id FROM curriculums WHERE usuario_id = $id_talento LIMIT 1";
$result_cv = mysqli_query($conn, $query_cv);

if (!$result_cv || mysqli_num_rows($result_cv) === 0) {
    header("Location: index-logged.php?status=error&msg=no_cv"); // Error si no tiene CV
    exit();
}
$curriculum_id = (int)mysqli_fetch_assoc($result_cv)['curriculum_id'];

// 4. Obtener agencia_id del casting
$query_agencia = "SELECT agencia_id FROM castings WHERE casting_id = $casting_id LIMIT 1";
$result_agencia = mysqli_query($conn, $query_agencia);
if (!$result_agencia || mysqli_num_rows($result_agencia) === 0) {
    header("Location: index-logged.php?status=error&msg=casting_not_found");
    exit();
}
$agencia_id = (int)mysqli_fetch_assoc($result_agencia)['agencia_id'];

// 5. Verificar si ya se postuló a este casting (doble check)
$check_duplicate_query = "SELECT 1 FROM notificaciones 
                         WHERE curriculum_id = $curriculum_id 
                           AND casting_id = $casting_id 
                           AND agencia_id = $agencia_id 
                           LIMIT 1";
$check_duplicate_result = mysqli_query($conn, $check_duplicate_query);
if (mysqli_num_rows($check_duplicate_result) > 0) {
    header("Location: index-logged.php?status=error&msg=already_applied");
    exit();
}


// 6. Insertar la notificación (Postulación)
$insert_query = "
    INSERT INTO notificaciones (curriculum_id, agencia_id, casting_id, fecha, estado, leida) 
    VALUES ($curriculum_id, $agencia_id, $casting_id, NOW(), 'pendiente', 0)
    /* 🔥 FIX: 'leida' = 0 asegura que el contador salte en la Agencia. */
";

if (mysqli_query($conn, $insert_query)) {
    
    // =========================================================
    // LÓGICA FREEMIUM: INCREMENTAR CONTADOR SI NO ES PREMIUM
    // =========================================================
    if (!$es_premium) {
        $query_update_counter = "UPDATE talentos 
                                 SET postulaciones_mes = postulaciones_mes + 1 
                                 WHERE id_talento = $id_talento";
        mysqli_query($conn, $query_update_counter);
    }
    // =========================================================

    // Éxito: Redirigir a la página principal (la postulación se confirma con JS)
    header("Location: index-logged.php"); 
    exit();
} else {
    error_log("Error al postular: " . mysqli_error($conn));
    header("Location: index-logged.php?status=error&msg=db_insert_error");
    exit();
}

mysqli_close($conn);
?>