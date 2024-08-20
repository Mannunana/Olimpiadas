<?php
include 'db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Comienza una transacción
$conn->begin_transaction();

try {
    // Obtén los productos en el carrito
    $sql = "SELECT * FROM carrito WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Crear un nuevo pedido
        $sql_insert_pedido = "INSERT INTO pedidos (usuario_id) VALUES (?)";
        $stmt = $conn->prepare($sql_insert_pedido);
        $stmt->bind_param("i", $usuario_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al crear el pedido: " . $conn->error);
        }
        $pedido_id = $conn->insert_id;

        while ($row = $result->fetch_assoc()) {
            $producto_id = $row['producto_id'];
            $cantidad = $row['cantidad'];

            // Actualiza el stock en la tabla productos
            $sql_update_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update_stock);
            $stmt->bind_param("ii", $cantidad, $producto_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el stock: " . $conn->error);
            }

            // Inserta detalles del pedido
            $sql_insert_detalle = "INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio)
                                   SELECT ?, id, ?, precio FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql_insert_detalle);
            $stmt->bind_param("iii", $pedido_id, $cantidad, $producto_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al insertar detalles del pedido: " . $conn->error);
            }
        }

        // Commit de la transacción
        $conn->commit();

        // Actualiza el contador del carrito en la sesión
        $_SESSION['cart_count'] = 0;

        // Redirige al usuario a la página de confirmación de compra
        header("Location: carrito.php");
        exit();
    } else {
        throw new Exception("El carrito está vacío.");
    }
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>