<?php
require "init.php";
require "conexao.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, name, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $user_type);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_type'] = $user_type;
                
                // 🔹 Redirecionar conforme o tipo de usuário
                switch ($user_type) {
                    case 'cliente':
                        header("Location: index.php");
                        break;
                    case 'funcionario': // vendedor
                        header("Location: index.php");
                        break;
                    case 'gerente':
                        header("Location: index.php");
                        break;
                    default:
                        $error = "Tipo de usuário inválido.";
                        break;
                }
                exit;
            } else {
                $error = "Senha incorreta.";
            }
        } else {
            $error = "E-mail não encontrado.";
        }
        $stmt->close();
    } else {
        $error = "Por favor, preencha todos os campos.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rosa Cores e Tintas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #14592C;       /* Verde escuro principal */
            --primary-light: #1E7A41; /* Verde mais claro */
            --secondary: #732D14;     /* Marrom escuro */
            --accent: #A7D9B8;        /* Verde claro */
            --accent-dark: #0F4020;   /* Verde muito escuro */
            --danger: #BF1B1B;        /* Vermelho */
            --danger-dark: #F20707;   /* Vermelho mais vibrante */
            --light: #DFF2E7;         /* Verde muito claro */
            --dark: #0F4020;          /* Verde escuro para textos */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .login-right {
            padding: 40px;
            background: var(--light);
        }
        
        .logo-login {
            width: 120px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #c8e6d4;
            transition: all 0.3s;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
        }
        
        .input-group-text {
            background: var(--accent);
            border: 2px solid #c8e6d4;
            border-right: none;
            color: var(--dark);
        }
        
        .btn-login {
            background: var(--primary);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #c8e6d4;
        }
        
        .divider span {
            padding: 0 15px;
            color: var(--dark);
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .social-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #c8e6d4;
            transition: all 0.3s;
            color: var(--dark);
            text-decoration: none;
            background: white;
        }
        
        .social-btn:hover {
            background: var(--accent);
            transform: translateY(-2px);
            color: var(--dark);
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .feature-list i {
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--dark);
            z-index: 5;
        }
        
        .password-container {
            position: relative;
        }
        
        .alert-danger {
            background: rgba(191, 27, 27, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger-dark);
            border-radius: 8px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .form-text a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .form-text a:hover {
            text-decoration: underline;
            color: var(--accent-dark);
        }
        
        .register-link {
            color: var(--primary);
            font-weight: 600;
        }
        
        .register-link:hover {
            color: var(--accent-dark);
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 30px 20px;
                text-align: center;
            }
            
            .login-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-container">
                    <div class="row g-0">
                        <div class="col-md-6 login-left">
                            <div class="text-center text-md-start">
                                <img src="logo.jpg" alt="Rosa Cores e Tintas" class="logo-login">
                                <h2 class="mb-4">Bem-vindo de volta!</h2>
                                <p class="mb-4">Acesse sua conta para continuar aproveitando os melhores produtos de tintas do mercado.</p>
                                
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-lg"></i> Acompanhe seus pedidos</li>
                                    <li><i class="bi bi-check-lg"></i> Lista de desejos</li>
                                    <li><i class="bi bi-check-lg"></i> Ofertas exclusivas</li>
                                    <li><i class="bi bi-check-lg"></i> Histórico de compras</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6 login-right">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold" style="color: var(--dark);">Acesse sua conta</h3>
                                <p class="text-muted">Digite suas credenciais para entrar</p>
                            </div>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label" style="color: var(--dark);">E-mail</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Seu endereço de e-mail" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label" style="color: var(--dark);">Senha</label>
                                    <div class="password-container">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Sua senha" required>
                                        </div>
                                        <span class="password-toggle" onclick="togglePassword()">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                    <div class="form-text text-end">
                                        <a href="#" class="text-decoration-none">Esqueceu a senha?</a>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember" style="color: var(--dark);">Lembrar-me</label>
                                </div>
                                
                                <button type="submit" class="btn btn-login w-100 py-2 mb-3 text-white">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                                </button>
                                
                                <div class="divider">
                                    <span>ou</span>
                                </div>
                                
                                <div class="social-login">
                                    <a href="#" class="social-btn">
                                        <i class="bi bi-google"></i>
                                    </a>
                                    <a href="#" class="social-btn">
                                        <i class="bi bi-facebook"></i>
                                    </a>
                                    <a href="#" class="social-btn">
                                        <i class="bi bi-linkedin"></i>
                                    </a>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0" style="color: var(--dark);">Não tem uma conta? 
                                    <a href="register.php" class="register-link text-decoration-none">Cadastre-se agora</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Efeito de foco nos inputs
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>