<?php
// Define o título da página (será usado no header.php)
$page_title = "Home - AdoteUm";

// Inclui o cabeçalho (menu, <head>, etc.)
require 'includes/header.php';

// Inclui a conexão com o banco para buscar os animais
require 'config/db.php';

// Busca 3 animais "Disponíveis" para a home
$sql = "SELECT * FROM animais 
        WHERE status = 'Disponível' 
        ORDER BY criado_em DESC 
        LIMIT 3";

$resultado = mysqli_query($conexao, $sql);
?>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">Encontre seu novo melhor amigo</h1> 
        <p class="hero-subtitle">Conectamos corações apaixonados por animais com pets que precisam de um lar cheio de amor.</p> 
        <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-primary btn-hero">Ver Animais Disponíveis</a> 
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
            
        </div> <div class="section-cta">
            <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-secondary">Ver Todos os Animais</a>
        </div>
    </div>
</section>

<?php
// Inclui o rodapé (encerra a página)
require 'includes/footer.php';
?>