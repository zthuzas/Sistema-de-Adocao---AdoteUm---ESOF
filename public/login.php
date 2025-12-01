<?php
session_start();
require_once '../config/db.php';

// --- VERIFICAÇÃO DE SESSÃO ATIVA ---
// Se já está logado, redireciona automaticamente com base no tipo
if (isset($_SESSION['user_id'])) {
    
    // Verifica primeiro se é o usuário de operações específico ou tem o tipo 'operacoes'
    if  (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'operacoes') {
        header('Location: ' . $base_url . '/public/dashboard_operacoes.php');
    } 
    // Depois verifica se é admin
    elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header('Location: ' . $base_url . '/public/dashboard_admin.php');
    } 
    // Por fim, usuário comum
    else {
        header('Location: ' . $base_url . '/public/index.php');
    }
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filtrar_entrada($conexao, $_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {
        // Busca o usuário
        $sql = "SELECT id, nome, email, senha, tipo_usuario FROM usuarios WHERE email = '$email'";
        $result = mysqli_query($conexao, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($senha, $user['senha'])) {
                // Login com sucesso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo_usuario'];
                $_SESSION['email'] = $user['email']; 

                // --- LÓGICA DE REDIRECIONAMENTO PÓS-LOGIN ---
                
                // 1. Prioridade: Usuário de Operações (por e-mail ou tipo)
                if ($user['email'] === 'operacoes@gmail.com' || $user['tipo_usuario'] === 'operacoes') {
                    header('Location: ' . $base_url . '/public/dashboard_operacoes.php');
                }
                // 2. Administrador Geral
                elseif ($user['tipo_usuario'] === 'admin') {
                    header('Location: ' . $base_url . '/public/dashboard_admin.php');
                }
                // 3. Usuário Comum
                else {
                    header('Location: ' . $base_url . '/public/index.php');
                }
                exit; // O 'exit' é crucial após um redirecionamento

            } else {
                $erro = "Senha incorreta!";
            }
        } else {
            $erro = "Usuário não encontrado!";
        }
    }
}

$page_title = "Login - AdoteUm";
require_once '../includes/header.php';
?>

<section class="page-content">
    <div class="form-container">
        <h2 class="form-title">Acesse sua Conta</h2>
        <p class="form-subtitle">Bem-vindo de volta!</p>

        <?php if ($erro): ?>
            <div class="msg msg-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required placeholder="seu@email.com">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required placeholder="••••••">
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">Entrar</button>
        </form>
        
        <div class="form-switch-link">
            <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
        </div>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>s