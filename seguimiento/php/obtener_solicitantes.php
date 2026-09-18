<?php
include '../php/conexion.php';

if (isset($_POST['area'])) {
    $area = $_POST['area'];
    
    // Traemos nombre y correo
    $stmt = $con->prepare("SELECT solicitante, correo FROM area_solicitante WHERE area = ?");
    $stmt->bind_param("s", $area);
    $stmt->execute();
    $result = $stmt->get_result();

    $solicitantes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitantes[] = [
            'nombre' => $row['solicitante'],
            'correo' => $row['correo']
        ];
    }

    // Indicamos que devolvemos JSON
    header('Content-Type: application/json');
    echo json_encode($solicitantes);
}
?>
