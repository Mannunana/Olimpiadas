<?php
include 'db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_id'];

// Si se ha enviado una solicitud para proceder con la compra
if (isset($_POST['proceed'])) {
    // Aquí agregarás el código para realizar el pedido y actualizar el stock
    // Luego rediriges al usuario de vuelta a la página de productos
    header("Location: productos.php");
    exit();
}

// Si se ha enviado una solicitud para eliminar un producto
if (isset($_POST['remove'])) {
    $producto_id = $_POST['producto_id'];
    $sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $producto_id);
    $stmt->execute();
    $stmt->close();
    header("Location: carrito.php"); // Redirige para actualizar la vista del carrito
    exit();
}

// Si se ha enviado una solicitud para actualizar la cantidad de un producto
if (isset($_POST['update_quantity'])) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $sql = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $cantidad, $usuario_id, $producto_id);
    $stmt->execute();
    $stmt->close();
    header("Location: carrito.php"); // Redirige para actualizar la vista del carrito
    exit();
}

// Obtener los productos en el carrito del usuario
$sql = "SELECT p.id, p.nombre, p.precio, c.cantidad
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$carrito = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $carrito[] = $row;
    $total += $row['precio'] * $row['cantidad'];
}
$stmt->close();

$foto_perfil = '';
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

if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

if (isset($_POST['id_producto']) && isset($_POST['cantidad'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];

    // Agregar el producto al carrito
    // (Implementa la lógica para agregar el producto a la base de datos o sesión)

    $_SESSION['cart_count'] += $cantidad;
    header('Location: productos.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
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
        <h1>Carrito de Compras</h1>
        <?php if (count($carrito) > 0): ?>
            <form method="POST" action="">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carrito as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($item['precio'], 2)); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" class="form-control" style="width:100px; display:inline;">
                                        <button type="submit" name="update_quantity" class="btn btn-secondary btn-sm">Actualizar</button>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars(number_format($item['precio'] * $item['cantidad'], 2)); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td><?php echo htmlspecialchars(number_format($total, 2)); ?></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" name="proceed" class="btn btn-primary">Proceder con la Compra</button>
            </form>
        <?php else: ?>
            <p>No hay productos en tu carrito.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>