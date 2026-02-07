<?php
session_start();
require_once '../config/config.php';

$db = new Conexion($opciones);
$pdo = $db->con;

$stmt = $pdo->query("
    SELECT V.id_venta, V.direccion_envio, V.ciudad, V.latitud, V.longitud,
           V.estado_venta, V.fecha_venta, C.nombre AS cliente, L.titulo
    FROM Ventas V
    LEFT JOIN Clientes C ON V.id_cliente = C.id_cliente
    LEFT JOIN Libros L ON V.id_libro = L.id_libro
    WHERE V.latitud IS NOT NULL AND V.longitud IS NOT NULL
    ORDER BY V.fecha_venta DESC
    LIMIT 50
");

$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$markers = [];
foreach($ubicaciones as $ubi) {
    $markers[] = [
        'lat' => floatval($ubi['latitud']),
        'lng' => floatval($ubi['longitud']),
        'title' => htmlspecialchars($ubi['titulo']),
        'cliente' => htmlspecialchars($ubi['cliente']),
        'direccion' => htmlspecialchars($ubi['direccion_envio']),
        'ciudad' => htmlspecialchars($ubi['ciudad']),
        'estado_venta' => $ubi['estado_venta'],
        'pedido' => str_pad($ubi['id_venta'], 6, '0', STR_PAD_LEFT)
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio de Geolocalización - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f5f5;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        #map {
            height: 600px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .map-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .legend {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
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
                <?php if(isset($_SESSION['id_cliente'])): ?>
                    <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="../login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1 class="mb-2">Servicio de Geolocalización</h1>
            <p class="text-muted mb-0">Mapa interactivo con ubicaciones de entrega</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number"><?php echo count($ubicaciones); ?></div>
                    <div class="text-muted">Entregas Registradas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number">
                        <?php 
                        $ciudades = array_unique(array_column($ubicaciones, 'ciudad'));
                        echo count($ciudades);
                        ?>
                    </div>
                    <div class="text-muted">Ciudades Cubiertas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number">
                        <?php 
                        $enviados = array_filter($ubicaciones, function($u) {
                            return $u['estado_venta'] === 'enviado' || $u['estado_venta'] === 'entregado';
                        });
                        echo count($enviados);
                        ?>
                    </div>
                    <div class="text-muted">Pedidos en Tránsito/Entregados</div>
                </div>
            </div>
        </div>

        <div class="map-container">
            <h4 class="mb-3">Mapa de Entregas</h4>
            <div class="mb-3">
                <label class="form-label"><strong>Seleccionar pedido</strong></label>
                <select class="form-select" onchange="mostrarInfo(this.value)">
                    <?php foreach ($markers as $i => $m): ?>
                        <option value="<?= $i ?>">
                            Pedido #<?= $m['pedido'] ?> – <?= $m['ciudad'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="map"></div>

            <div class="mt-3 p-3 bg-light rounded" id="infoPedido">
            
            </div>
        </div>

    <footer class="text-center text-muted mt-5 pb-4">
        <small>Biblioteca Digital © <?php echo date('Y'); ?></small>
    </footer>

<script>
    const markersData = <?php echo json_encode($markers); ?>;

    function cargarMapa(lat, lng) {
        document.getElementById("map").innerHTML = `
            <iframe
                width="100%"
                height="600"
                style="border:0; border-radius:12px"
                loading="lazy"
                allowfullscreen
                src="https://www.google.com/maps?q=${lat},${lng}&z=15&output=embed">
            </iframe>
        `;
    }

    function mostrarInfo(index) {
        const p = markersData[index];

        cargarMapa(p.lat, p.lng);

        document.getElementById("infoPedido").innerHTML = `
            <h6><strong>Información del Pedido</strong></h6>
            <p class="mb-1"><strong>Pedido:</strong> #${p.pedido}</p>
            <p class="mb-1"><strong>Cliente:</strong> ${p.cliente}</p>
            <p class="mb-1"><strong>Dirección:</strong> ${p.direccion}</p>
            <p class="mb-1"><strong>Ciudad:</strong> ${p.ciudad}</p>
            <p class="mb-0">
                <strong>Estado:</strong>
                <span class="badge bg-secondary">${p.estado_venta.toUpperCase()}</span>
            </p>
        `;
    }

    window.onload = () => {
        if (markersData.length > 0) {
            mostrarInfo(0);
        }
    };
</script>


    </body>
</html>
