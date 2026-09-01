/* =====================================================
   SOFTYLE
   JAVASCRIPT
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
       CONFIRMAR PICKING
    ================================================= */

    const botonPicking =
        document.querySelector("#confirmarPicking");


    if (botonPicking) {

        botonPicking.addEventListener(
            "click",
            function () {

                const textoOriginal =
                    this.innerHTML;


                this.innerHTML =
                    '<i class="bi bi-check-circle"></i> Recolección confirmada';


                this.style.background =
                    "#20c95a";


                this.style.color =
                    "#082713";


                setTimeout(function () {

                    botonPicking.innerHTML =
                        textoOriginal;

                    botonPicking.style.background =
                        "";

                    botonPicking.style.color =
                        "";

                }, 3000);

            }
        );

    }



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
                        "Por favor ingresá un nombre válido."
                    );

                    nombre.focus();

                    return;

                }


                if (
                    mensaje.value.trim().length < 10
                ) {

                    event.preventDefault();

                    alert(
                        "El mensaje debe tener al menos 10 caracteres."
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

    const cards =
        document.querySelectorAll(
            ".info-card, .zone-card, .gallery-card"
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
