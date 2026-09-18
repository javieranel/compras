<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Seguimiento de Compras</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         CHART.JS
    ====================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <style>

        /*
        =========================================================
        VARIABLES
        =========================================================
        */

        :root {

            --navbar-primary: #0d6efd;
            --navbar-secondary: #6f42c1;

            --navbar-hover: rgba(255, 255, 255, 0.12);

            --navbar-active: rgba(255, 255, 255, 0.18);

            --body-bg: #f5f7fb;

        }


        /*
        =========================================================
        BODY
        =========================================================
        */

        body {

            font-family: 'Poppins', sans-serif;

            background-color: var(--body-bg);

            padding-top: 78px;

            font-size: 13px;

            transition:
                background-color 0.3s ease,
                color 0.3s ease;

        }


        /*
        =========================================================
        NAVBAR
        =========================================================
        */

        .navbar-custom {

            min-height: 72px;

            background:
                linear-gradient(
                    135deg,
                    var(--navbar-primary),
                    var(--navbar-secondary)
                );

            box-shadow:
                0 4px 18px rgba(0, 0, 0, 0.15);

            backdrop-filter: blur(10px);

        }


        /*
        =========================================================
        CONTENEDOR NAVBAR
        =========================================================
        */

        .navbar-custom .container-fluid {

            padding-left: 22px;

            padding-right: 22px;

        }


        /*
        =========================================================
        LOGO
        =========================================================
        */

        .navbar-custom .navbar-brand {

            display: flex;

            align-items: center;

            gap: 9px;

            font-size: 1.15rem;

            font-weight: 700;

            color: #ffffff;

            letter-spacing: 0.2px;

            white-space: nowrap;

            transition:
                transform 0.2s ease,
                opacity 0.2s ease;

        }


        .navbar-custom .navbar-brand:hover {

            color: #ffffff;

            transform: translateY(-1px);

            opacity: 0.95;

        }


        /*
        =========================================================
        ICONO DEL LOGO
        =========================================================
        */

        .logo-icon {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                rgba(255, 255, 255, 0.16);

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.15);

            font-size: 1.15rem;

        }


        /*
        =========================================================
        NAV LINKS
        =========================================================
        */

        .navbar-custom .nav-link {

            position: relative;

            display: flex;

            align-items: center;

            gap: 6px;

            color: rgba(255, 255, 255, 0.92);

            font-size: 0.84rem;

            font-weight: 500;

            padding: 9px 12px !important;

            margin: 0 2px;

            border-radius: 9px;

            transition:
                background-color 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease;

        }


        /*
        =========================================================
        HOVER
        =========================================================
        */

        .navbar-custom .nav-link:hover {

            color: #ffffff;

            background-color: var(--navbar-hover);

            transform: translateY(-1px);

        }


        /*
        =========================================================
        ACTIVE
        =========================================================
        */

        .navbar-custom .nav-link.active {

            color: #ffffff;

            background-color: var(--navbar-active);

            font-weight: 600;

        }


        /*
        =========================================================
        ICONOS
        =========================================================
        */

        .navbar-custom .nav-link i {

            font-size: 1rem;

        }


        /*
        =========================================================
        DROPDOWN
        =========================================================
        */

        .navbar-custom .dropdown-menu {

            margin-top: 10px;

            border: none;

            border-radius: 12px;

            padding: 8px;

            min-width: 225px;

            box-shadow:
                0 10px 35px rgba(0, 0, 0, 0.16);

            animation:
                dropdownAnimation 0.18s ease;

        }


        @keyframes dropdownAnimation {

            from {

                opacity: 0;

                transform: translateY(-5px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }


        /*
        =========================================================
        DROPDOWN ITEMS
        =========================================================
        */

        .navbar-custom .dropdown-item {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 9px 11px;

            border-radius: 8px;

            font-size: 0.83rem;

            font-weight: 500;

            color: #343a40;

            transition:
                background-color 0.2s ease,
                transform 0.2s ease;

        }


        .navbar-custom .dropdown-item:hover {

            background-color: #f0f4ff;

            transform: translateX(3px);

        }


        /*
        =========================================================
        DROPDOWN ICONOS
        =========================================================
        */

        .navbar-custom .dropdown-item i {

            width: 20px;

            text-align: center;

            font-size: 1rem;

        }


        /*
        =========================================================
        DIVIDER
        =========================================================
        */

        .navbar-custom .dropdown-divider {

            margin: 6px 4px;

        }


        /*
        =========================================================
        ALERTAS
        =========================================================
        */

        .alertas-link {

            position: relative;

        }


        .alerta-indicador {

            position: absolute;

            top: 5px;

            right: 5px;

            width: 7px;

            height: 7px;

            background-color: #ff4d4f;

            border-radius: 50%;

            border: 2px solid rgba(13, 110, 253, 0.8);

        }


        /*
        =========================================================
        BOTÓN MODO OSCURO
        =========================================================
        */

        #toggleTheme {

            border: 1px solid rgba(255,255,255,0.25);

            background:
                rgba(255,255,255,0.10);

            color: #ffffff;

            border-radius: 8px;

            font-size: 0.78rem;

            padding: 7px 10px;

            transition:
                background-color 0.2s ease,
                transform 0.2s ease;

        }


        #toggleTheme:hover {

            background:
                rgba(255,255,255,0.18);

            transform: translateY(-1px);

        }


        /*
        =========================================================
        TOGGLER
        =========================================================
        */

        .navbar-toggler {

            border:

                1px solid
                rgba(255,255,255,0.35);

            border-radius: 9px;

            padding: 6px 9px;

        }


        .navbar-toggler:focus {

            box-shadow:
                0 0 0 3px
                rgba(255,255,255,0.15);

        }


        /*
        =========================================================
        RESPONSIVE
        =========================================================
        */

        @media (max-width: 991.98px) {

            .navbar-custom .navbar-nav {

                padding-top: 12px;

                padding-bottom: 10px;

            }


            .navbar-custom .nav-link {

                margin: 2px 0;

            }


            .navbar-custom .dropdown-menu {

                margin-top: 3px;

                box-shadow: none;

                border-radius: 9px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================== -->

<nav
    class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top"
>


    <div class="container-fluid">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            class="navbar-brand"
            href="/compras/seguimiento/index.php"
        >

            <span class="logo-icon">

                <i class="bi bi-box-seam-fill"></i>

            </span>


            <span>

                Seguimiento de Compras

            </span>

        </a>



        <!-- =====================================================
             MOBILE BUTTON
        ====================================================== -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menuNav"
            aria-controls="menuNav"
            aria-expanded="false"
            aria-label="Abrir menú"
        >

            <span class="navbar-toggler-icon"></span>

        </button>



        <!-- =====================================================
             MENU
        ====================================================== -->

        <div
            class="collapse navbar-collapse"
            id="menuNav"
        >


            <ul
                class="navbar-nav ms-auto align-items-lg-center"
            >


                <!-- =================================================
                     DASHBOARD
                ================================================== -->

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="/compras/seguimiento/index.php"
                    >

                        <i class="bi bi-speedometer2"></i>

                        Dashboard

                    </a>

                </li>



                <!-- =================================================
                     ORDENES
                ================================================== -->

                <li class="nav-item dropdown">

                    <a
                        class="nav-link dropdown-toggle"
                        href="#"
                        id="ordenesDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <i class="bi bi-file-earmark-text-fill"></i>

                        Órdenes

                    </a>


                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="ordenesDropdown"
                    >


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/formulario.php"
                            >

                                <i class="bi bi-plus-circle text-primary"></i>

                                Ingresar OC

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/editar_compra.php"
                            >

                                <i class="bi bi-pencil-square text-warning"></i>

                                Completar OC

                            </a>

                        </li>


                        <li>

                            <hr class="dropdown-divider">

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/bdd_compras.php"
                            >

                                <i class="bi bi-database-fill-check text-success"></i>

                                Base de datos

                            </a>

                        </li>


                    </ul>

                </li>



                <!-- =================================================
                     LISTADOS
                ================================================== -->

                <li class="nav-item dropdown">

                    <a
                        class="nav-link dropdown-toggle"
                        href="#"
                        id="listadosDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <i class="bi bi-list-check"></i>

                        Listados

                    </a>


                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="listadosDropdown"
                    >


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/area_solicitante.php"
                            >

                                <i class="bi bi-people-fill text-primary"></i>

                                Área / Solicitante

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/proveedores.php"
                            >

                                <i class="bi bi-truck text-success"></i>

                                Proveedores

                            </a>

                        </li>


                    </ul>

                </li>



                <!-- =================================================
                     ALERTAS
                ================================================== -->

                <li class="nav-item dropdown">

                    <a
                        class="nav-link dropdown-toggle alertas-link"
                        href="#"
                        id="alertasDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <i class="bi bi-bell-fill"></i>

                        Alertas

                        <!-- Indicador -->

                        <span class="alerta-indicador"></span>

                    </a>


                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="alertasDropdown"
                    >


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/alertas.php"
                            >

                                <i class="bi bi-exclamation-triangle-fill text-danger"></i>

                                Ver alertas

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/ver_listado_alertas.php"
                            >

                                <i class="bi bi-clock-history text-warning"></i>

                                Historial de alertas

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/ver_listado_alertas.php"
                            >

                                <i class="bi bi-diagram-3-fill text-primary"></i>

                                Trazabilidad

                            </a>

                        </li>


                    </ul>

                </li>



                <!-- =================================================
                     CONFIGURACIÓN
                ================================================== -->

                <li class="nav-item dropdown">

                    <a
                        class="nav-link dropdown-toggle"
                        href="#"
                        id="configuracionDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <i class="bi bi-gear-fill"></i>

                        Configuración

                    </a>


                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="configuracionDropdown"
                    >


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/create_user.php"
                            >

                                <i class="bi bi-person-plus-fill text-primary"></i>

                                Crear usuarios

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="/compras/seguimiento/view/control_user.php"
                            >

                                <i class="bi bi-person-fill-gear text-warning"></i>

                                Control de usuarios

                            </a>

                        </li>


                        <li>

                            <hr class="dropdown-divider">

                        </li>


                        <li>

                            <a
                                class="dropdown-item text-danger"
                                href="/compras/seguimiento/login.php"
                            >

                                <i class="bi bi-box-arrow-right"></i>

                                Cerrar sesión

                            </a>

                        </li>


                    </ul>

                </li>



                <!-- =================================================
                     MODO OSCURO
                ================================================== -->

                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <button
                        id="toggleTheme"
                        type="button"
                        class="btn"
                    >

                        <i class="bi bi-moon-stars-fill me-1"></i>

                        <span id="themeText">
                            Oscuro
                        </span>

                    </button>

                </li>

                


            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     BOOTSTRAP JS
========================================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>



<!-- =========================================================
     MODO OSCURO
========================================================== -->

<script>

const toggleTheme = document.getElementById('toggleTheme');

const themeText = document.getElementById('themeText');


toggleTheme.addEventListener('click', function () {


    const body = document.body;


    const modoOscuro =
        body.classList.toggle('dark-mode');


    if (modoOscuro) {

        body.style.backgroundColor = '#151922';

        body.style.color = '#e9ecef';

        themeText.textContent = 'Claro';

        toggleTheme.innerHTML =
            '<i class="bi bi-sun-fill me-1"></i> Claro';

    } else {

        body.style.backgroundColor = '#f5f7fb';

        body.style.color = '#212529';

        themeText.textContent = 'Oscuro';

        toggleTheme.innerHTML =
            '<i class="bi bi-moon-stars-fill me-1"></i> Oscuro';

    }

});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>