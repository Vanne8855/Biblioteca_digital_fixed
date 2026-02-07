<?php
session_start();
header('Content-Type: application/json');

// LOG DE DEBUGGING - Comentar en producción
error_log("=== INICIO PROCESAR COMPRA ===");
error_log("SESSION: " . print_r($_SESSION, true));
error_log("POST: " . print_r($_POST, true));

require_once '../config/config.php';

// Validación de sesión
if(!isset($_SESSION['id_cliente'])) {
    error_log("ERROR: No hay sesión activa");
    echo json_encode(['success' => false, 'error' => 'No ha iniciado sesión. Por favor, inicie sesión primero.']);
    exit();
}

// Validación de método
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Método incorrecto - " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    // Crear conexión
    $db = new Conexion($opciones);
    $pdo = $db->con;
    
    error_log("Conexión a BD establecida");
    
    // Recibir y validar datos - CON VERIFICACIÓN DE EXISTENCIA
    if(!isset($_POST['id_libro'])) {
        throw new Exception('Falta el ID del libro');
    }
    if(!isset($_POST['id_cliente'])) {
        throw new Exception('Falta el ID del cliente');
    }
    if(!isset($_POST['cantidad'])) {
        throw new Exception('Falta la cantidad');
    }
    if(!isset($_POST['direccion'])) {
        throw new Exception('Falta la dirección');
    }
    if(!isset($_POST['ciudad'])) {
        throw new Exception('Falta la ciudad');
    }
    if(!isset($_POST['estado'])) {
        throw new Exception('Falta el estado');
    }
    if(!isset($_POST['codigo_postal'])) {
        throw new Exception('Falta el código postal');
    }
    if(!isset($_POST['latitud'])) {
        throw new Exception('Falta la latitud - asegúrate de activar la ubicación');
    }
    if(!isset($_POST['longitud'])) {
        throw new Exception('Falta la longitud - asegúrate de activar la ubicación');
    }
    if(!isset($_POST['metodo_pago'])) {
        throw new Exception('Falta el método de pago');
    }
    
    // Sanitizar datos
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
    $metodo_pago = trim($_POST['metodo_pago']);
    
    error_log("Datos recibidos - Libro: $id_libro, Cliente: $id_cliente, Cantidad: $cantidad");
    error_log("Ubicación - Lat: $latitud, Lng: $longitud");
    
    // Validación de seguridad - el cliente de la sesión debe coincidir
    if($id_cliente != $_SESSION['id_cliente']) {
        throw new Exception('Error de seguridad: ID de cliente no coincide con la sesión');
    }
    
    // Validaciones de datos
    if($id_libro <= 0) {
        throw new Exception('ID de libro inválido');
    }
    if($id_cliente <= 0) {
        throw new Exception('ID de cliente inválido');
    }
    if($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a 0');
    }
    
    if(empty($direccion)) {
        throw new Exception('La dirección es obligatoria');
    }
    if(empty($ciudad)) {
        throw new Exception('La ciudad es obligatoria');
    }
    if(empty($estado)) {
        throw new Exception('El estado es obligatorio');
    }
    if(empty($codigo_postal)) {
        throw new Exception('El código postal es obligatorio');
    }
    
    if($latitud == 0 && $longitud == 0) {
        throw new Exception('Las coordenadas GPS no son válidas. Por favor, activa tu ubicación o ingresa las coordenadas manualmente.');
    }
    
    if(empty($metodo_pago)) {
        throw new Exception('Debe seleccionar un método de pago');
    }
    
    // Validar método de pago
    $metodos_validos = ['tarjeta_credito', 'tarjeta_debito', 'paypal', 'transferencia'];
    if(!in_array($metodo_pago, $metodos_validos)) {
        throw new Exception('Método de pago no válido');
    }
    
    error_log("Validaciones básicas pasadas");
    
    // Verificar que el libro existe y tiene stock
    $query_libro = $db->select("Libros", "*");
    $query_libro->where("id_libro", "=", $id_libro);
    $libros = $query_libro->execute();
    
    if(empty($libros)) {
        error_log("ERROR: Libro no encontrado - ID: $id_libro");
        throw new Exception('El libro solicitado no existe');
    }
    
    $libro = $libros[0];
    error_log("Libro encontrado: " . $libro['titulo'] . " - Stock: " . $libro['cantidad_disponible']);
    
    if($libro['cantidad_disponible'] < $cantidad) {
        throw new Exception("Stock insuficiente. Solo hay {$libro['cantidad_disponible']} disponible(s)");
    }
    
    // Calcular total
    $total = $libro['precio'] * $cantidad;
    error_log("Total calculado: $total");
    
    // Validar datos de tarjeta si es necesario
    if(in_array($metodo_pago, ['tarjeta_credito', 'tarjeta_debito'])) {
        if(!isset($_POST['numero_tarjeta']) || empty(trim($_POST['numero_tarjeta']))) {
            throw new Exception('El número de tarjeta es obligatorio');
        }
        if(!isset($_POST['nombre_titular']) || empty(trim($_POST['nombre_titular']))) {
            throw new Exception('El nombre del titular es obligatorio');
        }
        if(!isset($_POST['fecha_exp']) || empty(trim($_POST['fecha_exp']))) {
            throw new Exception('La fecha de expiración es obligatoria');
        }
        if(!isset($_POST['cvv']) || empty(trim($_POST['cvv']))) {
            throw new Exception('El CVV es obligatorio');
        }
    }
    
    // INICIAR TRANSACCIÓN
    error_log("Iniciando transacción");
    $pdo->beginTransaction();
    
    try {
        // 1. INSERTAR VENTA
        error_log("Insertando venta");
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
        error_log("Venta insertada con ID: $id_venta");
        
        // 2. INSERTAR PAGO
        error_log("Insertando pago");
        $numero_tarjeta_ultimos4 = null;
        $nombre_titular = null;
        
        if(in_array($metodo_pago, ['tarjeta_credito', 'tarjeta_debito'])) {
            $numero_tarjeta = trim($_POST['numero_tarjeta']);
            $numero_tarjeta_ultimos4 = substr($numero_tarjeta, -4);
            $nombre_titular = trim($_POST['nombre_titular']);
            error_log("Pago con tarjeta - Últimos 4: $numero_tarjeta_ultimos4");
        }
        
        $insert_pago = $db->insert("Pagos", "id_venta, metodo_pago, numero_tarjeta_ultimos4, nombre_titular, estado_pago");
        $insert_pago->value("?", $id_venta);
        $insert_pago->value_and("?", $metodo_pago);
        $insert_pago->value_and("?", $numero_tarjeta_ultimos4);
        $insert_pago->value_and("?", $nombre_titular);
        $insert_pago->value_and("?", 'aprobado');
        $insert_pago->execute();
        
        error_log("Pago insertado");
        
        // 3. ACTUALIZAR STOCK
        error_log("Actualizando stock");
        $nuevo_stock = $libro['cantidad_disponible'] - $cantidad;
        
        $update_libro = $db->update("Libros");
        $update_libro->set("cantidad_disponible", $nuevo_stock);
        $update_libro->where("id_libro", "=", $id_libro);
        $update_libro->execute();
        
        error_log("Stock actualizado - Nuevo stock: $nuevo_stock");
        
        // COMMIT
        $pdo->commit();
        error_log("Transacción completada exitosamente");
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'id_venta' => $id_venta,
            'message' => 'Compra realizada con éxito',
            'total' => $total,
            'libro' => $libro['titulo']
        ]);
        
    } catch(Exception $e) {
        // Si hay error en la transacción, hacer rollback
        $pdo->rollBack();
        error_log("ERROR en transacción: " . $e->getMessage());
        throw $e; // Re-lanzar para que lo capture el catch externo
    }
    
} catch(Exception $e) {
    // Rollback en caso de error (por si no se hizo en el try interno)
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Rollback ejecutado");
    }
    
    error_log("ERROR GENERAL: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

error_log("=== FIN PROCESAR COMPRA ===\n");
?>
