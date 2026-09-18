<?php
include 'conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("DELETE FROM compras_detalle WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../view/editar_compra.php");
    } else {
        echo "Error al eliminar: " . $stmt->error;
    }
}
?>
