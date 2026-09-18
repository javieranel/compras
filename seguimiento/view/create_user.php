<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}

include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';

$mensaje = '';
$tipo_mensaje = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if (empty($nombre) || empty($clave)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo_mensaje = "warning";
    } else {
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $con->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $nombre, $claveHash);
            if ($stmt->execute()) {
                $mensaje = "Usuario <strong>" . htmlspecialchars($nombre) . "</strong> creado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "El usuario ya existe o hubo un fallo al crear la cuenta.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error al preparar la consulta.";
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   VARIABLES
========================================================= */
:root {
    --bg-body-from: #0d6efd;
    --bg-body-to: #6610f2;
    --bg-card: #ffffff;
    --bg-input: #ffffff;

    --text-primary: #1a1d23;
    --text-secondary: #6c757d;

    --border-color: #e5e9f0;
    --shadow-card: 0 20px 60px rgba(0, 0, 0, .25);
    --shadow-btn: 0 4px 14px rgba(13, 110, 253, .30);

    --accent-blue: #0d6efd;
    --accent-green: #198754;
    --accent-red: #dc3545;
    --accent-orange: #fd7e14;

    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;

    --transition: 220ms cubic-bezier(.4, 0, .2, 1);
}

/* =========================================================
   BASE
========================================================= */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, var(--bg-body-from), var(--bg-body-to));
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    color: var(--text-primary);
    -webkit-font-smoothing: antialiased;
    position: relative;
    overflow-x: hidden;
}

/* Decoración de fondo (círculos difuminados) */
body::before,
body::after {
    content: "";
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: .35;
    pointer-events: none;
    z-index: 0;
}
body::before {
    width: 400px; height: 400px;
    background: #00c6ff;
    top: -100px; left: -100px;
}
body::after {
    width: 500px; height: 500px;
    background: #ff0080;
    bottom: -150px; right: -150px;
}

/* =========================================================
   CARD PRINCIPAL
========================================================= */
.register-card {
    width: 100%;
    max-width: 900px;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    position: relative;
    z-index: 1;
    animation: fadeInUp .6s cubic-bezier(.4, 0, .2, 1);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* =========================================================
   COLUMNA IMAGEN
========================================================= */
.register-image-col {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    min-height: 100%;
}
.register-image-col img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    position: absolute;
    inset: 0;
}
.register-image-col::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(13,110,253,.55), rgba(102,16,242,.65));
    z-index: 1;
}

.image-overlay-content {
    position: relative;
    z-index: 2;
    padding: 40px 32px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    color: white;
}
.image-overlay-content h3 {
    font-weight: 800;
    font-size: 1.5rem;
    margin-bottom: 8px;
    letter-spacing: -.5px;
}
.image-overlay-content p {
    opacity: .9;
    font-size: .9rem;
    margin: 0;
    line-height: 1.5;
}
.image-overlay-content .image-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.20);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
    backdrop-filter: blur(8px);
}

/* =========================================================
   COLUMNA FORMULARIO
========================================================= */
.register-form-col {
    padding: 48px 44px;
    background: var(--bg-card);
}

.form-header {
    margin-bottom: 28px;
    text-align: center;
}
.form-header .form-icon {
    width: 62px; height: 62px;
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    border-radius: var(--radius-lg);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    margin-bottom: 16px;
    box-shadow: var(--shadow-btn);
}
.form-header h4 {
    font-weight: 800;
    font-size: 1.5rem;
    margin: 0 0 6px;
    letter-spacing: -.5px;
    color: var(--text-primary);
}
.form-header p {
    color: var(--text-secondary);
    font-size: .85rem;
    margin: 0;
}

/* =========================================================
   FORMULARIO
========================================================= */
.form-label {
    font-weight: 600;
    font-size: 12.5px;
    color: var(--text-primary);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.form-label i {
    color: var(--accent-blue);
    font-size: 13px;
}

.input-wrapper {
    position: relative;
}

.form-control {
    border-radius: var(--radius-md);
    padding: 12px 14px;
    font-size: 13.5px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all var(--transition);
}
.form-control:focus {
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, .10);
    background: var(--bg-input);
}
.form-control::placeholder {
    color: #a8b0ba;
}

/* Botón ver/ocultar contraseña */
.toggle-password {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    background: transparent;
    border: 0;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 4px;
    font-size: 16px;
    transition: color var(--transition);
    line-height: 1;
}
.toggle-password:hover {
    color: var(--accent-blue);
}

/* =========================================================
   BOTONES
========================================================= */
.btn-register {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    border: 0;
    border-radius: var(--radius-md);
    padding: 13px 24px;
    font-weight: 700;
    font-size: 14px;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: var(--shadow-btn);
    transition: all var(--transition);
    margin-top: 8px;
}
.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(13, 110, 253, .45);
    color: white;
}

