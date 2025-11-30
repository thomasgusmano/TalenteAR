<?php
session_name("TALENTEAR_SESION");
session_start();

// ----------------------------------------------------
// --- 1. Validar la Sesión del Talento y Conexión ---
// ----------------------------------------------------
if (!isset($_SESSION["email"])) { 
    header("Location: ../login.php"); 
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Error al conectar a la base de datos: " . mysqli_connect_error());
}

// -------------------------------------------------------------------------------------
// --- 2. Obtener IDs (Talento y Curriculum) y Agencia ID desde la Notificación ---
// -------------------------------------------------------------------------------------
$email_talento = mysqli_real_escape_string($conn, $_SESSION["email"]);
$query_talento_data = "
    SELECT t.id_talento, c.curriculum_id, t.nombre
    FROM talentos t
    JOIN curriculums c ON t.id_talento = c.usuario_id
    WHERE t.email = '$email_talento' LIMIT 1
";
$result_talento_data = mysqli_query($conn, $query_talento_data);
if (!$result_talento_data || mysqli_num_rows($result_talento_data) === 0) {
    mysqli_close($conn);
    header("Location: ../login.php"); 
    exit();
}
$talento_data = mysqli_fetch_assoc($result_talento_data);
$id_talento = (int)$talento_data['id_talento'];
$curriculum_id = (int)$talento_data['curriculum_id']; 
$nombre_talento = $talento_data['nombre'];


// --------------------------------------------------------
// --- 3. Obtener y Validar Datos de la Respuesta POST ---
// --------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["notificacion_id"], $_POST["accion"])) {
    
    $notificacion_id = (int)$_POST["notificacion_id"];
    $accion = $_POST["accion"]; 

    if ($notificacion_id <= 0 || !in_array($accion, ['aceptada', 'rechazada'])) {
        mysqli_close($conn);
        die("Error: Datos o acción inválida.");
    }
    
    $accion_segura = mysqli_real_escape_string($conn, $accion);

    // ---------------------------------------------------------------
    // --- 4. Obtener AGENCIA_ID y Curriculum_ID de la invitación ---
    // ---------------------------------------------------------------
    $query_get_invitation_data = "
        SELECT agencia_id
        FROM notificaciones
        WHERE notificacion_id = $notificacion_id
        AND curriculum_id = $curriculum_id
        AND estado = 'invitacion' /* Buscamos específicamente el estado INVITACION */
    ";
    $result_invitation_data = mysqli_query($conn, $query_get_invitation_data);
    
    if (!$result_invitation_data || mysqli_num_rows($result_invitation_data) === 0) {
        mysqli_close($conn);
        header("Location: solicitudes.php?msg=not_found_or_responded"); // Ya respondió o no existe
        exit();
    }
    $invitation_data = mysqli_fetch_assoc($result_invitation_data);
    $id_agencia = (int)$invitation_data['agencia_id'];


    // ------------------------------------------------------------------------------
    // --- 5. UPDATE: Actualiza la invitación original a 'aceptada' o 'rechazada' ---
    // ------------------------------------------------------------------------------
    $query_update_response = "
        UPDATE notificaciones 
        SET 
            estado = '$accion_segura', 
            leida = 1,                 /* Marcamos como LEÍDA para que no cuente al Talento */
            fecha_respuesta = NOW() 
        WHERE notificacion_id = $notificacion_id
        AND curriculum_id = $curriculum_id 
        AND estado = 'invitacion'      /* Solo actualiza si sigue siendo una INVITACIÓN */
    ";

    if (mysqli_query($conn, $query_update_response)) {
        
        // --------------------------------------------------------------------------
        // --- 6. INSERT: Creamos una NUEVA notificación para la AGENCIA ---
        // --------------------------------------------------------------------------
        
        // El estado de la NUEVA notificación para la agencia es:
        $estado_nuevo = ($accion === 'aceptada') ? 'invitacion_aceptada' : 'invitacion_rechazada';
        
        $query_insert_new_notif_agency = "
            INSERT INTO notificaciones (agencia_id, curriculum_id, casting_id, estado, leida, fecha)
            VALUES (
                $id_agencia, 
                $curriculum_id, 
                NULL, /* No tiene casting_id asociado, es solo una invitación de colaboración */
                '$estado_nuevo', 
                0,    /* Marcada como NO LEÍDA para que cuente a la Agencia */
                NOW()
            )
        ";
        
        mysqli_query($conn, $query_insert_new_notif_agency);
        // Si falla el insert, la actualización ya se hizo, solo queda el Talento notificado.

        // Éxito: Redirigir al Talento.
        $msg_redir = ($accion === 'aceptada') ? 'invite_accept_success' : 'invite_reject_success';
        header("Location: solicitudes.php?msg=" . $msg_redir); 
        exit();
    } else {
        // Error en el Update
        echo "Error al procesar la respuesta: " . mysqli_error($conn);
    }

} else {
    // Acceso inválido (no POST o faltan datos)
    header("Location: solicitudes.php"); 
    exit();
}

mysqli_close($conn);
?>