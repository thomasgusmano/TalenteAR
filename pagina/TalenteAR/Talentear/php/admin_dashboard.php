<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("ADMIN_SESION");
session_start();

// VERIFICACIÓN DE ACCESO
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
	header("Location: admin_login.php"); 
	exit();
}

$host = "localhost";
$username = "root";
$password = ""; // **AJUSTA ESTA CONTRASEÑA SI ES NECESARIO**
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

$admin_nombre = $_SESSION['nombre'] ?? 'Administrador';

// --- LÓGICA DE ACCIONES DE GESTIÓN (DELETE & TOGGLE) ---

// 1. Alternar Estado Premium
if (isset($_GET['action']) && $_GET['action'] == 'toggle_premium' && isset($_GET['id'])) {
    $id_talento = (int)$_GET['id'];
    
    $query_current = "SELECT es_premium FROM talentos WHERE id_talento = ?";
    $stmt_current = mysqli_prepare($conn, $query_current);
    mysqli_stmt_bind_param($stmt_current, "i", $id_talento);
    mysqli_stmt_execute($stmt_current);
    $result_current = mysqli_stmt_get_result($stmt_current);
    $current_status = mysqli_fetch_assoc($result_current)['es_premium'];
    $new_status = $current_status ? 0 : 1; 

    $query_update = "UPDATE talentos SET es_premium = ? WHERE id_talento = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt_update, "ii", $new_status, $id_talento);
    mysqli_stmt_execute($stmt_update);

    header("Location: admin_dashboard.php");
    exit();
}

