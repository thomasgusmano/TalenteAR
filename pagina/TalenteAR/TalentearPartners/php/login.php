<?php
session_name("PARTNERS_SESION");
session_start();
$host = "localhost";
$username = "root";
$password = ""; 
$database = "basedatos"; 
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Error al conectar a la base de talentos: " . mysqli_connect_error());
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_agencia = $_POST["email_agencia"];
    $password_agencia = $_POST["password_agencia"];
    $query = "SELECT * FROM agencias WHERE email_agencia = '$email_agencia'";
    $result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stored_password = $row["password_agencia"];
        if (password_verify($password_agencia, $stored_password)) {
            $_SESSION["email_agencia"] = $email_agencia;
            header("Location: index-logged.php"); 
            exit();
        } else {
            echo "Contraseña incorrecta. Por favor, intenta nuevamente.";
        }
    } else {
        echo "El correo electrónico no está registrado. Por favor, verifícalo o crea una cuenta primero.";
    }
}
mysqli_close($conn);
?>
