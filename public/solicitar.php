<?php
session_start();

// Define o título da página
$page_title = "Solicitar Adoção - AdoteUm";

// Inclui a conexão com o banco (que define $base_url)
require_once '../config/db.php';

// Inclui o cabeçalho
require_once '../includes/header.php';

// 1. VERIFICAÇÕES INICIAIS
// ---------------------------------

// A. Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/public/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// B. Verificar se um ID de animal foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . $base_url . "/public/animais.php");
    exit();
}
$animal_id = (int)$_GET['id'];

// Variáveis de controle
$erro = '';
$sucesso = '';
$animal = null;


// 2. PROCESSAMENTO DO FORMULÁRIO (POST)
// ---------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obter dados do formulário
    $telefone = mysqli_real_escape_string($conexao, $_POST['telefone']);
    $endereco = mysqli_real_escape_string($conexao, $_POST['endereco']);
    $mensagem = mysqli_real_escape_string($conexao, $_POST['mensagem']);

    // Validação simples
    if (empty($telefone) || empty($endereco)) {
        $erro = "Telefone e Endereço são obrigatórios!";
    } else {
        
        // Iniciar transação
        mysqli_begin_transaction($conexao);

        try {
            // A. Inserir a solicitação
            $sql_insert = "INSERT INTO solicitacoes_adocao 
                           (user_id, animal_id, telefone, endereco, mensagem, status, criado_em) 
                           VALUES (?, ?, ?, ?, ?, 'Pendente', NOW())";
            
            $stmt_insert = mysqli_prepare($conexao, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "iisss", $user_id, $animal_id, $telefone, $endereco, $mensagem);
            mysqli_stmt_execute($stmt_insert);

            // B. Atualizar o status do animal para 'Em Processo'
            $sql_update = "UPDATE animais SET status = 'Em Processo' WHERE id = ?";
            $stmt_update = mysqli_prepare($conexao, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "i", $animal_id);
            mysqli_stmt_execute($stmt_update);

            // Se tudo deu certo, confirma a transação
            mysqli_commit($conexao);

            // Mensagem de sucesso
            $sucesso = "Solicitação enviada com sucesso! Você será redirecionado para seu perfil.";

        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conexao);
            $erro = "Erro ao processar a solicitação. Tente novamente.";
        }
    }
}


// 3. BUSCAR DADOS DO ANIMAL (GET)
// ---------------------------------

if (empty($sucesso)) {
    
    $sql_get = "SELECT nome, tipo, idade, descricao, imagem_url, status 
                FROM animais 
                WHERE id = ?";
                
    $stmt_get = mysqli_prepare($conexao, $sql_get);
    mysqli_stmt_bind_param($stmt_get, "i", $animal_id);
    mysqli_stmt_execute($stmt_get);
    $resultado = mysqli_stmt_get_result($stmt_get);
    
    if (mysqli_num_rows($resultado) == 1) {
        $animal = mysqli_fetch_assoc($resultado);

        // Se o animal não estiver "Disponível", redireciona
        if ($animal['status'] != 'Disponível') {
            header("Location: " . $base_url . "/public/animais.php");
            exit();
        }
    } else {
        // Se o ID não existir, redireciona
            header("Location: " . $base_url . "/public/animais.php");
        exit();
    }
}
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Formulário de Adoção</h1>
        <p class="section-subtitle">Preencha os dados abaixo para solicitar a adoção</p>
        
        <div style="max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            
            <!-- Informações do Animal -->
            <?php if ($animal): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($animal['imagem_url']); ?>" alt="Foto de <?php echo htmlspecialchars($animal['nome']); ?>" class="card-img">
                    <div class="card-content">
                        <h2 class="card-title"><?php echo htmlspecialchars($animal['nome']); ?></h2>
                        <p class="card-info">
                            <?php echo htmlspecialchars($animal['idade']); ?> • <?php echo htmlspecialchars($animal['tipo']); ?> 
                        </p>
                        <p class="card-desc">
                            <?php echo htmlspecialchars($animal['descricao']); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <div class="form-container" style="margin: 0; padding: 30px; max-width: none;">
                
                <?php if ($erro): ?>
                    <div class="msg msg-error"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="msg msg-success"><?php echo htmlspecialchars($sucesso); ?></div>
                    <script>
                        setTimeout(function() {
                            window.location.href = '<?php echo $base_url; ?>/public/dashboard_usuario.php';
                        }, 3000);
                    </script>
                <?php else: ?>

                    <form action="solicitar.php?id=<?php echo $animal_id; ?>" method="POST">
                        
                        <div class="form-group">
                            <label for="telefone">Telefone (com DDD):</label>
                            <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="endereco">Endereço Completo:</label>
                            <input type="text" id="endereco" name="endereco" placeholder="Rua, N°, Bairro, Cidade" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mensagem">Por que você gostaria de adotar este animal?</label>
                            <textarea id="mensagem" name="mensagem" rows="5" placeholder="Fale um pouco sobre você e o lar que o animal terá..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full">Enviar Solicitação de Adoção</button>
                        
                        <div class="form-switch-link">
                            <a href="<?php echo $base_url; ?>/public/animais.php">Voltar para animais</a>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
            
        </div>
    </div>
</section>

<?php
mysqli_close($conexao);
// Inclui o rodapé
require_once '../includes/footer.php';
?>
