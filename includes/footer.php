<?php
// Carrega configurações globais
require_once __DIR__ . '/../config/config.php';
?>

    </main>

    <footer class="footer-simple">
        <div class="container">
            
            <div class="footer-simple-logo">
                <a href="<?php echo $base_url; ?>/public/index.php">🐾 AdoteUm</a>
            </div>

            <nav class="footer-simple-nav">
                <a href="<?php echo $base_url; ?>/public/index.php">Home</a>
                <span>|</span>
                <a href="<?php echo $base_url; ?>/public/animais.php">Animais</a>
                <span>|</span>
                <a href="<?php echo $base_url; ?>/public/sobre.php">Sobre</a>
                <span>|</span>
                <a href="<?php echo $base_url; ?>/public/contato.php">Contato</a>
            </nav>

            <div class="footer-simple-info">
                <p>AdoteUm • Conectando corações com vidas</p>
                <p>&copy; <?php echo date('Y'); ?>. Todos os direitos reservados.</p>
            </div>
            
        </div>
    </footer>

</body>
</html> 