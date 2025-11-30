<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("ADMIN_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = ""; // **AJUSTA ESTA CONTRASEÑA**
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    $error = "Error al conectar con la base de datos. Verifica XAMPP y las credenciales.";
} else {
    $error = '';
}

if (isset($conn) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass_ingresada = $_POST['password'];

    $query = "SELECT id_admin, password, nombre FROM administradores WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            if (password_verify($pass_ingresada, $row['password'])) {
                
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['rol'] = 'admin'; 
                $_SESSION['nombre'] = $row['nombre'];
                
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                
                header("Location: admin_dashboard.php"); 
                exit();
            } else {
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Error interno del servidor al preparar la consulta.";
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Acceso Admin TalenteAR</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <div class="admin-login-container">
        <h2>Acceso de Administración</h2>
        <?php if ($error): ?>
            <p style="color: red; text-align: center; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="admin_login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required placeholder="admin@admin"><br><br>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required placeholder="admin"><br><br>
            
            <input type="submit" value="Iniciar Sesión" style="width: 100%;">
        </form>
    </div>
</body>
</html>