<?php
session_start();

// Inclui a conexão com o banco (que define $base_url)
require_once '../config/db.php';

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/login.php');
    exit;
}

// Define o título da página
$page_title = "Meu Perfil - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

// Busca informações do usuário
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM usuarios WHERE id = $user_id";
$result_user = mysqli_query($conexao, $sql_user);
$user = mysqli_fetch_assoc($result_user);

$sql_solicitacoes = "SELECT a.*, s.status, s.criado_em, s.animal_id FROM solicitacoes_adocao s JOIN animais a ON s.animal_id = a.id WHERE s.user_id = $user_id ORDER BY s.criado_em DESC";
$result_solicitacoes = mysqli_query($conexao, $sql_solicitacoes);
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Meu Perfil</h1>
        
        <div style="max-width: 800px; margin: 0 auto;">
            
            <!-- Informações do Usuário -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-content">
                    <h2 style="margin-top: 0;">Informações Pessoais</h2>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($user['nome']); ?></p>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Membro desde:</strong> <?php echo date('d/m/Y', strtotime($user['criado_em'])); ?></p>
                    <a href="<?php echo $base_url; ?>/public/index.php" class="btn btn-secondary">Voltar</a>
                </div>
            </div>
            
            <!-- Solicitações de Adoção -->
            <h2 style="margin-top: 40px; margin-bottom: 20px;">Minhas Solicitações de Adoção</h2>
            
            <?php if (mysqli_num_rows($result_solicitacoes) > 0): ?>
                <div style="display: grid; gap: 20px;">
                    <?php while ($solicitacao = mysqli_fetch_assoc($result_solicitacoes)): ?>
                        <div class="card">
                            <div class="card-content">
                                <h3><?php echo htmlspecialchars($solicitacao['nome']); ?></h3>
                                <p>
                                    <strong>Status:</strong> 
                                    <span style="padding: 5px 10px; border-radius: 5px; background-color: 
                                        <?php echo $solicitacao['status'] === 'Aprovada' ? '#d4edda' : ($solicitacao['status'] === 'Rejeitada' ? '#f8d7da' : '#fff3cd'); ?>;">
                                        <?php echo htmlspecialchars($solicitacao['status']); ?>
                                    </span>
                                </p>
                                <p><strong>Data da Solicitação:</strong> <?php echo date('d/m/Y', strtotime($solicitacao['criado_em'])); ?></p>
                                <a href="<?php echo $base_url; ?>/public/solicitar.php?id=<?php echo $solicitacao['animal_id']; ?>" class="btn btn-primary">Ver Detalhes</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-content">
                        <p>Você ainda não fez nenhuma solicitação de adoção.</p>
                        <a href="<?php echo $base_url; ?>/public/animais.php" class="btn btn-primary">Explorar Animais</a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<?php
mysqli_close($conexao);
// Inclui o rodapé
require_once '../includes/footer.php';
?>
