/* =====================================================
   SOFTYLE - SISTEMA DE PICKING
   sistema.js
   ===================================================== */


/* ==============================
   MOSTRAR / OCULTAR CONTRASEÑA
   ============================== */

const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");

if (togglePassword && passwordInput) {

    togglePassword.addEventListener("click", function () {

        const tipoActual = passwordInput.getAttribute("type");

        if (tipoActual === "password") {

            passwordInput.setAttribute("type", "text");

            togglePassword.innerHTML =
                '<i class="bi bi-eye-slash"></i>';

            togglePassword.setAttribute(
                "aria-label",
                "Ocultar contraseña"
            );

        } else {

            passwordInput.setAttribute("type", "password");

            togglePassword.innerHTML =
                '<i class="bi bi-eye"></i>';

            togglePassword.setAttribute(
                "aria-label",
                "Mostrar contraseña"
            );
        }

    });

}


/* ==============================
   BOTÓN DE LOGIN
   ============================== */

const loginForm = document.getElementById("loginForm");
const loginButton = document.getElementById("loginButton");

if (loginForm && loginButton) {

    loginForm.addEventListener("submit", function () {

        loginButton.disabled = true;

        loginButton.innerHTML =
            '<span>Ingresando...</span>' +
            '<i class="bi bi-arrow-repeat"></i>';

    });

}
/* =====================================================
   SISTEMA DE PICKING
   ===================================================== */

const confirmarPicking =
    document.getElementById("confirmarPicking");

if (confirmarPicking) {

    const productos = [
        {
            nombre: "Coca-Cola 1.5L",
            ubicacion: "B-01-09",
            cantidad: "4 unidades"
        },
        {
            nombre: "Agua Mineral 2L",
            ubicacion: "B-02-04",
            cantidad: "6 unidades"
        },
        {
            nombre: "Arroz 1Kg",
            ubicacion: "C-03-12",
            cantidad: "3 unidades"
        },
        {
            nombre: "Fideos 500g",
            ubicacion: "C-04-08",
            cantidad: "5 unidades"
        },
        {
            nombre: "Aceite 900ml",
            ubicacion: "D-01-03",
            cantidad: "2 unidades"
        }
    ];

    let productoActual = 0;

    const nombre =
        document.getElementById("productoNombre");

    const ubicacion =
        document.getElementById("productoUbicacion");

    const cantidad =
        document.getElementById("productoCantidad");

    const progreso =
        document.getElementById("progresoTexto");

    const progressBar =
        document.getElementById("progressBar");

    const numeroProducto =
        document.querySelector(".producto-numero");


    confirmarPicking.addEventListener("click", function () {

        productoActual++;

        if (productoActual >= productos.length) {

            nombre.textContent = "Pedido completado";

            ubicacion.textContent = "✓";

            cantidad.textContent =
                "Todos los productos fueron recogidos";

            numeroProducto.textContent =
                "PICKING FINALIZADO";

            progreso.textContent =
                productos.length + " / " + productos.length;

            progressBar.style.width = "100%";

            confirmarPicking.innerHTML =
                '<i class="bi bi-check-circle"></i> Pedido completado';

            confirmarPicking.disabled = true;

            return;
        }


        const producto =
            productos[productoActual];

        nombre.textContent =
            producto.nombre;

        ubicacion.textContent =
            producto.ubicacion;

        cantidad.textContent =
            producto.cantidad;

        numeroProducto.textContent =
            "PRODUCTO " +
            (productoActual + 1) +
            " DE " +
            productos.length;


        progreso.textContent =
            productoActual +
            " / " +
            productos.length;


        const porcentaje =
            (productoActual / productos.length) * 100;

        progressBar.style.width =
            porcentaje + "%";

    });

}


