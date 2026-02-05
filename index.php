<?php 
session_start();
require_once __DIR__ . '/config/config.php';

// Conexión
$db  = new Conexion($opciones);
$pdo = $db->con;

// Estadísticas generales
$stmt_suma = $pdo->query("SELECT SUM(precio * cantidad_disponible) AS total_inventario FROM Libros");
$datos_suma = $stmt_suma->fetch(PDO::FETCH_ASSOC);

$stmt_libros_count = $pdo->query("SELECT COUNT(*) as total FROM Libros WHERE cantidad_disponible > 0");
$total_libros = $stmt_libros_count->fetch(PDO::FETCH_ASSOC);

$stmt_ventas_count = $pdo->query("SELECT COUNT(*) as total FROM Ventas");
$total_ventas = $stmt_ventas_count->fetch(PDO::FETCH_ASSOC);

// Libros más vendidos (con LEFT JOIN por si no hay ventas)
$stmt_top = $pdo->query("
    SELECT L.titulo, L.autor, COALESCE(SUM(V.cantidad_venta), 0) AS total_vendido
    FROM Libros L
    LEFT JOIN Ventas V ON L.id_libro = V.id_libro
    GROUP BY L.id_libro, L.titulo, L.autor
    ORDER BY total_vendido DESC
    LIMIT 5
");

// Ventas recientes (INNER JOIN solo si hay ventas)
$stmt_recientes = $pdo->query("
    SELECT C.nombre, L.titulo, V.fecha_venta, V.total
    FROM Ventas V
    INNER JOIN Clientes C ON V.id_cliente = C.id_cliente
    INNER JOIN Libros L ON V.id_libro = L.id_libro
    ORDER BY V.fecha_venta DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Digital - Sistema de Ventas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-menu { 
            transition: all 0.3s; 
            cursor: pointer; 
            border: none !important;
            background: white;
        }
        .card-menu:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 15px;
        }
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <span style="font-size: 1.5rem;">📚</span>
                <strong>Biblioteca Digital</strong>
                <small class="text-muted ms-2">Sistema de Ventas</small>
            </a>
            <div>
                <?php if(isset($_SESSION['id_cliente'])): ?>
                    <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?></span>
                    <a href="modulos/logout.php" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-custom">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="mb-3">Bienvenido a Biblioteca Digital</h1>
            <p class="text-muted mb-0">Sistema completo de ventas con geolocalización, gestión de inventario y procesamiento de pagos</p>
        </div>

        <!-- Estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($datos_suma['total_inventario'], 2); ?></div>
                    <div class="text-muted mt-2">Valor Total del Inventario</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_libros['total']; ?></div>
                    <div class="text-muted mt-2">Libros Disponibles</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_ventas['total']; ?></div>
                    <div class="text-muted mt-2">Ventas Realizadas</div>
                </div>
            </div>
        </div>

        <!-- Módulos del Sistema -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card h-100 card-menu shadow-sm p-4">
                    <div class="card-body text-center">
                        <h4 class="card-title mt-3">Catálogo de Libros</h4>
                        <p class="card-text text-muted">Explora nuestro catálogo completo de libros disponibles para compra</p>
                        <a href="modulos/catalogo_libros.php" class="btn btn-primary w-100 mt-3">Ver Catálogo</a>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <span class="badge bg-primary">Funciones: SELECT, WHERE, ORDER BY</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 card-menu shadow-sm p-4">
                    <div class="card-body text-center">
                        <div style="font-size: 3rem;">🛒</div>
                        <h4 class="card-title mt-3">Sistema de Compras</h4>
                        <p class="card-text text-muted">Proceso completo de compra con geolocalización y pagos</p>
                        <a href="modulos/catalogo_libros.php" class="btn btn-success w-100 mt-3">Comprar Ahora</a>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <span class="badge bg-success">Geolocalización + Pagos</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 card-menu shadow-sm p-4">
                    <div class="card-body text-center">
                        <h4 class="card-title mt-3">Mis Pedidos</h4>
                        <p class="card-text text-muted">Consulta el historial de tus compras y seguimiento de envíos</p>
                        <a href="modulos/mis_pedidos.php" class="btn btn-info w-100 mt-3 text-white">Ver Pedidos</a>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <span class="badge bg-info text-white">Estado de Pedidos</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 card-menu shadow-sm p-4">
                    <div class="card-body text-center">
                        <h4 class="card-title mt-3">Servicio de Geolocalización</h4>
                        <p class="card-text text-muted">Visualiza rutas de entrega y ubicaciones de clientes</p>
                        <a href="modulos/servicio_geolocalizacion.php" class="btn btn-warning w-100 mt-3">Ver Mapa</a>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <span class="badge bg-warning text-dark">Google Maps API</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablas de información -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Libros Más Vendidos (SUM + GROUP BY)</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Vendidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($top = $stmt_top->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($top['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($top['autor']); ?></td>
                                    <td><span class="badge bg-success"><?php echo $top['total_vendido']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Ventas Recientes (INNER JOIN)</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Libro</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                while($reciente = $stmt_recientes->fetch(PDO::FETCH_ASSOC)): 
                                    if($count >= 5) break;
                                    $count++;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reciente['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($reciente['titulo']); ?></td>
                                    <td><strong>$<?php echo number_format($reciente['total'], 2); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($count == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay ventas registradas aún</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-white mt-5 pb-4">
        <small>Biblioteca Digital - Sistema de Ventas | Proyecto Integrador | <?php echo date('Y'); ?></small>
    </footer>

</body>
</html>
