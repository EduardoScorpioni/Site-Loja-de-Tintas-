<?php
require "init.php";

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados do formulário
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $assunto = trim($_POST['assunto']);
    $mensagem = trim($_POST['mensagem']);
    
    // Validações básicas
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $_SESSION['error_message'] = "Por favor, preencha todos os campos obrigatórios!";
        header("Location: fale_conosco.php");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Por favor, insira um e-mail válido!";
        header("Location: fale_conosco.php");
        exit;
    }
    
    try {
        // Configurações do e-mail
        $to = "trabalhodemit@gmail.com"; // E-mail que receberá as mensagens
        $subject = "Contato via Site: " . $assunto;
        
        // Cabeçalhos do e-mail
        $headers = "From: trabalhodemit@gmail.com" . "\r\n" .
                   "Reply-To: " . $email . "\r\n" .
                   "Content-Type: text/html; charset=UTF-8" . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        // Corpo do e-mail em HTML
        $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #14592C 0%, #0F4020 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                    .field { margin-bottom: 15px; padding: 10px; background: white; border-radius: 5px; border-left: 4px solid #14592C; }
                    .field strong { color: #14592C; }
                    .message { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; white-space: pre-wrap; }
                    .footer { margin-top: 20px; padding: 15px; background: #DFF2E7; border-radius: 5px; text-align: center; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>📧 Novo Contato via Site - Loja de Tintas</h2>
                    </div>
                    <div class='content'>
                        <div class='field'>
                            <strong>👤 Nome:</strong> " . htmlspecialchars($nome) . "
                        </div>
                        <div class='field'>
                            <strong>📧 E-mail:</strong> " . htmlspecialchars($email) . "
                        </div>
                        <div class='field'>
                            <strong>📝 Assunto:</strong> " . htmlspecialchars($assunto) . "
                        </div>
                        <div class='field'>
                            <strong>💬 Mensagem:</strong>
                        </div>
                        <div class='message'>
                            " . nl2br(htmlspecialchars($mensagem)) . "
                        </div>
                        <div class='footer'>
                            <p>🕐 Enviado em: " . date('d/m/Y H:i:s') . "</p>
                            <p>📍 Via Sistema de Contato - Loja de Tintas</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Versão texto simples para clientes de e-mail que não suportam HTML
        $text_body = "
            NOVO CONTATO VIA SITE - LOJA DE TINTAS
            
            Nome: " . $nome . "
            E-mail: " . $email . "
            Assunto: " . $assunto . "
            
            MENSAGEM:
            " . $mensagem . "
            
            Enviado em: " . date('d/m/Y H:i:s') . "
            Via Sistema de Contato
        ";
        
        // Enviar e-mail
        if (mail($to, $subject, $body, $headers)) {
            $_SESSION['success_message'] = "✅ Mensagem enviada com sucesso! Entraremos em contato em breve.";
            
            // Opcional: Enviar confirmação para o cliente
            $confirm_subject = "Confirmação de Recebimento - Loja de Tintas";
            $confirm_headers = "From: trabalhodemit@gmail.com" . "\r\n" .
                              "Content-Type: text/html; charset=UTF-8" . "\r\n" .
                              "X-Mailer: PHP/" . phpversion();
            
            $confirm_body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #14592C 0%, #0F4020 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                        .footer { margin-top: 20px; padding: 15px; background: #DFF2E7; border-radius: 5px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>✅ Mensagem Recebida</h2>
                        </div>
                        <div class='content'>
                            <p>Olá <strong>" . htmlspecialchars($nome) . "</strong>,</p>
                            <p>Recebemos sua mensagem e agradecemos pelo contato!</p>
                            <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
                            <p>Nossa equipe irá analisar sua solicitação e retornaremos em breve.</p>
                            <div class='footer'>
                                <p><strong>Loja de Tintas</strong></p>
                                <p>📞 Telefone: (11) 9999-9999</p>
                                <p>📧 E-mail: contato@lojatintas.com</p>
                                <p>🕐 Horário: Segunda a Sexta, 8h às 18h</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Enviar e-mail de confirmação para o cliente
            mail($email, $confirm_subject, $confirm_body, $confirm_headers);
            
        } else {
            $_SESSION['error_message'] = "❌ Erro ao enviar mensagem. O servidor de e-mail pode não estar configurado. Entre em contato diretamente pelo telefone.";
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Erro ao processar solicitação: " . $e->getMessage();
    }
    
} else {
    $_SESSION['error_message'] = "Método de requisição inválido!";
}

header("Location: fale_conosco.php");
exit;
?>