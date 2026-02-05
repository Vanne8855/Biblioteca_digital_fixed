<?php
session_start();
require_once '../config/config.php';

if(!isset($_SESSION['id_cliente'])) {
    header('Location: ../login.php');
    exit();
}

$db = new Conexion($opciones);
$pdo = $db->con;

// Obtener pedidos del cliente con INNER JOIN
$stmt = $pdo->prepare("
    SELECT V.*, L.titulo, L.autor, L.precio
    FROM Ventas V
    INNER JOIN Libros L ON V.id_libro = L.id_libro
    WHERE V.id_cliente = ?
    ORDER BY V.fecha_venta DESC
");
$stmt->execute([$_SESSION['id_cliente']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f5f5;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .pedido-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .pedido-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendiente { background: #fff3cd; color: #856404; }
        .status-procesando { background: #cfe2ff; color: #084298; }
        .status-enviado { background: #d1e7dd; color: #0a3622; }
        .status-entregado { background: #d1e7dd; color: #0f5132; }
        .status-cancelado { background: #f8d7da; color: #842029; }
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <strong>Biblioteca Digital</strong>
            </a>
            <div>
                <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?></span>
                <a href="catalogo_libros.php" class="btn btn-sm btn-outline-primary me-2">← Catálogo</a>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1 class="mb-2">Mis Pedidos</h1>
            <p class="text-muted mb-0">Historial completo de tus compras</p>
        </div>

        <?php if(!empty($pedidos)): ?>
            <?php foreach($pedidos as $pedido): ?>
                <div class="pedido-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2">
                                📖 <?php echo htmlspecialchars($pedido['titulo']); ?>
                            </h5>
                            <p class="text-muted mb-3">
                                <strong>Autor:</strong> <?php echo htmlspecialchars($pedido['autor']); ?>
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small><strong>Pedido #:</strong> <?php echo str_pad($pedido['id_venta'], 6, '0', STR_PAD_LEFT); ?></small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small><strong>Cantidad:</strong> <?php echo $pedido['cantidad_venta']; ?> unidad(es)</small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['ciudad']); ?>, <?php echo htmlspecialchars($pedido['estado']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="mb-3">
                                <span class="status-badge status-<?php echo $pedido['estado_venta']; ?>">
                                    <?php echo ucfirst($pedido['estado_venta']); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-primary mb-3">
                                $<?php echo number_format($pedido['total'], 2); ?>
                            </h3>
                            
                            <a href="confirmacion.php?id=<?php echo $pedido['id_venta']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Ver Detalles →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <h3 class="mt-3">No tienes pedidos aún</h3>
                <p class="text-muted">¡Explora nuestro catálogo y realiza tu primera compra!</p>
                <a href="catalogo_libros.php" class="btn btn-primary mt-3">
                    Ver Catálogo →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center text-muted mt-5 pb-4">
        <small>Biblioteca Digital © <?php echo date('Y'); ?></small>
    </footer>
</body>
</html>