.btn-back {
    background: transparent;
    color: var(--text-secondary);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 11px 20px;
    font-weight: 600;
    font-size: 13px;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all var(--transition);
    margin-top: 10px;
    text-decoration: none;
}
.btn-back:hover {
    background: #f1f5f9;
    color: var(--text-primary);
    border-color: var(--border-color);
}

/* =========================================================
   ALERTAS
========================================================= */
.alert-modern {
    border: 0;
    border-radius: var(--radius-md);
    padding: 14px 18px;
    font-size: 13px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    animation: fadeInUp .4s cubic-bezier(.4, 0, .2, 1);
}
.alert-modern i {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 1px;
}
.alert-modern strong { font-weight: 700; }

.alert-modern.alert-success-modern {
    background: rgba(25, 135, 84, .10);
    color: var(--accent-green);
}
.alert-modern.alert-error-modern {
    background: rgba(220, 53, 69, .10);
    color: var(--accent-red);
}
.alert-modern.alert-warning-modern {
    background: rgba(253, 126, 20, .10);
    color: var(--accent-orange);
}
.alert-modern.alert-info-modern {
    background: rgba(13, 110, 253, .10);
    color: var(--accent-blue);
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    body { padding: 16px; }
    .register-form-col { padding: 32px 24px; }
    .form-header h4 { font-size: 1.25rem; }
    .form-header .form-icon {
        width: 54px; height: 54px;
        font-size: 24px;
    }
}
</style>
</head>
<body>

<div class="register-card">
    <div class="row g-0">

        <!-- =========================================================
             COLUMNA IMAGEN (oculta en móvil)
        ========================================================= -->
        <div class="col-md-5 d-none d-md-block register-image-col">
            <img src="../img/melones.jpg" alt="Fondo">
            <div class="image-overlay-content">
                <div class="image-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h3>Nuevo Usuario</h3>
                <p>
                    Crea cuentas de acceso al sistema de gestión de compras.
                    Los usuarios podrán iniciar sesión con su nombre y contraseña.
                </p>
            </div>
        </div>

        <!-- =========================================================
             COLUMNA FORMULARIO
        ========================================================= -->
        <div class="col-md-7 register-form-col">

            <div class="form-header">
                <div class="form-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h4>Registrar Nuevo Usuario</h4>
                <p>Completa los datos para crear una cuenta</p>
            </div>

            <?php if (!empty($mensaje)): ?>
                <?php
                // Determinar clase e icono según el tipo
                switch ($tipo_mensaje) {
                    case 'success':
                        $alerta_class = 'alert-success-modern';
                        $alerta_icon  = 'check-circle-fill';
                        break;
                    case 'error':
                        $alerta_class = 'alert-error-modern';
                        $alerta_icon  = 'x-circle-fill';
                        break;
                    case 'warning':
                        $alerta_class = 'alert-warning-modern';
                        $alerta_icon  = 'exclamation-triangle-fill';
                        break;
                    default:
                        $alerta_class = 'alert-info-modern';
                        $alerta_icon  = 'info-circle-fill';
                }
                ?>
                <div class="alert-modern <?= $alerta_class ?>" role="alert">
                    <i class="bi bi-<?= $alerta_icon ?>"></i>
                    <div><?= $mensaje ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        <i class="bi bi-person-fill"></i>
                        Nombre de usuario
                    </label>
                    <input type="text"
                           class="form-control"
                           id="nombre"
                           name="nombre"
                           placeholder="Ej: rdelgado"
                           autocomplete="off"
                           required>
                </div>

                <div class="mb-3">
                    <label for="clave" class="form-label">
                        <i class="bi bi-lock-fill"></i>
                        Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password"
                               class="form-control"
                               id="clave"
                               name="clave"
                               placeholder="••••••••"
                               autocomplete="new-password"
                               required>
                        <button type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Mostrar u ocultar contraseña">
                            <i class="bi bi-eye-fill" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="bi bi-person-check-fill"></i>
                    Registrar Usuario
                </button>

                <a href="/compras/seguimiento/view/control_user.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Volver al listado de usuarios
                </a>

            </form>

        </div>

    </div>
</div>

<script>
/* =========================================================
   MOSTRAR / OCULTAR CONTRASEÑA
========================================================= */
(function() {
    const toggle = document.getElementById('togglePassword');
    const icon   = document.getElementById('toggleIcon');
    const input  = document.getElementById('clave');

    if (!toggle || !input) return;

    toggle.addEventListener('click', function() {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    });
})();
</script>

</body>
</html>