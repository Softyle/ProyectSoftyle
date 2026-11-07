/* =====================================================
   INICIO DE SESIÓN
   Mostrar / ocultar la contraseña y evitar doble envío.
   ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('togglePassword');
    var campo = document.getElementById('password');
    if (boton && campo) {
        boton.addEventListener('click', function () {
            var oculta = campo.getAttribute('type') === 'password';
            campo.setAttribute('type', oculta ? 'text' : 'password');
            boton.innerHTML = oculta ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            boton.setAttribute('aria-label', oculta ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    }

    var formulario = document.getElementById('loginForm');
    var ingresar = document.getElementById('loginButton');
    if (formulario && ingresar) {
        formulario.addEventListener('submit', function () {
            ingresar.disabled = true;
            ingresar.innerHTML = '<span>Ingresando...</span> <i class="bi bi-arrow-repeat"></i>';
        });
    }
});
