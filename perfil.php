<?php
include 'db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del usuario
$sql = "SELECT nombre, email, foto_perfil FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$foto_perfil = 'path/to/default-avatar.png'; // Ruta por defecto en caso de que no haya foto de perfil

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $sql = "SELECT foto_perfil FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->bind_result($foto_perfil);
    $stmt->fetch();
    $stmt->close();
}

// Manejar la actualización de datos y foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["foto_perfil"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Verificar si el archivo es una imagen
        $check = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $target_file)) {
                // Actualizar la foto de perfil en la base de datos
                $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $target_file, $usuario_id);
                $stmt->execute();
                $stmt->close();
                header("Location: perfil.php"); // Redirige para mostrar la nueva foto
                exit();
            } else {
                echo "Lo siento, hubo un error al subir tu archivo.";
            }
        } else {
            echo "El archivo no es una imagen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .nav-item img {
            width: 40px; /* Ajusta el tamaño según lo necesites */
            height: 40px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Tienda de Deportes</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="productos.php">Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="perfil.php">Perfil</a></li>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">
                            <?php if ($foto_perfil): ?>
                                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de Perfil">
                            <?php else: ?>
                                <img src="path/to/default-avatar.png" alt="Foto de Perfil">
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar sesión</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Iniciar sesión</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Registrar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Perfil de Usuario</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" class="form-control" id="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="foto_perfil">Foto de Perfil:</label>
                <?php if ($user['foto_perfil']): ?>
                    <br><img src="<?php echo htmlspecialchars($user['foto_perfil']); ?>" alt="Foto de perfil" style="width:150px;height:150px;">
                <?php endif; ?>
                <input type="file" class="form-control-file" id="foto_perfil" name="foto_perfil">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Foto</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
