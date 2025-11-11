<?php
// Inicia a sessão OBRIGATORIAMENTE no topo do arquivo
session_start();

// Inclui o arquivo de conexão
require_once '../config/db.php'; // Usa ../ para voltar um nível (de /user para /)

$erro = '';

// Verifica se o formulário foi enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Obter e filtrar dados do formulário
    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    // Validações simples
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido!";
    } else {
        
        // 2. Verificar se o e-mail já existe
        $sql_check = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_check = mysqli_prepare($conexao, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $erro = "Este e-mail já está cadastrado!";
        } else {
            // 3. Criptografar a senha (REQUISITO)
            $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
            
            // 4. Definir a role padrão (REQUISITO)
            $role_padrao = 'user';

            // 5. Inserir no banco de dados (Usando Prepared Statements para segurança)
            $sql_insert = "INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)";
            
            $stmt_insert = mysqli_prepare($conexao, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ssss", $nome, $email, $hash_senha, $role_padrao);

            if (mysqli_stmt_execute($stmt_insert)) {
                // 6. Cadastro bem-sucedido: Iniciar sessão e redirecionar
                
                // Obter o ID do usuário recém-criado
                $novo_user_id = mysqli_insert_id($conexao);

                $_SESSION['user_id'] = $novo_user_id;
                $_SESSION['user_nome'] = $nome;
                $_SESSION['role'] = $role_padrao;

                // Redireciona para a home (REQUISITO)
                header("Location: home.php");
                exit(); // Interrompe o script após o redirecionamento
            } else {
                $erro = "Erro ao cadastrar. Tente novamente. " . mysqli_error($conexao);
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
    mysqli_close($conexao);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - AdoteUm</title>
    <link rel="stylesheet" href="../assets/css/style.css">  
    
</head>
<body>
    <div class="container">
        <h2>Cadastro no AdoteUm</h2>
        
        <?php if (!empty($erro)): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn">Cadastrar</button>
        </form>
        
        <div class="login-link">
            Já tem conta? <a href="login.php">Faça login aqui</a>
        </div>
    </div>
</body>
</html>