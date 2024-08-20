<?php
session_start();
include 'db.php';

// Verificar si hay una búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consultar los productos basados en la búsqueda
$sql = "SELECT * FROM productos WHERE nombre LIKE '%$search%'";
$result = $conn->query($sql);

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .nav-item img {
            width: 40px; /* Ajusta el tamaño según lo necesites */
            height: 40px;
            border-radius: 50%;
        }
        .cart-icon {
            position: relative;
            display: inline-block;
        }
        .cart-icon .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
        }
        .card-img-top {
            height: 200px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .navbar {
            position: relative; /* Establece el contenedor de la barra de navegación como relativo */
        }

        .cart-icon {
            position: absolute; /* Posiciona el ícono de carrito de manera absoluta dentro del contenedor relativo */
            top: 10px; /* Ajusta el margen superior según sea necesario */
            right: 10px; /* Ajusta el margen derecho según sea necesario */
        }

    </style>

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand" href="#">Tienda de Deportes</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item"><a class="nav-link" href="productos.php">Productos</a></li>
                        <li class="nav-item"><a class="nav-link" href="perfil.php">Perfil</a></li>
                        
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <li class="nav-item cart-icon">
                                <a class="nav-link" href="procesar_compra.php">
                                    <i class="fas fa-shopping-cart"></i>
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
        <h1>Productos</h1>
        <form method="GET" action="" class="mb-4">
            <input type="text" name="search" placeholder="Buscar productos" class="form-control mb-2" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>

        <div class="row">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Aplicar descuento si es la primera compra
                    $descuento = isset($_SESSION['primer_compra']) && $_SESSION['primer_compra'] ? 0.20 : 0;
                    $precio_final = $row['precio'] * (1 - $descuento);

                    echo '
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <img src="images/' . htmlspecialchars($row['foto']) . '" class="card-img-top" alt="' . htmlspecialchars($row['nombre']) . '">
                                <div class="card-body">
                                    <h5 class="card-title">' . htmlspecialchars($row['nombre']) . '</h5>
                                    <p class="card-text">' . htmlspecialchars($row['descripcion']) . '</p>
                                    <p class="card-text">Precio original: $' . htmlspecialchars($row['precio']) . '</p>
                                    <p class="card-text">Precio con descuento: $' . number_format($precio_final, 2) . '</p>
                                    <p class="card-text">Stock: ' . htmlspecialchars($row['stock']) . '</p>
                                    <form action="agregar_carrito.php" method="POST">
                                        <input type="hidden" name="producto_id" value="' . htmlspecialchars($row['id']) . '">
                                        <input type="number" name="cantidad" value="1" min="1" max="' . htmlspecialchars($row['stock']) . '" class="form-control mb-2">
                                        <button type="submit" class="btn btn-primary">Agregar al carrito</button>
                                    </form>
                                </div>
                            </div>
                        </div>';
                }
            } else {
                echo '<p>No se encontraron productos.</p>';
            }
            $conn->close();
            ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Función para actualizar el contador del carrito
        function actualizarContador() {
            // Obtener el número de productos en el carrito desde el servidor o localmente
            var cartCount = <?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0; ?>;
            document.getElementById('cart-count').textContent = cartCount;
        }

        // Llamar a la función al cargar la página
        actualizarContador();
    </script>
</body>
</html>