document.getElementById("registerLink").addEventListener("click", function(event) {
    event.preventDefault(); 
    mostrarAlerta();
});
function mostrarAlerta() {
    Swal.fire({
        title: '',
        text: 'Para acceder a esta opción, tienes que registrarte. ¿Deseas registrarte?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si, deseo registrarme',
        cancelButtonText: 'No, volver'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../php/register.php';
        }
    });
}
