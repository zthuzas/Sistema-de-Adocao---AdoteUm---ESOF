<?php
// Inicia a sessão OBRIGATORIAMENTE no topo do arquivo
session_start();

// Inclui o arquivo de conexão
require_once '../config/db.php'; // Usa ../ para voltar um nível

$erro = '';

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Obter e filtrar dados
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    if (empty($email) || empty($senha)) {
        $erro = "E-mail e senha são obrigatórios!";
    } else {
        
        // 2. Buscar o usuário pelo e-mail (Usando Prepared Statements)
        $sql = "SELECT id, nome, senha, role FROM usuarios WHERE email = ?";
        
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        // 3. Verificar se o usuário existe
        if (mysqli_stmt_num_rows($stmt) == 1) {
            
            // 4. Obter os dados do usuário
            mysqli_stmt_bind_result($stmt, $id, $nome, $hash_senha, $role);
            mysqli_stmt_fetch($stmt);

            // 5. Verificar a senha (REQUISITO: password_verify)
            if (password_verify($senha, $hash_senha)) {
                
                // 6. Senha correta: Iniciar sessão
                $_SESSION['user_id'] = $id;
                $_SESSION['user_nome'] = $nome;
                $_SESSION['role'] = $role;

                // 7. Redirecionamento baseado na Role (REQUISITO)
                if ($role == 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../home.php");
                }
                exit(); // Interrompe o script

            } else {
                // Senha incorreta
                $erro = "E-mail ou senha inválidos!";
            }
        } else {
            // Usuário (e-mail) não encontrado
            $erro = "E-mail ou senha inválidos!";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexao);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AdoteUm</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Login - AdoteUm</h2>
        
        <?php if (!empty($erro)): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>

        <div class="register-link">
            Não tem conta? <a href="register.php">Cadastre-se aqui</a>
        </div>
    </div>
</body>
</html>