<?php
session_start();
require_once '../config/db.php';

// 1. VERIFICAÇÕES INICIAIS
// ---------------------------------

// A. Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// B. Verificar se um ID de animal foi passado (ex: solicitar.php?id=5)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Se não tiver ID, volta para a home
    header("Location: ../home.php");
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
        
        // Iniciar transação (para garantir que ambas as queries funcionem)
        mysqli_begin_transaction($conexao);

        try {
            // A. Inserir a solicitação (REQUISITO)
            $sql_insert = "INSERT INTO solicitacoes_adocao 
                           (user_id, animal_id, telefone, endereco, mensagem) 
                           VALUES (?, ?, ?, ?, ?)";
            
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

            // Mensagem de sucesso (REQUISITO)
            $sucesso = "Solicitação enviada com sucesso! Estamos felizes por você e pelo seu novo amigo. Você será redirecionado...";

            // Redirecionamento via <meta> tag no HTML (REQUISITO)

        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conexao); // Desfaz as operações em caso de erro
            $erro = "Erro ao processar a solicitação. Tente novamente.";
        }
    }
}


// 3. BUSCAR DADOS DO ANIMAL (GET)
// ---------------------------------

// Só busca os dados do animal se o formulário AINDA não foi enviado com sucesso
if (empty($sucesso)) {
    
    $sql_get = "SELECT nome, tipo, idade, porte, descricao, imagem_url, status 
                FROM animais 
                WHERE id = ?";
                
    $stmt_get = mysqli_prepare($conexao, $sql_get);
    mysqli_stmt_bind_param($stmt_get, "i", $animal_id);
    mysqli_stmt_execute($stmt_get);
    $resultado = mysqli_stmt_get_result($stmt_get);
    
    if (mysqli_num_rows($resultado) == 1) {
        $animal = mysqli_fetch_assoc($resultado);

        // C. Se o animal não estiver "Disponível", redireciona para home
        if ($animal['status'] != 'Disponível') {
            // Usamos um 'flash message' (opcional)
            $_SESSION['flash_message'] = "Este animal não está mais disponível para adoção.";
            header("Location: ../home.php");
            exit();
        }
    } else {
        // Se o ID não existir, redireciona
        header("Location: ../home.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($sucesso)): ?>
        <meta http-equiv="refresh" content="5;url=minhas_solicitacoes.php">
    <?php endif; ?>
    <title>Solicitar Adoção - AdoteUm</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="header">
        <h1>AdoteUm</h1>
        <a href="../home.php">Voltar para Home</a>
    </header>

    <div class="container">

        <?php if (!empty($erro)): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <p class="success"><?php echo $sucesso; ?></p>
        <?php endif; ?>


        <?php if ($animal && empty($sucesso)): ?>
            
            <div class="animal-info">
                <img src="../<?php echo htmlspecialchars($animal['imagem_url']); ?>" alt="Foto de <?php echo htmlspecialchars($animal['nome']); ?>">
                <div class="animal-details">
                    <h2>Formulário de Adoção para: <?php echo htmlspecialchars($animal['nome']); ?></h2>
                    <p><strong>Idade:</strong> <?php echo htmlspecialchars($animal['idade']); ?></p>
                    <p><strong>Porte:</strong> <?php echo htmlspecialchars($animal['porte']); ?></p>
                    <p><strong>Sobre:</strong> <?php echo nl2br(htmlspecialchars($animal['descricao'])); ?></p>
                </div>
            </div>
            
            <hr style="margin: 20px 0;">

            <form action="solicitar.php?id=<?php echo $animal_id; ?>" method="POST">
                <h3>Preencha seus dados para a solicitação</h3>
                
                <div class="form-group">
                    <label for="telefone">Telefone (com DDD):</label>
                    <input type="text" id="telefone" name="telefone" required>
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço Completo (Rua, N°, Bairro, Cidade):</label>
                    <input type="text" id="endereco" name="endereco" required>
                </div>
                <div class="form-group">
                    <label for="mensagem">Por que você gostaria de adotar este animal?</label>
                    <textarea id="mensagem" name="mensagem" placeholder="Fale um pouco sobre você e o lar que o animal terá..."></textarea>
                </div>
                
                <button type="submit" class="btn">Enviar Solicitação</button>
            </form>

        <?php endif; ?>
        
    </div>

</body>
</html>

<?php
// Fecha a conexão
mysqli_close($conexao);
?>