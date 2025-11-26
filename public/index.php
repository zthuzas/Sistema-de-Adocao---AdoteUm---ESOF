<?php
session_start();

// Define o título da página
$page_title = "Home - AdoteUm";

// Inclui o cabeçalho (menu, <head>, etc.)
require_once '../includes/header.php';

// Inclui a conexão com o banco
require_once '../config/db.php';

// Busca 3 animais "Disponíveis" para a home
$sql = "SELECT * FROM animais WHERE status = 'Disponível' ORDER BY criado_em DESC LIMIT 3";

// Executa a query e checa erros
$resultado = mysqli_query($conexao, $sql);
if ($resultado === false) {
    error_log('SQL error (index.php): ' . mysqli_error($conexao));
    echo '<div class="msg msg-error">Erro ao carregar animais: ' . htmlspecialchars(mysqli_error($conexao)) . '</div>';
    $resultado = [];
}
?>
<section class="hero">
    <div class="container">
           <div class="hero-container">
              <h1 class="hero-title">Encontre seu novo melhor amigo!</h1> 
              <p class="hero-subtitle">Conectamos corações apaixonados por animais com pets que precisam de um lar cheio de amor.</p> 
              <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-primary btn-hero">Ver Animais Disponíveis</a> 
           </div>
    </div>
</section>

<section class="animais-destaque">
    <div class="container">
        <h2 class="section-title">Animais Disponíveis</h2>
        <p class="section-subtitle">Conheça alguns dos nossos amigos que estão esperando por você.</p>
        
        <div class="grid-animais">
            
            <?php
            if (mysqli_num_rows($resultado) > 0):
                while ($animal = mysqli_fetch_assoc($resultado)):
            ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($animal['imagem_url']); ?>" alt="Foto de <?php echo htmlspecialchars($animal['nome']); ?>" class="card-img">
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($animal['nome']); ?></h3>
                        <p class="card-info">
                            <?php echo htmlspecialchars($animal['idade']); ?> • <?php echo htmlspecialchars($animal['tipo']); ?> 
                        </p>
                        <p class="card-desc">
                            <?php echo htmlspecialchars(substr($animal['descricao'], 0, 100)); ?>...
                        </p>
                        <a href="<?php echo $base_url; ?>/public/solicitar.php?id=<?php echo $animal['id']; ?>" class="btn btn-primary">Ver Detalhes</a>
                    </div>
                </div>
            
            <?php
                endwhile;
            else:
                echo "<p>Nenhum animal disponível no momento.</p>";
            endif;
            
            // Fecha a conexão
            mysqli_close($conexao);
            ?>
            
        </div>
        <div class="section-cta">
            <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-secondary">Ver Todos os Animais</a>
        </div>
    </div>
</section>

<section id="onde-nos-localizar" class="onde-localizar" style="padding:40px 0; background:#fafafa; border-top:1px solid #eee;">
    <div class="container">
        <h2 class="section-title">Onde nos localizar?</h2>
        <p class="section-subtitle">Venha nos visitar!</p>

        <div style="max-width:1000px; margin:18px auto;">
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.08);">
                <iframe 
                    src="https://maps.google.com/maps?q=https://maps.app.goo.gl/28Z4yQEtX9KCWLsE7&output=embed" 
                    style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" 
                    allowfullscreen="" 
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <p style="text-align:center; margin-top:10px; color:#666; font-size:14px;">
                <a href="https://maps.app.goo.gl/28Z4yQEtX9KCWLsE7" target="_blank" rel="noopener" style="color:#007bff; text-decoration:none;">Abrir no Google Maps</a>
            </p>
        </div>
    </div>
</section>

<?php
// Inclui o rodapé (encerra a página)
require_once '../includes/footer.php';
?>
