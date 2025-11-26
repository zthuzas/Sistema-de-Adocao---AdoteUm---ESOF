<?php
session_start();

// Define o título da página
$page_title = "Animais - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

// Inclui a conexão com o banco
require_once '../config/db.php';

// Preparar filtros e busca (tipo, idade, termo)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$filtro_idade = isset($_GET['idade']) ? trim($_GET['idade']) : '';

// Obter listas para os selects (tipos e idades existentes)
$tipos = [];
$idades = [];
$resTipos = mysqli_query($conexao, "SELECT DISTINCT tipo FROM animais ORDER BY tipo ASC");
if ($resTipos) {
    while ($r = mysqli_fetch_assoc($resTipos)) $tipos[] = $r['tipo'];
}
$resIdades = mysqli_query($conexao, "SELECT DISTINCT idade FROM animais ORDER BY idade ASC");
if ($resIdades) {
    while ($r = mysqli_fetch_assoc($resIdades)) $idades[] = $r['idade'];
}

// Construir a query com filtros usando funções de escape consolidadas
$where = "status = 'Disponível'";
if ($q !== '') {
    $q_esc = escape($conexao, $q);
    $where .= " AND (nome LIKE '%$q_esc%' OR descricao LIKE '%$q_esc%')";
}
if ($filtro_tipo !== '' && $filtro_tipo !== 'all') {
    $tipo_esc = escape($conexao, $filtro_tipo);
    $where .= " AND tipo = '$tipo_esc'";
}
if ($filtro_idade !== '' && $filtro_idade !== 'all') {
    $idade_esc = escape($conexao, $filtro_idade);
    $where .= " AND idade = '$idade_esc'";
}

$sql = "SELECT * FROM animais WHERE $where ORDER BY criado_em DESC";

// Executa a query e checa erros
$resultado = mysqli_query($conexao, $sql);
if ($resultado === false) {
    error_log('SQL error (animais.php): ' . mysqli_error($conexao));
    echo '<div class="msg msg-error">Erro ao carregar animais: ' . htmlspecialchars(mysqli_error($conexao)) . '</div>';
    $resultado = [];
}
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Todos os Animais Disponíveis</h1>
        <p class="section-subtitle">Conheça todos os nossos amigos que estão esperando por um lar cheio de amor.</p>

        <form method="GET" class="form-filtro" style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0;align-items:center;">
            <input type="text" name="q" placeholder="Buscar por nome ou descrição" value="<?php echo htmlspecialchars($q); ?>" style="flex:1; padding:8px 10px; border:1px solid #ccc; border-radius:6px;">

            <select name="tipo" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">
                <option value="all">Todas as espécies</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($filtro_tipo === $t) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="idade" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">
                <option value="all">Todas as idades</option>
                <?php foreach ($idades as $i): ?>
                    <option value="<?php echo htmlspecialchars($i); ?>" <?php echo ($filtro_idade === $i) ? 'selected' : ''; ?>><?php echo htmlspecialchars($i); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary" style="padding:8px 12px;">Filtrar</button>
            <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-secondary" style="padding:8px 12px;">Limpar</a>
        </form>

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
                            <?php echo htmlspecialchars($animal['descricao']); ?>
                        </p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="card-actions">
                                <a href="<?php echo $base_url; ?>/public/solicitar.php?id=<?php echo $animal['id']; ?>" class="btn btn-secondary">
                                    Adotar
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo $base_url; ?>/public/login.php" class="btn btn-primary">Fazer Login para Adotar</a>
                        <?php endif; ?>
                    </div>
                </div>
            
            <?php
                endwhile;
            else:
                echo "<p>Nenhum animal disponível no momento.</p>";
            endif;
            
            mysqli_close($conexao);
            ?>
            
        </div>
    </div>
</section>

<?php
// Inclui o rodapé
require_once '../includes/footer.php';
?>
