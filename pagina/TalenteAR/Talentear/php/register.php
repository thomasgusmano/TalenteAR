<?php
session_name("TALENTEAR_SESION");
session_start();

// --- 1. CONFIGURACIÓN DE CONEXIÓN ---
$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "agenciatrabajo"; 

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar a la base de talentos: " . mysqli_connect_error());
}

// Inicialización de la variable de error y de las variables para repoblar el formulario
$error = null;
$nombre = $apellido = $email = $numero_telefono = $fecha_nacimiento = $tipo_documento = $numero_documento = '';
$terms = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recolección de variables
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $email = trim($_POST["email"]);
    $password_input = $_POST["password"]; 
    $confirm_password = $_POST["confirm_password"];
    $numero_telefono = trim($_POST["numero_telefono"]);
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $tipo_documento = $_POST["tipo_documento"] ?? ''; 
    $numero_documento = trim($_POST["numero_documento"]);
    $terms = isset($_POST["terms"]);

    // --- 2. VALIDACIÓN DE ENTRADA Y LÍMITES ---

    // Comprobar que los campos críticos no estén vacíos
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password_input) || empty($confirm_password) || empty($numero_telefono) || empty($fecha_nacimiento) || empty($tipo_documento) || empty($numero_documento)) {
        $error = "Todos los campos son obligatorios. Por favor, completa el formulario.";
    } elseif (!$terms) {
        $error = "Debes aceptar los Términos y Condiciones para registrarte.";
    } 
    
    // Validación de Nombre y Apellido (Solo letras, espacios, hasta 50 caracteres)
    elseif (!preg_match("/^[a-zA-Z\s]{1,50}$/", $nombre)) {
        $error = "El Nombre solo debe contener letras y espacios, y tener un máximo de 50 caracteres.";
    }
    elseif (!preg_match("/^[a-zA-Z\s]{1,50}$/", $apellido)) {
        $error = "El Apellido solo debe contener letras y espacios, y tener un máximo de 50 caracteres.";
    }
    
    // Validación de Formato y Dominio de Email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    }
    elseif (!checkdnsrr(substr($email, strpos($email, '@') + 1), 'MX')) {
        $error = "El dominio del correo electrónico parece no ser válido o no existe.";
    }
    
    // Validación de Teléfono (solo números, mínimo 8, máximo 15)
    elseif (!preg_match("/^[0-9]{8,15}$/", $numero_telefono)) {
        $error = "El número de teléfono debe contener solo dígitos (entre 8 y 15) y no debe contener espacios.";
    }
    
    // Validación de Número de Documento (solo números, hasta 10 dígitos)
    elseif (!preg_match("/^[0-9]{4,10}$/", $numero_documento)) {
        $error = "El número de documento debe contener solo dígitos, sin puntos ni espacios (4 a 10 dígitos).";
    }
    
    // Validación de Edad Mínima (18 años)
    elseif (strtotime($fecha_nacimiento) > strtotime('-18 years')) {
        $error = "Debes ser mayor de 18 años para registrarte en TalenteAR.";
    }
    
    // Comparación y Longitud de Contraseñas
    elseif ($password_input != $confirm_password) {
        $error = "Las contraseñas no coinciden. Por favor, intenta nuevamente.";
    } 
    elseif (strlen($password_input) < 8 || strlen($password_input) > 32) {
        $error = "La contraseña debe tener entre 8 y 32 caracteres.";
    }
    
    // Si todas las validaciones de formato pasan:
    if ($error === null) {
        
        // Comprobación de existencia de email (VULNERABLE a Inyección SQL)
        $query = "SELECT * FROM talentos WHERE email = '$email'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $error = "El correo electrónico ya está registrado. Por favor, utiliza otro correo electrónico.";
        } else {
            // 3. Inserción de datos (VULNERABLE a Inyección SQL)
            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO talentos (nombre, apellido, email, password, numero_telefono, fecha_nacimiento, tipo_documento, numero_documento) 
                      VALUES ('$nombre', '$apellido', '$email', '$hashed_password', '$numero_telefono', '$fecha_nacimiento', '$tipo_documento', '$numero_documento')";
            
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                // 4. Éxito: Crear registro base en la tabla 'curriculums'
                $id_talento_insertado = mysqli_insert_id($conn);
                
                $query_cv_base = "INSERT INTO curriculums (usuario_id) VALUES ($id_talento_insertado)";
                mysqli_query($conn, $query_cv_base);
                
                $_SESSION['success_message'] = "Registro exitoso. Ahora puedes iniciar sesión.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Error al guardar el registro: " . mysqli_error($conn); 
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>TalenteAR - Registrarse</title>
    <link rel="stylesheet" type="text/css" href="../css/register.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8"/>
</head>
<body>
    <div class="navbar">
        <div class="navbar-title">
            <div class="imagenesflechas">
                <a href="#" class="flecha-link" onclick="goBack()">
                    <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás" />
                    <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás" />
                </a>
            </div>
            <a href="../html/index.html" class="navbar-title">
                <img class="maleta" src="../../imagenes/logotalentear.png" alt="" />
                <h2 class="title">TalenteAR</h2>
            </a>
        </div>
        <div class="box-container">
            <a href="../../TalenteARpartners/html/partners.html" class="box redirect">
                Soy una agencia
            </a>
        </div>
    </div>
    <div class="container">
        <h2>Registro de Usuarios</h2>
        <form action="register.php" method="POST">
            <fieldset>
                <legend>Datos personales</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($nombre ?? ''); ?>" maxlength="50" /> 
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" required value="<?php echo htmlspecialchars($apellido ?? ''); ?>" maxlength="50" />
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_telefono">Número de teléfono:</label>
                        <input type="tel" id="numero_telefono" name="numero_telefono" required value="<?php echo htmlspecialchars($numero_telefono ?? ''); ?>" maxlength="15" />
                    </div>
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de nacimiento:</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required value="<?php echo htmlspecialchars($fecha_nacimiento ?? ''); ?>" /> </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>" maxlength="100" />
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required minlength="8" maxlength="32" />
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="confirm_password">Confirmar contraseña:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" maxlength="32" />
                    </div>
                    <div class="form-group">
                        
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_documento">Tipo de documento:</label>
                        <div class="select-wrapper">
                            <select id="tipo_documento" name="tipo_documento">
                                <option value="" disabled <?php echo empty($tipo_documento) ? 'selected' : ''; ?>>Seleccione un tipo de documento</option>
                                <option value="Cedula de identidad" <?php echo ($tipo_documento ?? '') == 'Cedula de identidad' ? 'selected' : ''; ?>>Cedula de identidad</option>
                                <option value="L.E." <?php echo ($tipo_documento ?? '') == 'L.E.' ? 'selected' : ''; ?>>L.E.</option>
                                <option value="Pasaporte" <?php echo ($tipo_documento ?? '') == 'Pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                                <option value="L.C." <?php echo ($tipo_documento ?? '') == 'L.C.' ? 'selected' : ''; ?>>L.C.</option>
                                <option value="DNI" <?php echo ($tipo_documento ?? '') == 'DNI' ? 'selected' : ''; ?>>DNI</option>
                            </select>
                            <div class="arrow"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="numero_documento">Número de documento:
                            <span style="color: rgb(140, 140, 146); font-size: 10px">(sin espacios ni puntos)</span>
                        </label>
                        <input type="text" id="numero_documento" name="numero_documento" required value="<?php echo htmlspecialchars($numero_documento ?? ''); ?>" maxlength="10" />
                    </div>
                </div>
            </fieldset>
            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required <?php echo ($terms ?? false) ? 'checked' : ''; ?>/>
                <label for="terms" class="labelterms">Acepto los <a class="linkredirect" href="../html/terminosycondiciones.html">terminos y condiciones</a>.</label>
            </div>
            <input class="sumbit" type="submit" value="Registrarse"/>

            <?php if (isset($error)): ?>
                <p style="color: red; font-weight: bold; margin-top: 15px;"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <p>¿Ya tienes una cuenta? <a class="linkredirect" href="login.php">Iniciar sesión</a></p>
        </form>
    </div>
    <footer class="footer">
        <div class="social-media">
            <a href="https://www.instagram.com/TalenteARtec4"><img src="../../imagenes/instagram-alt-logo-108.png"/></a>
            <a href="https://www.linkedin.com/in/TalenteAR/"><img src="../../imagenes/linkedin-logo-108.png"/></a>
            <a href="https://www.facebook.com/profile.php?id=61553049380666"><img src="../../imagenes/facebook-circle-logo-108.png"/></a>
        </div>
        <div class="footer-links">
            <a href="../html/terminosycondiciones.html">Términos y Condiciones</a><b>-</b>
            <a href="../html/politic.html">&nbsp;Política de Privacidad</a><b>-</b>
            <a href="../html/address.html">&nbsp;Dirección física</a>
        </div>
        <div class="footer-text">TalenteAR® - Todos los derechos reservados</div>
    </footer>
</body>
<script>
    function goBack() {
        window.history.back();
    }
</script>
</html>