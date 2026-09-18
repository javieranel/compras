<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}




require_once __DIR__ . '/../auth.php';
include '../php/conexion.php';


/*
|--------------------------------------------------------------------------
| CONSULTA DE SEGUIMIENTOS DE ATRASOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        compra_id,
        fecha_reporte,
        solicitante,
        correo_solicitante,
        categoria,
        fecha_solicitud,
        dias_transcurridos,
        limite_dias,
        dias_atraso,
        motivo,
        explicacion,
        accion_tomada,
        correo_enviado,
        fecha_correo,
        estado,
        usuario_registro,
        comentario
    FROM seguimiento_atrasos
    ORDER BY fecha_reporte DESC
";

$resultado = $con->query($sql);

if (!$resultado) {
    die("Error al consultar seguimiento_atrasos: " . $con->error);
}

$totalRegistros = $resultado->num_rows;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Listado de Alertas</title>


    <!-- =========================================================
         BOOTSTRAP
    ========================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =========================================================
         BOOTSTRAP ICONS
    ========================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =========================================================
         DATATABLES
    ========================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css"
    >


    <style>

        body {
            background-color: #f4f6f9;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR
        |--------------------------------------------------------------------------
        */

        .contenedor-principal {
            padding: 25px;
        }


        /*
        |--------------------------------------------------------------------------
        | TITULO
        |--------------------------------------------------------------------------
        */

        .titulo-principal {
            font-weight: 700;
            color: #212529;
        }

        .subtitulo {
            color: #6c757d;
        }


        /*
        |--------------------------------------------------------------------------
        | TARJETA
        |--------------------------------------------------------------------------
        */

        .card-principal {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header-alertas {
            background: linear-gradient(
                135deg,
                #dc3545,
                #b02a37
            );

            color: white;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLA
        |--------------------------------------------------------------------------
        */

        #tablaAlertas thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        #tablaAlertas tbody td {
            vertical-align: middle;
        }


        /*
        |--------------------------------------------------------------------------
        | MOTIVO
        |--------------------------------------------------------------------------
        */

        .motivo-atraso {
            min-width: 260px;
            max-width: 350px;
        }

        .motivo-titulo {
            font-weight: 600;
            color: #dc3545;
        }

        .motivo-explicacion {
            color: #6c757d;
            font-size: 0.82rem;
            margin-top: 4px;
        }


        /*
        |--------------------------------------------------------------------------
        | DIAS DE ATRASO
        |--------------------------------------------------------------------------
        */

        .dias-atraso {
            color: #dc3545;
            font-weight: 700;
            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | CORREO
        |--------------------------------------------------------------------------
        */

        .correo-enviado {
            color: #198754;
            font-weight: 600;
            white-space: nowrap;
        }

        .correo-no-enviado {
            color: #dc3545;
            font-weight: 600;
            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .detalle-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .detalle-valor {
            font-size: 0.95rem;
            color: #212529;
        }


        .detalle-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            min-height: 50px;
            white-space: pre-wrap;
        }


        /*
        |--------------------------------------------------------------------------
        | ICONO DE TRAZABILIDAD
        |--------------------------------------------------------------------------
        */

        .icono-trazabilidad {
            width: 42px;
            height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: rgba(255,255,255,0.18);

            font-size: 1.3rem;
        }


        /*
        |--------------------------------------------------------------------------
        | BADGE
        |--------------------------------------------------------------------------
        */

        .badge {
            font-weight: 500;
        }

    </style>

</head>


<body>


<div class="container-fluid contenedor-principal">


    <!-- =========================================================
         ENCABEZADO
    ========================================================== -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h2 class="titulo-principal mb-1">

                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>

                Listado de Alertas

            </h2>

            <div class="subtitulo">

                Seguimiento y trazabilidad de atrasos registrados

            </div>

        </div>


        <div class="mt-3 mt-md-0">

            <a
                href="/compras/seguimiento/view/alertas.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Volver

            </a>

        </div>

    </div>



    <!-- =========================================================
         TARJETA PRINCIPAL
    ========================================================== -->

    <div class="card card-principal shadow-sm">


        <!-- HEADER -->

        <div class="card-header header-alertas py-3">

            <div class="d-flex align-items-center">

                <div class="icono-trazabilidad me-3">

                    <i class="bi bi-clock-history"></i>

                </div>


                <div>

                    <h5 class="mb-1">

                        Historial de seguimientos

                    </h5>

                    <small>

                        Registro completo de las alertas y atrasos

                    </small>

                </div>


                <div class="ms-auto">

                    <span class="badge bg-light text-dark fs-6">

                        <?= $totalRegistros ?>

                        registros

                    </span>

                </div>

            </div>

        </div>



        <!-- BODY -->

        <div class="card-body">


            <!-- =================================================
                 TABLA
            ================================================== -->

            <div class="table-responsive">

                <table
                    id="tablaAlertas"
                    class="table table-hover table-striped align-middle"
                    style="width:100%"
                >


                    <thead class="table-dark">

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Compra
                            </th>

                            <th>
                                Solicitante
                            </th>

                            <th>
                                Categoría
                            </th>

                            <th>
                                Fecha reporte
                            </th>

                            <th>
                                ¿Por qué está atrasado?
                            </th>

                            <th>
                                Días atraso
                            </th>

                            <th>
                                Correo
                            </th>

                            <th>
                                Fecha correo
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Usuario
                            </th>

                            <th>
                                Detalle
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while ($fila = $resultado->fetch_assoc()): ?>


                        <tr>


                            <!-- =================================
                                 ID
                            ================================== -->

                            <td>

                                <span class="badge bg-secondary">

                                    #

                                    <?= htmlspecialchars($fila['id']) ?>

                                </span>

                            </td>



                            <!-- =================================
                                 COMPRA
                            ================================== -->

                            <td>

                                <a
                                    href="/compras/seguimiento/view/detalle_compra.php?id=<?= urlencode($fila['compra_id']) ?>"
                                    class="fw-bold text-decoration-none"
                                    title="Ver compra"
                                >

                                    <i class="bi bi-cart-check me-1"></i>

                                    #

                                    <?= htmlspecialchars($fila['compra_id']) ?>

                                </a>

                            </td>



                            <!-- =================================
                                 SOLICITANTE
                            ================================== -->

                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $fila['solicitante'] ?: 'No registrado'
                                    ) ?>

                                </div>


                                <?php if (!empty($fila['correo_solicitante'])): ?>

                                    <small class="text-muted">

                                        <i class="bi bi-envelope me-1"></i>

                                        <?= htmlspecialchars(
                                            $fila['correo_solicitante']
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>



                            <!-- =================================
                                 CATEGORIA
                            ================================== -->

                            <td>

                                <?php

                                $categoria = trim(
                                    $fila['categoria'] ?? ''
                                );

                                $categoriaLower = strtolower(
                                    $categoria
                                );


                                if (
                                    strpos(
                                        $categoriaLower,
                                        'import'
                                    ) !== false
                                ) {

                                    $colorCategoria = 'primary';

                                } elseif (
                                    strpos(
                                        $categoriaLower,
                                        'servicio'
                                    ) !== false
                                ) {

                                    $colorCategoria = 'info';

                                } elseif (
                                    strpos(
                                        $categoriaLower,
                                        'local'
                                    ) !== false
                                ) {

                                    $colorCategoria = 'success';

                                } else {

                                    $colorCategoria = 'warning';

                                }

                                ?>


                                <span
                                    class="badge bg-<?= $colorCategoria ?>"
                                >

                                    <?= htmlspecialchars(
                                        $categoria ?: 'Sin categoría'
                                    ) ?>

                                </span>

                            </td>



                            <!-- =================================
                                 FECHA REPORTE
                            ================================== -->

                            <td>

                                <?php

                                if (!empty($fila['fecha_reporte'])) {

                                    echo htmlspecialchars(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $fila['fecha_reporte']
                                            )
                                        )
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>



                            <!-- =================================
                                 MOTIVO DEL ATRASO
                            ================================== -->

                            <td class="motivo-atraso">


                                <?php if (!empty($fila['motivo'])): ?>

                                    <div class="motivo-titulo">

                                        <i class="bi bi-exclamation-circle-fill me-1"></i>

                                        <?= htmlspecialchars(
                                            $fila['motivo']
                                        ) ?>

                                    </div>


                                    <?php if (!empty($fila['explicacion'])): ?>

                                        <div
                                            class="motivo-explicacion"
                                            title="<?= htmlspecialchars($fila['explicacion']) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                mb_strimwidth(
                                                    $fila['explicacion'],
                                                    0,
                                                    120,
                                                    '...'
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                <?php else: ?>

                                    <span class="text-muted">

                                        <i class="bi bi-dash-circle me-1"></i>

                                        Sin motivo registrado

                                    </span>

                                <?php endif; ?>


                            </td>



                            <!-- =================================
                                 DIAS ATRASO
                            ================================== -->

                            <td>

                                <?php

                                $diasAtraso = (int)(
                                    $fila['dias_atraso'] ?? 0
                                );

                                ?>


                                <?php if ($diasAtraso > 0): ?>

                                    <span class="dias-atraso">

                                        <i class="bi bi-clock-history me-1"></i>

                                        <?= $diasAtraso ?>

                                        días

                                    </span>

                                <?php else: ?>

                                    <span class="text-success">

                                        <i class="bi bi-check-circle me-1"></i>

                                        0 días

                                    </span>

                                <?php endif; ?>


                            </td>



                            <!-- =================================
                                 CORREO
                            ================================== -->

                            <td>

                                <?php

                                $correoEnviado = strtolower(
                                    trim(
                                        $fila['correo_enviado'] ?? ''
                                    )
                                );


                                $enviado =
                                    $correoEnviado === 'si' ||
                                    $correoEnviado === 'sí' ||
                                    $correoEnviado === 'enviado' ||
                                    $correoEnviado === '1';

                                ?>


                                <?php if ($enviado): ?>

                                    <span class="correo-enviado">

                                        <i class="bi bi-envelope-check-fill me-1"></i>

                                        Enviado

                                    </span>

                                <?php else: ?>

                                    <span class="correo-no-enviado">

                                        <i class="bi bi-envelope-x-fill me-1"></i>

                                        No enviado

                                    </span>

                                <?php endif; ?>


                            </td>



                            <!-- =================================
                                 FECHA CORREO
                            ================================== -->

                            <td>

                                <?php

                                if (!empty($fila['fecha_correo'])) {

                                    echo htmlspecialchars(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $fila['fecha_correo']
                                            )
                                        )
                                    );

                                } else {

                                    echo '<span class="text-muted">-</span>';

                                }

                                ?>

                            </td>



                            <!-- =================================
                                 ESTADO
                            ================================== -->

                            <td>

                                <?php

                                $estado = trim(
                                    $fila['estado'] ?? 'Pendiente'
                                );

                                $estadoLower = strtolower(
                                    $estado
                                );


                                if (
                                    strpos(
                                        $estadoLower,
                                        'complet'
                                    ) !== false ||
                                    strpos(
                                        $estadoLower,
                                        'cerr'
                                    ) !== false
                                ) {

                                    $colorEstado = 'success';

                                } elseif (
                                    strpos(
                                        $estadoLower,
                                        'proceso'
                                    ) !== false
                                ) {

                                    $colorEstado = 'primary';

                                } elseif (
                                    strpos(
                                        $estadoLower,
                                        'cancel'
                                    ) !== false
                                ) {

                                    $colorEstado = 'dark';

                                } else {

                                    $colorEstado = 'warning';

                                }

                                ?>


                                <span
                                    class="badge bg-<?= $colorEstado ?>"
                                >

                                    <?= htmlspecialchars(
                                        $estado
                                    ) ?>

                                </span>


                            </td>



                            <!-- =================================
                                 USUARIO
                            ================================== -->

                            <td>

                                <span
                                    title="Usuario que registró el seguimiento"
                                >

                                    <i class="bi bi-person-circle me-1"></i>

                                    <?= htmlspecialchars(
                                        $fila['usuario_registro']
                                        ?: 'N/A'
                                    ) ?>

                                </span>

                            </td>



                            <!-- =================================
                                 DETALLE
                            ================================== -->

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Ver trazabilidad completa"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetalle"
                                    onclick='verDetalle(
                                        <?= json_encode(
                                            $fila,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        ) ?>
                                    )'
                                >

                                    <i class="bi bi-eye-fill"></i>

                                </button>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        </div>

    </div>


