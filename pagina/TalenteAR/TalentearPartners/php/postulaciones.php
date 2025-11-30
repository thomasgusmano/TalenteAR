<?php
session_name("PARTNERS_SESION");
session_start();

// ----------------------------------------------------
// --- 1. Inicialización de Variables y Conexión ---
// ----------------------------------------------------
$conn = null;
$postulaciones_pendientes = [];
$postulaciones_historial = [];
$notifError = null;
$mensaje_estado = ''; // Variable para mensajes de éxito/error

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// 2. Verificar sesión y Obtener id_agencia
if (!isset($_SESSION["email_agencia"])) {
    mysqli_close($conn);
    header("Location: ../loginpartners.php");
    exit();
}

$email_agencia = $_SESSION["email_agencia"];
$id_agencia = 0;
$nombre_agencia = "";
$nombre_contacto = "";

$query_agencia = "SELECT id_agencia, nombre_agencia, nombre_contacto FROM agencias WHERE email_agencia = ?";
$stmt_agencia = mysqli_prepare($conn, $query_agencia);
if ($stmt_agencia) {
    mysqli_stmt_bind_param($stmt_agencia, "s", $email_agencia);
    mysqli_stmt_execute($stmt_agencia);
    $result_agencia = mysqli_stmt_get_result($stmt_agencia);
    if ($result_agencia && mysqli_num_rows($result_agencia) > 0) {
        $agencia_data = mysqli_fetch_assoc($result_agencia);
        $id_agencia = (int)$agencia_data["id_agencia"];
        $nombre_agencia = $agencia_data["nombre_agencia"];
        $nombre_contacto = $agencia_data["nombre_contacto"];
    } else {
        mysqli_close($conn);
        header("Location: ../loginpartners.php");
        exit();
    }
    mysqli_stmt_close($stmt_agencia);
} else {
    $notifError = "Error al preparar la consulta de agencia: " . mysqli_error($conn);
}


// 3. Manejo de mensajes de estado (después de gestionar_postulante.php)
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'success_acept') {
        $mensaje_estado = '<p class="msg-success">✅ ¡Postulante **aceptado** con éxito! El talento ha sido notificado.</p>';
    } elseif ($msg === 'success_reject') {
        $mensaje_estado = '<p class="msg-warning">❌ Postulante **rechazado**. El talento ha sido notificado.</p>';
    } elseif (strpos($msg, 'fail') !== false || $msg === 'invalid_data') {
        $mensaje_estado = '<p class="msg-error">⚠️ Error al procesar la solicitud. Intenta nuevamente.</p>';
    }
}

