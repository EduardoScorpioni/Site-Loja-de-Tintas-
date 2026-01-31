<?php
require "init.php";
include "header.php";
?>

<style>
.contact-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.contact-header {
    background: linear-gradient(135deg, #14592C 0%, #0F4020 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 20px 20px;
}

.contact-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.contact-card-header {
    background: linear-gradient(135deg, #14592C 0%, #1E7A41 100%);
    color: white;
    padding: 1.5rem;
    font-weight: 700;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
}

.contact-card-header i {
    margin-right: 10px;
    font-size: 1.5rem;
}

.contact-card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: #0F4020;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #A7D9B8;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s;
    background: white;
}

.form-control:focus {
    border-color: #14592C;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.btn-primary {
    background: #14592C;
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
    color: white;
    font-size: 1.1rem;
}

.btn-primary:hover {
    background: #0F4020;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(20, 89, 44, 0.3);
}

.contact-info {
    background: #DFF2E7;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.contact-info h5 {
    color: #14592C;
    margin-bottom: 1rem;
}

.contact-info ul {
    list-style: none;
    padding: 0;
}

.contact-info li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
}

.contact-info i {
    color: #14592C;
    margin-right: 10px;
    font-size: 1.2rem;
}

.alert-success {
    background: rgba(20, 89, 44, 0.1);
    border: 2px solid #14592C;
    color: #14592C;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    font-weight: 600;
}

.alert-error {
    background: rgba(191, 27, 27, 0.1);
    border: 2px solid #BF1B1B;
    color: #BF1B1B;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    font-weight: 600;
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .contact-card-body {
        padding: 1.5rem;
    }
    
    .contact-header {
        padding: 2rem 0;
    }
}
</style>

<div class="contact-container">
    <div class="contact-header">
        <div class="container">
            <h1 class="display-5 fw-bold text-center"><i class="bi bi-envelope me-3"></i>Fale Conosco</h1>
            <p class="lead text-center">Estamos aqui para ajudar! Entre em contato conosco.</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="contact-card">
            <div class="contact-card-header">
                <i class="bi bi-chat-dots"></i>Envie sua Mensagem
            </div>
            <div class="contact-card-body">
                <form method="POST" action="enviar_contato.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Seu Nome *</label>
                                <input type="text" name="nome" class="form-control" 
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Seu E-mail *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assunto *</label>
                        <input type="text" name="assunto" class="form-control" 
                               placeholder="Ex: Dúvida sobre produto, Problema com pedido, etc." required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mensagem *</label>
                        <textarea name="mensagem" class="form-control" 
                                  placeholder="Descreva sua dúvida, sugestão ou problema detalhadamente..." 
                                  required></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-send me-2"></i>Enviar Mensagem
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="contact-info">
            <h5><i class="bi bi-info-circle"></i>Outras Formas de Contato</h5>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li>
                            <i class="bi bi-telephone"></i>
                            <strong>Telefone:</strong> (11) 9999-9999
                        </li>
                        <li>
                            <i class="bi bi-whatsapp"></i>
                            <strong>WhatsApp:</strong> (11) 8888-8888
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <strong>E-mail:</strong> contato@lojatintas.com
                        </li>
                        <li>
                            <i class="bi bi-clock"></i>
                            <strong>Horário:</strong> Seg-Sex: 8h-18h
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Enviando...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>

<?php 
include "footer.php"; 
?>