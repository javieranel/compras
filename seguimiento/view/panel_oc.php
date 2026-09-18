<?php

session_start(); // Iniciar sesión

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}




require_once __DIR__ . '/../auth.php';
include '../php/conexion.php';

$consulta = "SELECT * FROM compras_detalle 
             WHERE alertas_dias > 15";
$resultado = $con->query($consulta);
?>

<?php include '../includes/navbar.php'; ?>

<style>
 body {
    font-family: 'Inter', sans-serif;
    background-color: #f5f7fa;
    padding-top: 90px;
    font-size: 13px;
  }

  .main-content {
    margin-top: 90px; /* sube un poco más si aún se tapa */
  }
</style>


<body style="margin-top: 80px;">



<div class="container mt-5">
  <h3>Órdenes de Compra Pendientes</h3>
  <table id="tablaOC" class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th># OC</th>
        <th>Categoría</th>
        <th>Área</th>
        <th>Solicitante</th>
        <th>Días sin cerrar</th>
        <th>Observación</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $hoy = date('Y-m-d');
        $sql = "SELECT * FROM compras_detalle 
                WHERE (llego_terminal = 'No' AND alertas_dias > 15)";
        $res = $con->query($sql);
        while($row = $res->fetch_assoc()) {
          $dias = $row['alertas_dias'];
          $alerta = ($dias >= 15) ? 'table-danger' : (($dias >= 7) ? 'table-warning' : '');

          echo "<tr class='$alerta'>";
          echo "<td>{$row['orden_compra']}</td>";
          echo "<td>{$row['categoria']}</td>";
          echo "<td>{$row['area']}</td>";
          echo "<td>{$row['solicitante']}</td>";
          echo "<td>{$dias}</td>";
          echo "<td><button class='btn btn-sm btn-primary' onclick='abrirModal({$row['id']})'>Comentar</button></td>";
          echo "</tr>";
        }
      ?>
    </tbody>
  </table>
</div>




</body>

<?php   include '../includes/footer.php' ?>
<script>
  $(document).ready(function () {
    $('#tablaOC').DataTable();
  });

  function abrirModal(id) {
    Swal.fire({
      title: 'Agregar observación',
      input: 'text',
      inputLabel: 'Escribe tu comentario',
      inputPlaceholder: 'Motivo de demora...',
      showCancelButton: true,
      confirmButtonText: 'Guardar',
      cancelButtonText: 'Cancelar',
      preConfirm: (comentario) => {
        if (!comentario) {
          Swal.showValidationMessage('Debes escribir algo');
        }
        return comentario;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('../php/procesar_observacion.php', {
          id: id,
          comentario: result.value
        }, function(response) {
          Swal.fire('Guardado', response, 'success').then(() => {
            location.reload();
          });
        });
      }
    });
  }
</script>

</html>
