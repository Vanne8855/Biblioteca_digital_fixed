<?php
session_start();
require_once '../config/config.php';

$db = new Conexion($opciones);
$pdo = $db->con;

// Obtener todas las ventas con ubicaciones (LEFT JOIN para incluir todas las ventas)
$stmt = $pdo->query("
    SELECT V.id_venta, V.direccion_envio, V.ciudad, V.estado, V.latitud, V.longitud, 
           V.estado_venta, V.fecha_venta, C.nombre as cliente, L.titulo
    FROM Ventas V
    LEFT JOIN Clientes C ON V.id_cliente = C.id_cliente
    LEFT JOIN Libros L ON V.id_libro = L.id_libro
    WHERE V.latitud IS NOT NULL AND V.longitud IS NOT NULL
    ORDER BY V.fecha_venta DESC
    LIMIT 50
");
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para el mapa
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
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
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
            <h4 class="mb-3">🗺️ Mapa de Entregas</h4>
            <div id="map"></div>
            
            <div class="legend">
                <h6 class="mb-3"><strong>Leyenda de Estados</strong></h6>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffc107;"></div>
                    <span>Pendiente</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #0dcaf0;"></div>
                    <span>Procesando</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #0d6efd;"></div>
                    <span>Enviado</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #198754;"></div>
                    <span>Entregado</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #dc3545;"></div>
                    <span>Cancelado</span>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted mt-5 pb-4">
        <small>Biblioteca Digital © <?php echo date('Y'); ?></small>
    </footer>

    <script>
        const markersData = <?php echo json_encode($markers); ?>;
        
        function initMap() {
            // Centro del mapa (México)
            const center = { lat: 23.6345, lng: -102.5528 };
            
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 5,
                center: center,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });
            

            const colorMap = {
                'pendiente': '#ffc107',
                'procesando': '#0dcaf0',
                'enviado': '#0d6efd',
                'entregado': '#198754',
                'cancelado': '#dc3545'
            };
            
            markersData.forEach(function(markerData) {
                const color = colorMap[markerData.estado_venta] || '#666';
                
                const marker = new google.maps.Marker({
                    position: { lat: markerData.lat, lng: markerData.lng },
                    map: map,
                    title: markerData.title,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: color,
                        fillOpacity: 0.8,
                        strokeColor: '#fff',
                        strokeWeight: 2
                    }
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px; min-width: 200px;">
                            <h6 style="margin: 0 0 10px 0; color: #333;">
                                <strong>${markerData.title}</strong>
                            </h6>
                            <p style="margin: 5px 0; font-size: 14px;">
                                <strong>Pedido:</strong> #${markerData.pedido}<br>
                                <strong>Cliente:</strong> ${markerData.cliente}<br>
                                <strong>Dirección:</strong> ${markerData.direccion}<br>
                                <strong>Ciudad:</strong> ${markerData.ciudad}<br>
                                <strong>Estado:</strong> <span style="color: ${color}; font-weight: bold;">${markerData.estado_venta.toUpperCase()}</span>
                            </p>
                        </div>
                    `
                });
                
                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
            });
 
            if(markersData.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markersData.forEach(function(marker) {
                    bounds.extend(new google.maps.LatLng(marker.lat, marker.lng));
                });
                map.fitBounds(bounds);
            }
        }
        

        window.onload = initMap;
    </script>
</body>
</html>