// ----------------------------------------------------
// --- 4. Consultas de Postulaciones ---
// ----------------------------------------------------
if ($id_agencia > 0) {
    // Consulta 1: Postulaciones PENDIENTES (Para gestión activa)
    // Se añade el filtro: AND n.casting_id IS NOT NULL AND n.casting_id > 0
    $pendientesQ = "
        SELECT 
            n.notificacion_id, n.fecha, n.estado, n.casting_id,
            t.nombre AS nombre_talento, t.apellido AS apellido_talento, t.email AS email_talento, t.id_talento,
            c.biografia, c.educacion, c.ciudad, c.experiencia,
            o.NombrePuesto AS nombre_casting, o.DescripcionPuesto AS descripcion_casting
        FROM notificaciones n
        JOIN curriculums c ON n.curriculum_id = c.curriculum_id
        JOIN talentos t ON c.usuario_id = t.id_talento
        LEFT JOIN castings o ON n.casting_id = o.casting_id
        WHERE n.agencia_id = ? 
        AND n.estado = 'pendiente'
        AND n.casting_id IS NOT NULL AND n.casting_id > 0 
        ORDER BY n.fecha DESC
    ";
    $stmt_pendientes = mysqli_prepare($conn, $pendientesQ);
    if ($stmt_pendientes) {
        mysqli_stmt_bind_param($stmt_pendientes, "i", $id_agencia);
        mysqli_stmt_execute($stmt_pendientes);
        $result_pendientes = mysqli_stmt_get_result($stmt_pendientes);
        if (!$result_pendientes) {
            $notifError = "Error en consulta de pendientes: " . mysqli_error($conn);
        } else {
            $postulaciones_pendientes = mysqli_fetch_all($result_pendientes, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt_pendientes);
    } else {
        $notifError = "Error al preparar la consulta de pendientes: " . mysqli_error($conn);
    }

    // Consulta 2: Historial de Postulaciones (Ya gestionadas) - Se mantiene el original
    $historialQ = "
        SELECT 
            n.notificacion_id, n.fecha, n.estado, n.casting_id,
            t.nombre AS nombre_talento, t.apellido AS apellido_talento, t.email AS email_talento, t.id_talento,
            c.ciudad,
            o.NombrePuesto AS nombre_casting
        FROM notificaciones n
        JOIN curriculums c ON n.curriculum_id = c.curriculum_id
        JOIN talentos t ON c.usuario_id = t.id_talento
        LEFT JOIN castings o ON n.casting_id = o.casting_id
        WHERE n.agencia_id = ? AND n.estado IN ('aceptada', 'rechazada') 
        ORDER BY n.fecha DESC
    ";
    $stmt_historial = mysqli_prepare($conn, $historialQ);
    if ($stmt_historial) {
        mysqli_stmt_bind_param($stmt_historial, "i", $id_agencia);
        mysqli_stmt_execute($stmt_historial);
        $result_historial = mysqli_stmt_get_result($stmt_historial);
        if (!$result_historial) {
            $notifError = "Error en consulta de historial: " . mysqli_error($conn);
        } else {
            $postulaciones_historial = mysqli_fetch_all($result_historial, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt_historial);
    } else {
        $notifError = "Error al preparar la consulta de historial: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Postulantes - TalenteAR Partners</title>

    <link rel="stylesheet" type="text/css" href="../css/home-empresas.css"/> 
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <link rel="stylesheet" type="text/css" href="../css/login.css"/>
    <link rel="stylesheet" type="text/css" href="../css/solicitudes.css"/>
    <link rel="stylesheet" type="text/css" href="../css/postulaciones.css"/> <script src="../js/profile-menu.js" defer></script> 
</head>
<body class="body-postulaciones"> <div class="navbar">
    <div class="navbar-title">
        <div class="imagenesflechas">
            <a href="#" class="flecha-link" onclick="goBack()">
                <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás" />
                <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás" />
            </a>
        </div>
        <a href="index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="TalenteAR" />
            <h2 class="title">TalenteAR Partners</h2>
        </a>
    </div>

    <div class="navbar-links">
        <a href="../cerrar_sesion.php" class="box redirect">Cerrar sesión</a>
        
        <div class="profile-menu">
            <button class="profile-button" onclick="toggleProfileMenu()" style="background:none;">
                <img src="../../imagenes/profilelogo.png" alt="Perfil" class="profile-logo" />
            </button>
            <div id="profileDropdown" class="dropdown-content">
                <?php echo "<p style='text-align:center'>" . htmlspecialchars($nombre_contacto) . " (" . htmlspecialchars($nombre_agencia) . ")</p>"; ?>
                <hr>
                <a href="perfil_agencia.php">Mi Perfil</a>
                <a href="configuracion_agencia.php">Configuración</a>
                <a href="../cerrar_sesion.php">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</div>
<div class="container-body">
    
    <?php echo $mensaje_estado; // Muestra mensajes de éxito/error ?>

    <h2 class="titulo-seccion">🌟 Postulantes Pendientes de Revisión (<?= count($postulaciones_pendientes) ?>)</h2>
    <p>Revisa estos talentos que se postularon a tus castings y decide si deseas contactarlos. El talento será notificado con tu respuesta.</p>
    
    <?php if ($notifError): ?>
        <p class='msg-error'>Error al cargar postulaciones: <?= htmlspecialchars($notifError) ?></p>
    <?php elseif (empty($postulaciones_pendientes)): ?>
        <div class="empty-state-message">
            <h4>🎉 ¡Casting al día!</h4>
            <p>No tienes postulaciones nuevas pendientes de revisión. **Solo se muestran las postulaciones directas a tus castings.**</p>
        </div>
    <?php else: ?>
        <div class="postulaciones-feed">
            <?php foreach ($postulaciones_pendientes as $p): 
                $fecha_completa_p = strtotime($p['fecha']);
                $fecha_recibida_p = date('d/m/Y', $fecha_completa_p);
                $tipo_borde = '#5d2b77'; // Se asume que son postulaciones a casting gracias al filtro SQL.
            ?>
                <div class="postulante-panel" style="border-left: 5px solid <?= $tipo_borde ?>;">
                    <h3>
                        <?= htmlspecialchars($p['nombre_talento'] . ' ' . $p['apellido_talento']) ?>
                        <span style="font-size: 0.8em; font-weight: normal; color: gray;">Recibido: <?= $fecha_recibida_p ?></span>
                    </h3>
                    
                    <p class="postulante-casting">
                        Postulado al Casting: **<?= htmlspecialchars($p['nombre_casting'] ?: 'N/A') ?>**
                    </p>
                    
                    <hr>
                    
                    <div class="cv-data">
                        <p><strong><span class="icon">📧</span> Email:</strong> <?= htmlspecialchars($p['email_talento']) ?></p>
                        <p><strong><span class="icon">📍</span> Ciudad:</strong> <?= htmlspecialchars($p['ciudad']) ?></p>
                        <p><strong>Biografía:</strong> <em><?= nl2br(htmlspecialchars(substr($p['biografia'], 0, 150))) . (strlen($p['biografia']) > 150 ? '...' : '') ?></em></p>
                        <p><strong>Educación:</strong> <?= nl2br(htmlspecialchars(substr($p['educacion'], 0, 100))) . (strlen($p['educacion']) > 100 ? '...' : '') ?></p>
                    </div>

                    <div class="panel-acciones">
                        <form method="POST" action="gestionar_postulante.php" class="form-action">
                            <input type="hidden" name="notificacion_id" value="<?= (int)$p['notificacion_id'] ?>">
                            <input type="hidden" name="accion" value="aceptar">
                            <button class="btn-acept" type="submit" onclick="return confirm('¿Confirmas aceptar a este postulante? Se le notificará.')">Aceptar ✅</button>
                        </form>
                        
                        <form method="POST" action="gestionar_postulante.php" class="form-action">
                            <input type="hidden" name="notificacion_id" value="<?= (int)$p['notificacion_id'] ?>">
                            <input type="hidden" name="accion" value="rechazar">
                            <button class="btn-rech" type="submit" onclick="return confirm('¿Confirmas rechazar a este postulante? Se le notificará.')">Rechazar ❌</button>
                        </form>
                        
                        <form method='GET' action='ver_talento.php' class='form-action'>
                            <input type='hidden' name='id_talento' value='<?= (int)$p['id_talento'] ?>'>
                            <button type='submit' class='btn-info'>Ver Perfil Completo</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-separator">
        <span>FIN DE PENDIENTES</span>
    </div>

    <h2 class="titulo-seccion">💾 Historial de Postulaciones Gestionadas</h2>
    
    <?php if (empty($postulaciones_historial)): ?>
        <div class="empty-state-message" style="background-color: #f7f7f7; color: #666; border: 1px dashed #ccc;">
            <h4>📂 Historial vacío</h4>
            <p>Aquí aparecerán los talentos que aceptes o rechaces en el futuro.</p>
        </div>
    <?php else: ?>
        <div class="postulaciones-feed">
            <?php foreach ($postulaciones_historial as $h): 
                $tag_class = ($h['estado'] === 'aceptada') ? 'tag-aceptada' : 'tag-rechazada';
                $tag_text = ($h['estado'] === 'aceptada') ? 'Aceptado ✅' : 'Rechazado ❌';
                $border_color = ($h['estado'] === 'aceptada') ? '#4CAF50' : '#888';
                $fecha_completa_h = strtotime($h['fecha']);
                $fecha_gestion_h = date('d/m/Y H:i', $fecha_completa_h);
                
                // Se mantiene la lógica para el Historial, ya que sí debe mostrar los resultados de las invitaciones.
                $casting_historial_text = !empty($h['nombre_casting']) ? "Casting: **" . htmlspecialchars($h['nombre_casting']) . "**" : "Respuesta a tu Invitación.";
            ?>
                <div class="postulante-panel" style="border-left: 5px solid <?= $border_color ?>; opacity: 0.9;">
                    <h3>
                        <?= htmlspecialchars($h['nombre_talento'] . ' ' . $h['apellido_talento']) ?>
                        <span class="estado-tag <?= $tag_class ?>"><?= $tag_text ?></span>
                    </h3>
                    
                    <p class="postulante-casting" style="font-size: 1em;">
                        <?= $casting_historial_text ?>
                    </p>
                    
                    <p style="font-size:0.85em; color:gray; margin-top:5px;">
                        Ciudad: <?= htmlspecialchars($h['ciudad']) ?> | Gestionado el: <?= $fecha_gestion_h ?>
                    </p>
                    
                    <div class="panel-acciones" style="margin-top: 10px;">
                        <form method='GET' action='ver_talento.php' class='form-action'>
                            <input type='hidden' name='id_talento' value='<?= (int)$h['id_talento'] ?>'>
                            <button type='submit' class='btn-info' style="padding: 5px 10px; font-size: 0.9em;">Ver Perfil</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    </footer>

<script>
function goBack() { window.history.back(); }

function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
}
</script>

<?php
if ($conn) {
    mysqli_close($conn); 
}
?>

</body>
</html>