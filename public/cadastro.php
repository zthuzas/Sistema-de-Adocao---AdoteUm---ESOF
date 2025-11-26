<?php
session_start();

// Inclui a conexão com o banco (que define $base_url)
require_once '../config/db.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/index.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filtrar_entrada($conexao, $_POST['nome']);
    $email = filtrar_entrada($conexao, $_POST['email']);
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];
    
    // Validações usando funções consolidadas
    if (empty($nome) || empty($email) || empty($senha) || empty($senha_confirm)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif (!validar_email($email)) {
        $erro = "Email inválido!";
    } elseif (!validar_senhas_iguais($senha, $senha_confirm)) {
        $erro = "As senhas não correspondem!";
    } elseif (!validar_forca_senha($senha, 6)) {
        $erro = "A senha deve ter no mínimo 6 caracteres!";
    } elseif (email_ja_existe($conexao, $email)) {
        $erro = "Este email já está registrado!";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $tipo_usuario = 'solicitante';
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, criado_em) VALUES ('$nome', '$email', '$senha_hash', '$tipo_usuario', NOW())";
            
        if (mysqli_query($conexao, $sql)) {
            $sucesso = "Cadastro realizado com sucesso! Redirecionando...";
            header("Refresh: 2; url=login.php");
        } else {
            $erro = "Erro ao cadastrar: " . mysqli_error($conexao);
        }
    }
}

// Define o título da página
$page_title = "Cadastro - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';
?>

<section class="page-content">
    <div class="form-container">
        <h2 class="form-title">Criar Sua Conta</h2>
        <p class="form-subtitle">Junte-se a nós e comece a adotar!</p>
        
        <?php if ($erro): ?>
            <div class="msg msg-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="msg msg-success"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required placeholder="Seu nome">
            </div>
            
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required placeholder="seu@email.com">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required placeholder="••••••">
            </div>
            
            <div class="form-group">
                <label for="senha_confirm">Confirme a Senha:</label>
                <input type="password" id="senha_confirm" name="senha_confirm" required placeholder="••••••">
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">Cadastrar</button>
        </form>
        
        <div class="form-switch-link">
            <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
        </div>
    </div>
</section>

<?php
// Inclui o rodapé
require_once '../includes/footer.php';
?>
