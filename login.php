<?php 
session_start();
require_once __DIR__ . '/config/config.php';

// Redirigir si ya está logueado
if(isset($_SESSION['id_cliente'])) {
    header('Location: index.php');
    exit();
}

$mensaje_error = "";

if(isset($_POST['btnlogin'])) {
    if(empty($_POST['txtemail']) || empty($_POST['txtpassword'])) {
        $mensaje_error = "Por favor complete todos los campos";
    }
    elseif(!filter_var($_POST['txtemail'], FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del email no es válido";
    }
    else {
        try {
            $db = new Conexion($opciones);
            
            // Buscar cliente por correo
            $query = $db->select("Clientes", "*");
            $query->where("correo", "=", $_POST['txtemail']);
            $query->where_and("activo", "=", 1);
            $clientes = $query->execute();
            
            if(!empty($clientes)) {
                $cliente = $clientes[0];
                
                // Verificar contraseña
                if(password_verify($_POST['txtpassword'], $cliente['contrasena'])) {
                    // Login exitoso
                    $_SESSION['id_cliente'] = $cliente['id_cliente'];
                    $_SESSION['nombre_cliente'] = $cliente['nombre'];
                    $_SESSION['correo_cliente'] = $cliente['correo'];
                    $_SESSION['telefono_cliente'] = $cliente['telefono'];
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $mensaje_error = "Credenciales incorrectas";
                }
            } else {
                $mensaje_error = "Usuario no encontrado o inactivo";
            }
        } catch(Exception $e) {
            $mensaje_error = "Error en el sistema. Intente más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .credentials-info {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
            text-align: center;
        }
        
        .credentials-info strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
    
    <script>
        function validarFormulario() {
            var email = document.getElementById('txtemail').value;
            var password = document.getElementById('txtpassword').value;
            
            if(email.trim() === '' || password.trim() === '') {
                alert('Por favor complete todos los campos');
                return false;
            }
            
            if(password.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>📚 Biblioteca Digital</h1>
            <p>Ingrese sus credenciales para continuar</p>
        </div>
        
        <?php if($mensaje_error != ""): ?>
            <div class="error-message">
                ⚠️ <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php" onsubmit="return validarFormulario()">
            <div class="form-group">
                <label for="txtemail">Correo Electrónico</label>
                <input type="email" id="txtemail" name="txtemail" 
                       placeholder="correo@ejemplo.com" 
                       value="<?php echo isset($_POST['txtemail']) ? htmlspecialchars($_POST['txtemail']) : ''; ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="txtpassword">Contraseña</label>
                <input type="password" id="txtpassword" name="txtpassword" 
                       placeholder="Ingrese su contraseña" required>
            </div>
            
            <button type="submit" name="btnlogin" class="btn-login">
                🔐 Iniciar Sesión
            </button>
        </form>
        
        <div class="credentials-info">
            <strong>Credenciales de prueba:</strong>
            <div style="margin-top: 10px;">
                <div><strong>Email:</strong> juan@ejemplo.com</div>
                <div><strong>Password:</strong> password</div>
            </div>
            <div style="margin-top: 10px; font-size: 12px; color: #999;">
                (Todos los usuarios de prueba usan la misma contraseña)
            </div>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>
