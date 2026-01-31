<?php
require "init.php";
require "conexao.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "As senhas não coincidem.";
        } else {
            // Verificar se o e-mail já existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Este e-mail já está cadastrado.";
            } else {
                // Inserir novo usuário como cliente
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_type = 'cliente';
                
                $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, ?, NOW())");
                $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $user_type);
                
                if ($insert_stmt->execute()) {
                    $success = "Cadastro realizado com sucesso! Faça login para continuar.";
                } else {
                    $error = "Erro ao cadastrar. Tente novamente.";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        }
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
    <title>Cadastro - Rosa Cores e Tintas</title>
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
        
        .register-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .register-left {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .register-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .register-right {
            padding: 40px;
            background: var(--light);
        }
        
        .logo-register {
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
        
        .btn-register {
            background: var(--primary);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
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
        
        .alert-success {
            background: rgba(20, 89, 44, 0.1);
            border: 1px solid var(--primary);
            color: var(--accent-dark);
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
        
        .login-link {
            color: var(--primary);
            font-weight: 600;
        }
        
        .login-link:hover {
            color: var(--accent-dark);
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .register-left {
                padding: 30px 20px;
                text-align: center;
            }
            
            .register-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="register-container">
                    <div class="row g-0">
                        <div class="col-md-6 register-left">
                            <div class="text-center text-md-start">
                                <img src="logo.jpg" alt="Rosa Cores e Tintas" class="logo-register">
                                <h2 class="mb-4">Junte-se a nós!</h2>
                                <p class="mb-4">Faça parte da nossa comunidade e descubra o mundo das cores com os melhores produtos do mercado.</p>
                                
                                <ul class="feature-list">
                                    <li><i class="bi bi-truck"></i> Frete grátis em compras acima de R$ 200</li>
                                    <li><i class="bi bi-percent"></i> Ofertas exclusivas para membros</li>
                                    <li><i class="bi bi-heart"></i> Lista de desejos personalizada</li>
                                    <li><i class="bi bi-star"></i> Programa de fidelidade</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6 register-right">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold" style="color: var(--dark);">Crie sua conta</h3>
                                <p class="text-muted">Preencha os dados abaixo para se cadastrar</p>
                            </div>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?php echo $success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="registerForm">
                                <div class="mb-3">
                                    <label for="name" class="form-label" style="color: var(--dark);">Nome Completo</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Seu nome completo" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label" style="color: var(--dark);">E-mail</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Seu endereço de e-mail" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label" style="color: var(--dark);">Senha</label>
                                    <div class="password-container">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Crie uma senha segura" required>
                                        </div>
                                        <span class="password-toggle" onclick="togglePassword('password')">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="form-text">
                                        Use pelo menos 8 caracteres, incluindo letras e números.
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label" style="color: var(--dark);">Confirmar Senha</label>
                                    <div class="password-container">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Digite sua senha novamente" required>
                                        </div>
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                    <div id="passwordMatch" class="form-text"></div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" required>
                                    <label class="form-check-label" for="terms" style="color: var(--dark);">
                                        Aceito os <a href="#" class="text-decoration-none">termos de uso</a> e <a href="#" class="text-decoration-none">política de privacidade</a>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-register w-100 py-2 mb-3 text-white">
                                    <i class="bi bi-person-plus me-2"></i>Criar Conta
                                </button>
                                
                                <div class="divider">
                                    <span>Já tem uma conta?</span>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0" style="color: var(--dark);">Já é cadastrado? 
                                    <a href="login.php" class="login-link text-decoration-none">Faça login aqui</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.querySelector(`[onclick="togglePassword('${fieldId}')"] i`);
            
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
        
        // Verificar força da senha
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length > 7) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 40) {
                strengthBar.style.backgroundColor = '#BF1B1B';
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#732D14';
            } else {
                strengthBar.style.backgroundColor = '#14592C';
            }
        });
        
        // Verificar se as senhas coincidem
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
            } else if (password === confirmPassword) {
                matchText.textContent = 'Senhas coincidem!';
                matchText.style.color = '#14592C';
            } else {
                matchText.textContent = 'Senhas não coincidem';
                matchText.style.color = '#BF1B1B';
            }
        });
        
        // Efeito de foco nos inputs
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.classList.remove('focused');
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>