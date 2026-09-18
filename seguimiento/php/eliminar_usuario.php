<?php
include 'conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        //header("Location: https://apps.melonesoilterminal.com/compras/seguimiento/view/control_user.php");
        //header("Location: http://localhost/compras/seguimiento/view/control_user.php");
    } else {
        echo "Error al eliminar: " . $stmt->error;
    }
}
?>