</div>



<!-- =========================================================
     MODAL DETALLE / TRAZABILIDAD
========================================================== -->

<div
    class="modal fade"
    id="modalDetalle"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
    >


        <div class="modal-content">


            <!-- HEADER -->

            <div class="modal-header bg-dark text-white">

                <div>

                    <h5 class="modal-title">

                        <i class="bi bi-clock-history me-2"></i>

                        Trazabilidad del seguimiento

                    </h5>

                    <small class="opacity-75">

                        Información completa del atraso registrado

                    </small>

                </div>


                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                ></button>

            </div>



            <!-- BODY -->

            <div class="modal-body">


                <!-- =============================================
                     INFORMACIÓN GENERAL
                ============================================== -->

                <h6 class="border-bottom pb-2 mb-3">

                    <i class="bi bi-info-circle me-2"></i>

                    Información general

                </h6>


                <div class="row g-3">


                    <div class="col-md-3">

                        <div class="detalle-label">

                            ID Seguimiento

                        </div>

                        <div
                            id="detalle_id"
                            class="detalle-valor fw-bold"
                        ></div>

                    </div>


                    <div class="col-md-3">

                        <div class="detalle-label">

                            ID Compra

                        </div>

                        <div
                            id="detalle_compra"
                            class="detalle-valor fw-bold text-primary"
                        ></div>

                    </div>


                    <div class="col-md-6">

                        <div class="detalle-label">

                            Fecha del reporte

                        </div>

                        <div
                            id="detalle_fecha_reporte"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-6">

                        <div class="detalle-label">

                            Solicitante

                        </div>

                        <div
                            id="detalle_solicitante"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-6">

                        <div class="detalle-label">

                            Correo del solicitante

                        </div>

                        <div
                            id="detalle_correo"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Categoría

                        </div>

                        <div
                            id="detalle_categoria"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Fecha de solicitud

                        </div>

                        <div
                            id="detalle_fecha_solicitud"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Usuario que registró

                        </div>

                        <div
                            id="detalle_usuario"
                            class="detalle-valor"
                        ></div>

                    </div>


                </div>



                <!-- =============================================
                     TIEMPOS
                ============================================== -->

                <h6 class="border-bottom pb-2 mb-3 mt-4">

                    <i class="bi bi-stopwatch me-2"></i>

                    Control de tiempo

                </h6>


                <div class="row g-3">


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Días transcurridos

                        </div>

                        <div
                            id="detalle_dias_transcurridos"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Límite permitido

                        </div>

                        <div
                            id="detalle_limite"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Días de atraso

                        </div>

                        <div
                            id="detalle_atraso"
                            class="detalle-valor text-danger fw-bold"
                        ></div>

                    </div>


                    <div class="col-md-4">

                        <div class="detalle-label">

                            Estado

                        </div>

                        <div
                            id="detalle_estado"
                            class="detalle-valor"
                        ></div>

                    </div>


                </div>



                <!-- =============================================
                     MOTIVO DEL ATRASO
                ============================================== -->

                <h6 class="border-bottom pb-2 mb-3 mt-4">

                    <i class="bi bi-question-circle me-2"></i>

                    ¿Por qué está atrasado?

                </h6>


                <div class="row g-3">


                    <div class="col-12">

                        <div class="detalle-label">

                            Motivo

                        </div>

                        <div
                            id="detalle_motivo"
                            class="detalle-box text-danger fw-semibold"
                        ></div>

                    </div>


                    <div class="col-12">

                        <div class="detalle-label">

                            Explicación

                        </div>

                        <div
                            id="detalle_explicacion"
                            class="detalle-box"
                        ></div>

                    </div>


                </div>



                <!-- =============================================
                     ACCIÓN TOMADA
                ============================================== -->

                <h6 class="border-bottom pb-2 mb-3 mt-4">

                    <i class="bi bi-check2-square me-2"></i>

                    Acción tomada

                </h6>


                <div class="row g-3">


                    <div class="col-12">

                        <div
                            id="detalle_accion"
                            class="detalle-box"
                        ></div>

                    </div>


                    <div class="col-12">

                        <div class="detalle-label">

                            Comentario adicional

                        </div>

                        <div
                            id="detalle_comentario"
                            class="detalle-box"
                        ></div>

                    </div>


                </div>



                <!-- =============================================
                     CORREO
                ============================================== -->

                <h6 class="border-bottom pb-2 mb-3 mt-4">

                    <i class="bi bi-envelope me-2"></i>

                    Notificación por correo

                </h6>


                <div class="row g-3">


                    <div class="col-md-6">

                        <div class="detalle-label">

                            Estado del correo

                        </div>

                        <div
                            id="detalle_correo_enviado"
                            class="detalle-valor"
                        ></div>

                    </div>


                    <div class="col-md-6">

                        <div class="detalle-label">

                            Fecha y hora del envío

                        </div>

                        <div
                            id="detalle_fecha_correo"
                            class="detalle-valor"
                        ></div>

                    </div>


                </div>


            </div>



            <!-- FOOTER -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    <i class="bi bi-x-lg me-1"></i>

                    Cerrar

                </button>

            </div>


        </div>

    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
