<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

if(!isset($_SESSION['id_cliente'])) {
    echo json_encode(['success' => false, 'error' => 'No ha iniciado sesión']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    $db = new Conexion($opciones);
    $pdo = $db->con;
    
    // Validar datos recibidos
    $id_libro = intval($_POST['id_libro']);
    $id_cliente = intval($_POST['id_cliente']);
    $cantidad = intval($_POST['cantidad']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $estado = trim($_POST['estado']);
    $codigo_postal = trim($_POST['codigo_postal']);
    $latitud = floatval($_POST['latitud']);
    $longitud = floatval($_POST['longitud']);
    $referencia = trim($_POST['referencia'] ?? '');
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $metodo_pago = $_POST['metodo_pago'];
    
    // Validaciones
    if($id_libro <= 0 || $id_cliente <= 0 || $cantidad <= 0) {
        throw new Exception('Datos inválidos');
    }
    
    if(empty($direccion) || empty($ciudad) || empty($estado) || empty($codigo_postal)) {
        throw new Exception('Complete todos los campos de envío');
    }
    
    if(empty($metodo_pago)) {
        throw new Exception('Seleccione un método de pago');
    }
    
    // Verificar que el libro existe y tiene stock
    $query_libro = $db->select("Libros", "*");
    $query_libro->where("id_libro", "=", $id_libro);
    $libros = $query_libro->execute();
    
    if(empty($libros)) {
        throw new Exception('Libro no encontrado');
    }
    
    $libro = $libros[0];
    
    if($libro['cantidad_disponible'] < $cantidad) {
        throw new Exception('Stock insuficiente');
    }
    
    // Calcular total
    $total = $libro['precio'] * $cantidad;
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Insertar venta
    $insert_venta = $db->insert("Ventas", "id_cliente, id_libro, cantidad_venta, total, estado_venta, direccion_envio, ciudad, estado, codigo_postal, latitud, longitud, referencia, instrucciones");
    $insert_venta->value("?", $id_cliente);
    $insert_venta->value_and("?", $id_libro);
    $insert_venta->value_and("?", $cantidad);
    $insert_venta->value_and("?", $total);
    $insert_venta->value_and("?", 'pendiente');
    $insert_venta->value_and("?", $direccion);
    $insert_venta->value_and("?", $ciudad);
    $insert_venta->value_and("?", $estado);
    $insert_venta->value_and("?", $codigo_postal);
    $insert_venta->value_and("?", $latitud);
    $insert_venta->value_and("?", $longitud);
    $insert_venta->value_and("?", $referencia);
    $insert_venta->value_and("?", $instrucciones);
    $insert_venta->execute();
    
    $id_venta = $db->lastInsertId();
    
    // 2. Insertar pago
    $numero_tarjeta_ultimos4 = null;
    $nombre_titular = null;
    
    if(in_array($metodo_pago, ['tarjeta_credito', 'tarjeta_debito'])) {
        $numero_tarjeta = $_POST['numero_tarjeta'] ?? '';
        $numero_tarjeta_ultimos4 = substr($numero_tarjeta, -4);
        $nombre_titular = $_POST['nombre_titular'] ?? '';
    }
    
    $insert_pago = $db->insert("Pagos", "id_venta, metodo_pago, numero_tarjeta_ultimos4, nombre_titular, estado_pago");
    $insert_pago->value("?", $id_venta);
    $insert_pago->value_and("?", $metodo_pago);
    $insert_pago->value_and("?", $numero_tarjeta_ultimos4);
    $insert_pago->value_and("?", $nombre_titular);
    $insert_pago->value_and("?", 'aprobado');
    $insert_pago->execute();
    
    // 3. Actualizar stock del libro
    $update_libro = $db->update("Libros");
    $update_libro->set("cantidad_disponible", $libro['cantidad_disponible'] - $cantidad);
    $update_libro->where("id_libro", "=", $id_libro);
    $update_libro->execute();
    
    // Commit de la transacción
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'id_venta' => $id_venta,
        'message' => 'Compra realizada con éxito'
    ]);
    
} catch(Exception $e) {
    // Rollback en caso de error
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
