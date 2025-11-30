<?php
// 1. Asigna un nombre de sesión único para Partners
session_name("PARTNERS_SESION");
session_start();

// 2. Destruye la sesión (solo la de Partners)
session_destroy();

// 3. Redirige
header("Location: [Ruta de redirección de Partners]"); // Ajusta la ruta aquí
exit();
?>