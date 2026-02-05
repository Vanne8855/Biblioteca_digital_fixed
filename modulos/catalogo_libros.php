<?php 
session_start();
require_once '../config/config.php';

// Conexión
$db = new Conexion($opciones);

// Obtener parámetros de búsqueda
$buscar = isset($_POST['txtbuscar']) ? $_POST['txtbuscar'] : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';

// ================== CONSULTA CORREGIDA ==================
$query = $db->select("Libros", "*");

// Filtro base obligatorio (evita AND sin WHERE)
$query->where("cantidad_disponible", ">", 0);

if($buscar != '') {
    $query->where_and("titulo", "LIKE", "%$buscar%");
}

if($categoria != '') {
    $query->where_and("categoria", "=", $categoria);
}

$query->orderby("titulo ASC");
$libros = $query->execute();
// ========================================================

// Obtener categorías únicas para el filtro
$query_cat = $db->select("Libros", "DISTINCT categoria");
$query_cat->orderby("categoria ASC");
$categorias = $query_cat->execute();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Libros - Biblioteca Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .book-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .book-icon { font-size: 3rem; text-align: center; margin-bottom: 15px; }
        .book-title { font-size: 1.2rem; font-weight: bold; color: #333; margin-bottom: 8px; min-height: 50px; }
        .book-author { color: #666; font-size: 0.95rem; margin-bottom: 12px; }
        .book-info { font-size: 0.85rem; color: #777; margin-bottom: 10px; }
        .book-price { font-size: 1.5rem; font-weight: bold; color: #667eea; margin: 15px 0; }
        .stock-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .stock-available { background: #d4edda; color: #155724; }
        .stock-low { background: #fff3cd; color: #856404; }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header-section { text-align: center; padding: 40px 0; color: #333; }
        .header-section h1 { font-size: 2.5rem; font-weight: bold; color: #667eea; }
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
        <h1>Catálogo de Libros</h1>
        <p class="lead">Explora nuestra colección de libros disponibles</p>
    </div>

    <!-- Filtros -->
    <div class="filters">
        <form method="post" action="catalogo_libros.php">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="txtbuscar" class="form-control"
                           placeholder="Buscar por título..."
                           value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="col-md-4">
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['categoria']); ?>"
                                <?php echo ($categoria == $cat['categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <?php if(!empty($libros)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php foreach($libros as $libro): ?>
                <div class="col">
                    <div class="book-card">
                        <div class="book-title"><?php echo htmlspecialchars($libro['titulo']); ?></div>
                        <div class="book-author">por <?php echo htmlspecialchars($libro['autor']); ?></div>

                        <div class="book-info">
                            <div><strong>Categoría:</strong> <?php echo htmlspecialchars($libro['categoria']); ?></div>
                            <div><strong>Año:</strong> <?php echo $libro['anio_publicacion'] ?? 'N/A'; ?></div>
                            <?php if(!empty($libro['isbn'])): ?>
                                <div><strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn']); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php 
                        $cantidad = $libro['cantidad_disponible'];
                        if($cantidad > 10) {
                            echo '<span class="stock-badge stock-available">✓ Disponible ('.$cantidad.' unidades)</span>';
                        } else {
                            echo '<span class="stock-badge stock-low">Pocas unidades ('.$cantidad.')</span>';
                        }
                        ?>

                        <div class="book-price">
                            $<?php echo number_format($libro['precio'], 2); ?> MXN
                        </div>

                        <?php if(isset($_SESSION['id_cliente'])): ?>
                            <a href="proceso_compra.php?id=<?php echo $libro['id_libro']; ?>" class="btn btn-success w-100">
                                🛒 Comprar Ahora
                            </a>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-outline-primary w-100">
                                Inicia sesión para comprar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <h3 class="mt-3">No se encontraron libros</h3>
            <p class="text-muted">Intenta con otros términos de búsqueda</p>
            <a href="catalogo_libros.php" class="btn btn-primary mt-3">Ver todos los libros</a>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center text-muted mt-5 pb-4">
    <small>Biblioteca Digital © <?php echo date('Y'); ?></small>
</footer>

</body>
</html>
