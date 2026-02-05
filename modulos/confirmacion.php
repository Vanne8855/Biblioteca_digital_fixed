<?php
session_start();
require_once '../config/config.php';

if(!isset($_SESSION['id_cliente'])) {
    header('Location: ../login.php');
    exit();
}

$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id_venta <= 0) {
    header('Location: catalogo_libros.php');
    exit();
}

$db = new Conexion($opciones);

// Obtener información de la venta con INNER JOIN
$pdo = $db->con;
$stmt = $pdo->prepare("
    SELECT V.*, L.titulo, L.autor, L.precio, C.nombre, C.correo, C.telefono, P.metodo_pago, P.numero_tarjeta_ultimos4
    FROM Ventas V
    INNER JOIN Libros L ON V.id_libro = L.id_libro
    INNER JOIN Clientes C ON V.id_cliente = C.id_cliente
    LEFT JOIN Pagos P ON V.id_venta = P.id_venta
    WHERE V.id_venta = ? AND V.id_cliente = ?
");
$stmt->execute([$id_venta, $_SESSION['id_cliente']]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venta) {
    header('Location: catalogo_libros.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .confirmation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .confirmation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .confirmation-body {
            padding: 40px;
        }
        .info-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .total-amount {
            font-size: 2rem;
            color: #667eea;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .map-container {
            margin-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="confirmation-header">
            <div class="success-icon">✅</div>
            <h1 class="mb-3">¡Compra Realizada con Éxito!</h1>
            <p class="mb-0">Tu pedido ha sido procesado correctamente</p>
        </div>
        
        <div class="confirmation-body">
            <!-- Información del pedido -->
            <div class="info-section">
                <h4 class="mb-3">📦 Información del Pedido</h4>
                <div class="info-row">
                    <strong>Número de Pedido:</strong>
                    <span>#<?php echo str_pad($venta['id_venta'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <strong>Fecha:</strong>
                    <span><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></span>
                </div>
                <div class="info-row">
                    <strong>Estado:</strong>
                    <span class="status-badge status-pendiente"><?php echo $venta['estado_venta']; ?></span>
                </div>
            </div>
            
            <!-- Detalles del libro -->
            <div class="info-section">
                <h4 class="mb-3">📖 Detalles de la Compra</h4>
                <div class="info-row">
                    <strong>Libro:</strong>
                    <span><?php echo htmlspecialchars($venta['titulo']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Autor:</strong>
                    <span><?php echo htmlspecialchars($venta['autor']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Cantidad:</strong>
                    <span><?php echo $venta['cantidad_venta']; ?> unidad(es)</span>
                </div>
                <div class="info-row">
                    <strong>Precio unitario:</strong>
                    <span>$<?php echo number_format($venta['precio'], 2); ?> MXN</span>
                </div>
            </div>
            
            <!-- Total -->
            <div class="total-amount">
                Total: $<?php echo number_format($venta['total'], 2); ?> MXN
            </div>
            
            <!-- Información de envío -->
            <div class="info-section">
                <h4 class="mb-3">📍 Dirección de Envío</h4>
                <div class="info-row">
                    <strong>Dirección:</strong>
                    <span><?php echo htmlspecialchars($venta['direccion_envio']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Ciudad:</strong>
                    <span><?php echo htmlspecialchars($venta['ciudad']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Estado:</strong>
                    <span><?php echo htmlspecialchars($venta['estado']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Código Postal:</strong>
                    <span><?php echo htmlspecialchars($venta['codigo_postal']); ?></span>
                </div>
                <?php if($venta['referencia']): ?>
                <div class="info-row">
                    <strong>Referencia:</strong>
                    <span><?php echo htmlspecialchars($venta['referencia']); ?></span>
                </div>
                <?php endif; ?>
                <?php if($venta['instrucciones']): ?>
                <div class="info-row">
                    <strong>Instrucciones:</strong>
                    <span><?php echo htmlspecialchars($venta['instrucciones']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Mapa de ubicación -->
            <?php if($venta['latitud'] && $venta['longitud']): ?>
            <div class="map-container">
                <h5 class="mb-3">🗺️ Ubicación de Entrega</h5>
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3500.125497250773!2d<?php echo $venta['longitud']; ?>!3d<?php echo $venta['latitud']; ?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1ses-419!2smx!4v<?php echo time(); ?>!5m2!1ses-419!2smx" 
                    width="100%" 
                    height="300" 
                    style="border:0; border-radius: 12px;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
            <?php endif; ?>
            
            <!-- Información de pago -->
            <div class="info-section mt-4">
                <h4 class="mb-3">💳 Método de Pago</h4>
                <div class="info-row">
                    <strong>Método:</strong>
                    <span>
                        <?php 
                        $metodos = [
                            'tarjeta_credito' => '💳 Tarjeta de Crédito',
                            'tarjeta_debito' => '💳 Tarjeta de Débito',
                            'paypal' => '🅿️ PayPal',
                            'transferencia' => '🏦 Transferencia Bancaria'
                        ];
                        echo $metodos[$venta['metodo_pago']] ?? $venta['metodo_pago'];
                        ?>
                    </span>
                </div>
                <?php if($venta['numero_tarjeta_ultimos4']): ?>
                <div class="info-row">
                    <strong>Tarjeta terminada en:</strong>
                    <span>**** <?php echo $venta['numero_tarjeta_ultimos4']; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Información de contacto -->
            <div class="info-section">
                <h4 class="mb-3">👤 Datos de Contacto</h4>
                <div class="info-row">
                    <strong>Nombre:</strong>
                    <span><?php echo htmlspecialchars($venta['nombre']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Correo:</strong>
                    <span><?php echo htmlspecialchars($venta['correo']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Teléfono:</strong>
                    <span><?php echo htmlspecialchars($venta['telefono']); ?></span>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="d-flex gap-2 mt-4">
                <a href="../index.php" class="btn btn-primary flex-grow-1">
                    🏠 Volver al Inicio
                </a>
                <a href="catalogo_libros.php" class="btn btn-success flex-grow-1">
                    🛒 Seguir Comprando
                </a>
                <a href="mis_pedidos.php" class="btn btn-info flex-grow-1 text-white">
                    📊 Mis Pedidos
                </a>
            </div>
            
            <!-- Mensaje informativo -->
            <div class="alert alert-info mt-4 mb-0">
                <strong>📧 Confirmación enviada</strong><br>
                Hemos enviado los detalles de tu pedido a <strong><?php echo htmlspecialchars($venta['correo']); ?></strong>
            </div>
        </div>
    </div>
</body>
</html>
