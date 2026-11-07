/* =====================================================
   SITIO PÚBLICO
===================================================== */

document.addEventListener("DOMContentLoaded", function () {


    /* =================================================
       NAVEGACIÓN SUAVE
    ================================================= */

    const links = document.querySelectorAll(
        'a[href^="#"]'
    );


    links.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const id = this.getAttribute("href");

            if (id === "#") {
                return;
            }


            const section = document.querySelector(id);


            if (section) {

                event.preventDefault();

                section.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });


                // Cerrar menú Bootstrap en celular

                const menu = document.querySelector(
                    "#menuPrincipal"
                );


                if (
                    menu &&
                    menu.classList.contains("show")
                ) {

                    const button =
                        document.querySelector(
                            ".navbar-toggler"
                        );


                    if (button) {
                        button.click();
                    }

                }

            }

        });

    });






    /* =================================================
       VALIDACIÓN DEL FORMULARIO
    ================================================= */

    const form =
        document.querySelector("#contactForm");


    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const nombre =
                    document.querySelector("#nombre");

                const correo =
                    document.querySelector("#correo");

                const mensaje =
                    document.querySelector("#mensaje");


                if (
                    nombre.value.trim().length < 2
                ) {

                    event.preventDefault();

                    alert(
                        form.dataset.errorNombre || "Por favor ingresá un nombre válido."
                    );

                    nombre.focus();

                    return;

                }


                if (
                    mensaje.value.trim().length < 10
                ) {

                    event.preventDefault();

                    alert(
                        form.dataset.errorMensaje || "El mensaje debe tener al menos 10 caracteres."
                    );

                    mensaje.focus();

                    return;

                }

            }
        );

    }



    /* =================================================
       ANIMACIÓN DE TARJETAS
    ================================================= */

    // La animación solo se activa si este script corre (sin JS las tarjetas se ven igual)
    document.documentElement.classList.add("animar");

    const cards =
        document.querySelectorAll(
            ".tarjeta-info, .tarjeta-zona, .tarjeta-galeria"
        );


    const observer =
        new IntersectionObserver(
            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.classList.add(
                            "visible"
                        );

                    }

                });

            },
            {
                threshold: 0.15
            }
        );


    cards.forEach(function (card) {

        observer.observe(card);

    });

});