// 2. Eliminar Talento (Requiere borrar dependencias: Curriculums y Notificaciones)
if (isset($_GET['action']) && $_GET['action'] == 'delete_talento' && isset($_GET['id'])) {
    $id_talento = (int)$_GET['id'];
    mysqli_autocommit($conn, FALSE); // Inicia transacción
    $success = true;

    try {
        // A. Eliminar Notificaciones relacionadas con los Curriculums del Talento
        $query_notif = "DELETE n FROM notificaciones n JOIN curriculums c ON n.curriculum_id = c.curriculum_id WHERE c.usuario_id = ?";
        $stmt_notif = mysqli_prepare($conn, $query_notif);
        mysqli_stmt_bind_param($stmt_notif, "i", $id_talento);
        $success = mysqli_stmt_execute($stmt_notif);
        mysqli_stmt_close($stmt_notif);

        // B. Eliminar Curriculums del Talento
        if ($success) {
            $query_curr = "DELETE FROM curriculums WHERE usuario_id = ?";
            $stmt_curr = mysqli_prepare($conn, $query_curr);
            mysqli_stmt_bind_param($stmt_curr, "i", $id_talento);
            $success = mysqli_stmt_execute($stmt_curr);
            mysqli_stmt_close($stmt_curr);
        }

        // C. Eliminar Talento
        if ($success) {
            $query_talento = "DELETE FROM talentos WHERE id_talento = ?";
            $stmt_talento = mysqli_prepare($conn, $query_talento);
            mysqli_stmt_bind_param($stmt_talento, "i", $id_talento);
            $success = mysqli_stmt_execute($stmt_talento);
            mysqli_stmt_close($stmt_talento);
        }

        if ($success) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
    mysqli_autocommit($conn, TRUE);

    header("Location: admin_dashboard.php");
    exit();
}

// 3. Eliminar Agencia (Requiere borrar dependencias: Castings)
if (isset($_GET['action']) && $_GET['action'] == 'delete_agencia' && isset($_GET['id'])) {
    $id_agencia = (int)$_GET['id'];
    mysqli_autocommit($conn, FALSE); 
    $success = true;

    try {
        // A. Eliminar Castings relacionados con la Agencia
        $query_cast = "DELETE FROM castings WHERE agencia_id = ?";
        $stmt_cast = mysqli_prepare($conn, $query_cast);
        mysqli_stmt_bind_param($stmt_cast, "i", $id_agencia);
        $success = mysqli_stmt_execute($stmt_cast);
        mysqli_stmt_close($stmt_cast);

        // B. Eliminar Agencia
        if ($success) {
            $query_agencia = "DELETE FROM agencias WHERE id_agencia = ?";
            $stmt_agencia = mysqli_prepare($conn, $query_agencia);
            mysqli_stmt_bind_param($stmt_agencia, "i", $id_agencia);
            $success = mysqli_stmt_execute($stmt_agencia);
            mysqli_stmt_close($stmt_agencia);
        }

        if ($success) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
    mysqli_autocommit($conn, TRUE);

    header("Location: admin_dashboard.php");
    exit();
}


// OBTENER MÉTRICAS GENERALES
$total_talentos = 0;
$total_premium = 0;
$total_agencias = 0;

$query_metrics = "
    SELECT 
        (SELECT COUNT(id_talento) FROM talentos) AS total_talentos,
        (SELECT SUM(CASE WHEN es_premium = 1 THEN 1 ELSE 0 END) FROM talentos) AS total_premium,
        (SELECT COUNT(id_agencia) FROM agencias) AS total_agencias
";
$result_metrics = mysqli_query($conn, $query_metrics);

if ($result_metrics) {
	$metrics = mysqli_fetch_assoc($result_metrics);
	$total_talentos = (int)$metrics['total_talentos'];
	$total_premium = (int)$metrics['total_premium'];
    $total_agencias = (int)$metrics['total_agencias'];
}

// Obtener la lista de talentos y agencias
$query_talentos = "SELECT id_talento, nombre, apellido, email, es_premium FROM talentos ORDER BY id_talento DESC";
$result_talentos = mysqli_query($conn, $query_talentos);

$query_agencias = "SELECT id_agencia, nombre_agencia, email_agencia, nombre_contacto, apellido_contacto FROM agencias ORDER BY id_agencia DESC";
$result_agencias = mysqli_query($conn, $query_agencias);
?>
<!DOCTYPE html>
<html>
<head>
	<title>Panel de Administración</title>
	<link rel="stylesheet" type="text/css" href="../css/styles.css"/> 
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<style>
		body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
		.dashboard-container { max-width: 1300px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
		.metrics-grid { display: flex; justify-content: space-between; margin-bottom: 30px; gap: 20px; }
		.metric-card { background: #f0f4ff; padding: 25px; border-radius: 6px; text-align: center; flex: 1; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 5px solid #4863a0; }
		.metric-card h3 { margin-top: 0; color: #4863a0; font-size: 1.1em; }
		.metric-card p { font-size: 3em; font-weight: bold; color: #333; margin: 0; }
		.user-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		.user-table th, .user-table td { border: 1px solid #eee; padding: 12px; text-align: left; }
		.user-table th { background-color: #4863a0; color: white; }
		.navbar-admin { background-color: #4863a0; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        .badge.premium { background-color: #3e8e41; color: white; }
        .badge.free { background-color: #ffc107; color: #333; }
        .btn-action { background: #6c757d; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9em; display: inline-block; margin: 2px 0; }
        .btn-action:hover { background: #5a6268; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        .btn-edit { background: #007bff; }
        .btn-edit:hover { background: #0056b3; }
        h2 { margin-top: 40px; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #4863a0; }
        .metric-card.agencias { border-left-color: #007bff; background: #e7f4ff; }
        .metric-card.agencias h3 { color: #007bff; }
        .metric-card.ingresos { border-left-color: #28a745; background: #e6ffe6; }
        .metric-card.ingresos p { color: #28a745; }
	</style>
</head>
<body>
	<div class="navbar-admin">
		<div class="navbar-title">
			<a href="admin_dashboard.php" style="color: white; text-decoration: none;">
				<h2 style="margin: 0;">TalenteAR Admin Dashboard</h2>
			</a>
		</div>
		<div class="navbar-links">
			<span style="padding: 0 15px;">Bienvenido, <?= htmlspecialchars($admin_nombre) ?></span>
			<a href="cerrar_sesion.php" style="color: white; text-decoration: underline;">Cerrar sesión</a>
		</div>
	</div>

	<div class="dashboard-container">
		<h1>Panel de Control del Administrador</h1>

		<h2>📊 Resumen General</h2>
		<div class="metrics-grid">
			<div class="metric-card">
				<h3>👥 Total Talentos</h3>
				<p><?= $total_talentos ?></p>
			</div>
			<div class="metric-card metric-card-premium" style="border-left-color: #3e8e41; background: #e6ffe6;">
				<h3>⭐ Talentos Premium</h3>
				<p style="color: #3e8e41;"><?= $total_premium ?></p>
			</div>
			<div class="metric-card metric-card-agencias metric-card-agencias">
				<h3>🏢 Total Agencias</h3>
				<p style="color: #007bff;"><?= $total_agencias ?></p>
			</div>
			<div class="metric-card metric-card-ingresos ingresos">
				<h3>💰 Ingresos Mensuales Estimados ($9.99 por suscripción)</h3>
				<p>$<?= number_format($total_premium * 9.99, 2, ',', '.') ?></p>
			</div>
		</div>

		<h2>👤 Gestión de Talentos (Usuarios)</h2>
		<?php if ($result_talentos && mysqli_num_rows($result_talentos) > 0): ?>
			<table class="user-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nombre Completo</th>
						<th>Email</th>
						<th>Premium</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($talento = mysqli_fetch_assoc($result_talentos)): ?>
					<tr>
						<td><?= $talento['id_talento'] ?></td>
						<td><?= htmlspecialchars($talento['nombre'] . ' ' . $talento['apellido']) ?></td>
						<td><?= htmlspecialchars($talento['email']) ?></td>
						<td>
                            <span class="badge <?= $talento['es_premium'] ? 'premium' : 'free' ?>">
                                <?= $talento['es_premium'] ? 'Premium' : 'Gratis' ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $accion_texto = $talento['es_premium'] ? 'Desactivar Premium' : 'Activar Premium';
                            ?>
                            <a href="?action=toggle_premium&id=<?= $talento['id_talento'] ?>" class="btn-action">
                                <?= $accion_texto ?>
                            </a>
                            <a href="admin_edit_user.php?type=talento&id=<?= $talento['id_talento'] ?>" class="btn-action btn-edit">
                                Editar
                            </a>
                            <a href="?action=delete_talento&id=<?= $talento['id_talento'] ?>" class="btn-action btn-delete" onclick="return confirm('¿Estás seguro de ELIMINAR al talento <?= htmlspecialchars($talento['nombre']) ?>? Se borrarán sus curriculums y notificaciones.')">
                                Eliminar
                            </a>
                        </td>
					</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p style="text-align:center; padding: 20px; background: #ffeded; border: 1px solid #f00;">No hay talentos registrados en este momento.</p>
		<?php endif; ?>
        
		<h2>🏢 Gestión de Agencias</h2>
		<?php if ($result_agencias && mysqli_num_rows($result_agencias) > 0): ?>
			<table class="user-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nombre Agencia</th>
						<th>Email</th>
						<th>Contacto Principal</th>
                        <th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($agencia = mysqli_fetch_assoc($result_agencias)): ?>
					<tr>
						<td><?= $agencia['id_agencia'] ?></td>
						<td><?= htmlspecialchars($agencia['nombre_agencia']) ?></td>
						<td><?= htmlspecialchars($agencia['email_agencia']) ?></td>
						<td><?= htmlspecialchars($agencia['nombre_contacto'] . ' ' . $agencia['apellido_contacto']) ?></td>
                        <td>
                            <a href="admin_edit_user.php?type=agencia&id=<?= $agencia['id_agencia'] ?>" class="btn-action btn-edit">
                                Editar
                            </a>
                            <a href="?action=delete_agencia&id=<?= $agencia['id_agencia'] ?>" class="btn-action btn-delete" onclick="return confirm('¿Estás seguro de ELIMINAR la agencia <?= htmlspecialchars($agencia['nombre_agencia']) ?>? Se borrarán todos sus castings.')">
                                Eliminar
                            </a>
                        </td>
					</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p style="text-align:center; padding: 20px; background: #ffeded; border: 1px solid #f00;">No hay agencias registradas en este momento.</p>
		<?php endif; ?>

	</div>
</body>
</html>
<?php mysqli_close($conn); ?>