========================================================== -->


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>



<!-- jQuery -->

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>



<!-- DataTables -->

<script
    src="https://cdn.datatables.net/2.1.8/js/dataTables.js">
</script>


<script
    src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js">
</script>



<script>


/*
|--------------------------------------------------------------------------
| DATATABLE
|--------------------------------------------------------------------------
*/

$(document).ready(function () {


    $('#tablaAlertas').DataTable({

        language: {

            url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'

        },


        pageLength: 10,


        lengthMenu: [

            [10, 25, 50, 100, -1],

            [10, 25, 50, 100, "Todos"]

        ],


        order: [

            [4, 'desc']

        ],


        columnDefs: [

            {
                orderable: false,
                targets: 11
            }

        ],


        responsive: false,


        autoWidth: false

    });

});



/*
|--------------------------------------------------------------------------
| MOSTRAR DETALLE
|--------------------------------------------------------------------------
*/

function verDetalle(data) {


    /*
    |--------------------------------------------------------------------------
    | INFORMACIÓN GENERAL
    |--------------------------------------------------------------------------
    */

    document.getElementById('detalle_id').textContent =

        '#' + (data.id ?? '');


    document.getElementById('detalle_compra').textContent =

        '#' + (data.compra_id ?? '');


    document.getElementById('detalle_fecha_reporte').textContent =

        data.fecha_reporte ?? '-';


    document.getElementById('detalle_solicitante').textContent =

        data.solicitante || 'No registrado';


    document.getElementById('detalle_correo').textContent =

        data.correo_solicitante || 'No registrado';


    document.getElementById('detalle_categoria').textContent =

        data.categoria || 'Sin categoría';


    document.getElementById('detalle_fecha_solicitud').textContent =

        data.fecha_solicitud || '-';


    document.getElementById('detalle_usuario').textContent =

        data.usuario_registro || 'No registrado';



    /*
    |--------------------------------------------------------------------------
    | TIEMPOS
    |--------------------------------------------------------------------------
    */

    document.getElementById('detalle_dias_transcurridos').textContent =

        (data.dias_transcurridos ?? 0) + ' días';


    document.getElementById('detalle_limite').textContent =

        (data.limite_dias ?? 0) + ' días';


    document.getElementById('detalle_atraso').textContent =

        (data.dias_atraso ?? 0) + ' días';


    document.getElementById('detalle_estado').textContent =

        data.estado || 'Pendiente';



    /*
    |--------------------------------------------------------------------------
    | MOTIVO
    |--------------------------------------------------------------------------
    */

    document.getElementById('detalle_motivo').textContent =

        data.motivo || 'No se registró un motivo.';


    document.getElementById('detalle_explicacion').textContent =

        data.explicacion || 'No se registró una explicación.';



    /*
    |--------------------------------------------------------------------------
    | ACCIÓN
    |--------------------------------------------------------------------------
    */

    document.getElementById('detalle_accion').textContent =

        data.accion_tomada || 'No se registró una acción.';


    document.getElementById('detalle_comentario').textContent =

        data.comentario || 'Sin comentario.';



    /*
    |--------------------------------------------------------------------------
    | CORREO
    |--------------------------------------------------------------------------
    */

    let correo = data.correo_enviado || 'No';


    if (

        correo.toLowerCase() === 'si' ||

        correo.toLowerCase() === 'sí' ||

        correo.toLowerCase() === 'enviado' ||

        correo === '1'

    ) {

        document.getElementById(
            'detalle_correo_enviado'
        ).innerHTML =

            '<span class="text-success fw-bold">' +

            '<i class="bi bi-envelope-check-fill me-1"></i>' +

            'Correo enviado' +

            '</span>';

    } else {

        document.getElementById(
            'detalle_correo_enviado'
        ).innerHTML =

            '<span class="text-danger fw-bold">' +

            '<i class="bi bi-envelope-x-fill me-1"></i>' +

            'Correo no enviado' +

            '</span>';

    }


    document.getElementById('detalle_fecha_correo').textContent =

        data.fecha_correo || 'No enviado';


}

</script>


</body>

</html>