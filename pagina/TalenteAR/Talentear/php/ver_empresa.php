<?php
session_name("PARTNERS_SESION");
session_start();
$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar: " . mysqli_connect_error());
}

if (!isset($_SESSION["email"])) {
    header("Location: error.php?code=401");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_agencia'])) {
    $email_agencia = mysqli_real_escape_string($conn, $_POST['email_agencia']);
    $query = "SELECT * FROM agencias WHERE email_agencia = '$email_agencia'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $empresa = mysqli_fetch_assoc($result);
        echo "<h1>{$empresa['nombre_agencia']}</h1>";
        echo "<p><strong>Ubicación:</strong> {$empresa['ubicacion_agencia']}</p>";
        echo "<p><strong>Teléfono:</strong> {$empresa['numero_telefono_agencia']}</p>";
        echo "<p><strong>Email:</strong> {$empresa['email_agencia']}</p>";
    } else {
        echo "<p>No se encontraron talentos de la empresa.</p>";
    }
} else {
    echo "<p>Error: solicitud inválida.</p>";
}

if (isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($conn, "UPDATE notificaciones SET leida = 1 WHERE notificacion_id = $notif_id");
}


mysqli_close($conn);
?>
