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

$user_type = $_GET['type'] ?? '';
$user_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';
$user_data = null;
$table = '';
$id_col = '';
$fields = []; // Campos específicos para mostrar y actualizar

// Determinar la tabla y las columnas
if ($user_type === 'talento') {
    $table = 'talentos';
    $id_col = 'id_talento';
    $fields = ['nombre', 'apellido', 'email'];
    $name_fields = 'Nombre y Apellido';
} elseif ($user_type === 'agencia') {
    $table = 'agencias';
    $id_col = 'id_agencia';
    $fields = ['nombre_agencia', 'email_agencia', 'nombre_contacto', 'apellido_contacto'];
    $name_fields = 'Nombre Agencia y Contacto';
} else {
    $error = "Tipo de usuario o ID no válido.";
}

// 1. OBTENER DATOS ACTUALES
if ($user_id > 0 && $table != '') {
    $query = "SELECT * FROM {$table} WHERE {$id_col} = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) === 1) {
        $user_data = mysqli_fetch_assoc($result);
    } else {
        $error = "Usuario no encontrado.";
    }
}

// 2. PROCESAR FORMULARIO DE ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    
    // Preparar la consulta de actualización
    $update_fields = [];
    $update_params = [];
    $param_types = '';

    // Actualizar campos de nombre/email
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $update_fields[] = "{$field} = ?";
            $update_params[] = trim($_POST[$field]);
            $param_types .= 's';
            // Actualizar el valor en $user_data para que se muestre en el formulario
            $user_data[$field] = trim($_POST[$field]);
        }
    }

    // Manejar la contraseña
    if (!empty($_POST['new_password'])) {
        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $password_col = ($user_type === 'talento') ? 'password' : 'password_agencia';

        $update_fields[] = "{$password_col} = ?";
        $update_params[] = $new_password_hash;
        $param_types .= 's';
        
        $success_password = true;
    }

    // Ejecutar la actualización si hay campos para actualizar
    if (count($update_fields) > 0) {
        $update_query = "UPDATE {$table} SET " . implode(', ', $update_fields) . " WHERE {$id_col} = ?";
        
        // Agregar el ID al final de los parámetros
        $update_params[] = $user_id;
        $param_types .= 'i';

        $stmt_update = mysqli_prepare($conn, $update_query);
        
        // Bindear los parámetros dinámicamente
        // Nota: mysqli_stmt_bind_param requiere una referencia, por lo que usamos call_user_func_array
        $refs = [];
        foreach ($update_params as $key => $value) {
            $refs[$key] = &$update_params[$key];
        }
        array_unshift($refs, $stmt_update, $param_types);
        call_user_func_array('mysqli_stmt_bind_param', $refs);

        if (mysqli_stmt_execute($stmt_update)) {
            $success = "Datos actualizados exitosamente." . (isset($success_password) ? " (Contraseña cambiada)" : "");
        } else {
            $error = "Error al actualizar datos: " . mysqli_error($conn);
        }
    } else {
        $error = "No hay campos para actualizar.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Editar Usuario/Agencia</title>
	<link rel="stylesheet" type="text/css" href="../css/styles.css"/> 
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<style>
		body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
		.edit-container { max-width: 600px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
		.edit-container h1 { color: #4863a0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
		.form-group { margin-bottom: 20px; }
		.form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
		.form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
		.btn-save { background-color: #3e8e41; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
		.btn-save:hover { background-color: #316f33; }
		.message-success { background: #e6ffe6; color: #3e8e41; padding: 10px; border: 1px solid #3e8e41; border-radius: 4px; margin-bottom: 15px; }
		.message-error { background: #ffeded; color: #dc3545; padding: 10px; border: 1px solid #dc3545; border-radius: 4px; margin-bottom: 15px; }
		.back-link { display: block; margin-top: 20px; text-align: center; color: #4863a0; }
	</style>
</head>
<body>
	<div class="edit-container">
		<h1>Editar <?= ucfirst($user_type) ?> (ID: <?= $user_id ?>)</h1>
		
		<?php if ($success): ?>
			<p class="message-success"><?= htmlspecialchars($success) ?></p>
		<?php endif; ?>
		<?php if ($error): ?>
			<p class="message-error"><?= htmlspecialchars($error) ?></p>
		<?php endif; ?>
		
		<?php if ($user_data): ?>
			<form method="POST" action="admin_edit_user.php?type=<?= $user_type ?>&id=<?= $user_id ?>">

				<?php if ($user_type === 'talento'): ?>
					<div class="form-group">
						<label for="nombre">Nombre:</label>
						<input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($user_data['nombre'] ?? '') ?>" required>
					</div>
					<div class="form-group">
						<label for="apellido">Apellido:</label>
						<input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($user_data['apellido'] ?? '') ?>" required>
					</div>
					<div class="form-group">
						<label for="email">Email:</label>
						<input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
					</div>
				<?php elseif ($user_type === 'agencia'): ?>
					<div class="form-group">
						<label for="nombre_agencia">Nombre Agencia:</label>
						<input type="text" id="nombre_agencia" name="nombre_agencia" value="<?= htmlspecialchars($user_data['nombre_agencia'] ?? '') ?>" required>
					</div>
					<div class="form-group">
						<label for="email_agencia">Email Agencia:</label>
						<input type="email" id="email_agencia" name="email_agencia" value="<?= htmlspecialchars($user_data['email_agencia'] ?? '') ?>" required>
					</div>
					<div class="form-group">
						<label for="nombre_contacto">Nombre Contacto:</label>
						<input type="text" id="nombre_contacto" name="nombre_contacto" value="<?= htmlspecialchars($user_data['nombre_contacto'] ?? '') ?>" required>
					</div>
					<div class="form-group">
						<label for="apellido_contacto">Apellido Contacto:</label>
						<input type="text" id="apellido_contacto" name="apellido_contacto" value="<?= htmlspecialchars($user_data['apellido_contacto'] ?? '') ?>" required>
					</div>
				<?php endif; ?>
				
				<h2>Cambiar Contraseña (Opcional)</h2>
				<div class="form-group">
					<label for="new_password">Nueva Contraseña:</label>
					<input type="password" id="new_password" name="new_password" placeholder="Dejar vacío para no cambiar">
				</div>

				<input type="submit" value="Guardar Cambios" class="btn-save" style="width: 100%;">
			</form>
		<?php endif; ?>
		
		<a href="admin_dashboard.php" class="back-link">← Volver al Panel</a>
	</div>
</body>
</html>
<?php mysqli_close($conn); ?>