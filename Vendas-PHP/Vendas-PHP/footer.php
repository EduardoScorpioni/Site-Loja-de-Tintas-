</div> <!-- Fechamento da div container do conteúdo -->

<footer class="footer mt-5 text-white py-5" style="background-color: #0F4020;">

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>Rosa Cores e Tintas</h5>
                <p>A melhor loja de tintas do Brasil, com produtos de qualidade e preços competitivos.</p>
                <div class="mt-3">
                    <a href="https://www.facebook.com/RosaCoreseTintas/" class="social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/rosacoresetintas/" class="social-icon"><i class="bi bi-instagram"></i></a>
                    <a href="https://api.whatsapp.com/send/?phone=%2B5518996843037&text=Ol%C3%A1%2C+gostaria+de+realizar+um+or%C3%A7amento&type=phone_number&app_absent=0" class="social-icon"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.youtube.com/watch?v=UrpskboSYZ8&list=TLPQMDMwOTIwMjUBUCR2u84ejw&index=4" class="social-icon"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Institucional</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Quem Somos</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Nossas Lojas</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Trabalhe Conosco</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Termos de Uso</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Ajuda</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Como Comprar</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Prazos de Entrega</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Política de Trocas</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Dúvidas Frequentes</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Contato</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-geo-alt"></i> Av. Cel. José Soares Marcondes, 4615 A - Jardim Bongiovani, Pres. Prudente - SP, 19050-230</li>
                    <li><i class="bi bi-telephone"></i> (18) 9 9684-3037</li>
                    <li><i class="bi bi-whatsapp"></i> (18) 9 9684-3037</li>
                    <li><i class="bi bi-envelope"></i> contato@rosacoresetintas.com.br</li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>Formas de Pagamento</h5>
                <div>
                    <img src="https://via.placeholder.com/40/ffffff/000000?text=V" alt="Visa" class="me-2">
                    <img src="https://via.placeholder.com/40/ffffff/000000?text=MC" alt="Mastercard" class="me-2">
                    <img src="https://via.placeholder.com/40/ffffff/000000?text=AE" alt="American Express" class="me-2">
                    <img src="https://via.placeholder.com/40/ffffff/000000?text=EL" alt="Elo" class="me-2">
                    <img src="https://via.placeholder.com/40/ffffff/000000?text=P" alt="Pix" class="me-2">
                </div>
            </div>
            <div class="col-md-6 mb-3 text-md-end">
                <h5>Segurança</h5>
                <img src="https://via.placeholder.com/80/ffffff/000000?text=SSL" alt="SSL" class="me-2">
                <img src="https://via.placeholder.com/80/ffffff/000000?text=SG" alt="Site Seguro">
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Rosa Cores e Tintas - Todos os direitos reservados - CNPJ: 42.965.767/0001-03</p>
        </div>
    </div>
</footer>

<!-- SCRIPTS CORRETAMENTE ORGANIZADOS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- REMOVA o jQuery se não for estritamente necessário -->
<!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<script>
    // Inicialização do carousel (se necessário)
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar componentes Bootstrap manualmente se necessário
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl)
        });
        
        // Se você usar Owl Carousel, inicialize aqui:
        // $('.owl-carousel').owlCarousel();
    });
</script>
</body>
</html>