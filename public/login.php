<?php
session_start();

// Inclui a conexão com o banco (que também define $base_url)
require_once '../config/db.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/index.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'register') {
            // Cadastro de usuário
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
                    $sucesso = "Cadastro realizado com sucesso! Faça login para continuar.";
                } else {
                    $erro = "Erro ao cadastrar: " . mysqli_error($conexao);
                }
            }
        } 
        else if ($_POST['action'] === 'login') {
            // Login de usuário
            $email = filtrar_entrada($conexao, $_POST['email']);
            $senha = $_POST['senha'];
            
            if (empty($email) || empty($senha)) {
                $erro = "Email e senha são obrigatórios!";
            } else {
                $sql = "SELECT id, nome, email, senha, tipo_usuario FROM usuarios WHERE email = '$email'";
                $result = mysqli_query($conexao, $sql);
                
                if (mysqli_num_rows($result) > 0) {
                    $user = mysqli_fetch_assoc($result);
                    
                    if (password_verify($senha, $user['senha'])) {
                        // Login bem-sucedido
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nome'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_type'] = $user['tipo_usuario'];
                        
                        // Redireciona conforme o tipo de usuário
                        if ($user['tipo_usuario'] === 'admin') {
                            header('Location: ' . $base_url . '/public/dashboard_admin.php');
                        } else {
                            // ✅ CORRIGIDO: Linha 'header' descomentada
                            header('Location: ' . $base_url . '/public/index.php');
                        }
                        exit; // O 'exit' é crucial após um redirecionamento
                    } else {
                        $erro = "Senha incorreta!";
                    }
                } else {
                    $erro = "Email não encontrado!";
                }
            }
        }
    }
}

// Define o título da página
$page_title = "Login - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';
?>

<section class="page-content">
    <div class="form-container">
        
        <!-- Nav Tabs para trocar entre Login e Cadastro -->
        <div class="form-tabs">
            <button class="form-tab-btn active" onclick="showTab('login-form', this)">Login</button>
            <button class="form-tab-btn" onclick="showTab('register-form', this)">Cadastro</button>
        </div>
        
        <!-- FORMULÁRIO DE LOGIN -->
        <div id="login-form" class="form-tab-content active">
            <h2 class="form-title">Bem-vindo de volta!</h2>
            <p class="form-subtitle">Faça login para acessar sua conta</p>
            
            <?php if ($erro): ?>
                <div class="msg msg-error"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required placeholder="•••">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Entrar</button>
            </form>
        </div>
        
        <!-- FORMULÁRIO DE CADASTRO -->
        <div id="register-form" class="form-tab-content">
            <h2 class="form-title">Criar Conta</h2>
            <p class="form-subtitle">Junte-se a nós e comece a adotar!</p>
            
            <?php if ($sucesso): ?>
                <div class="msg msg-success"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required placeholder="Seu nome">
                </div>
                
                <div class="form-group">
                    <label for="email_reg">E-mail:</label>
                    <input type="email" id="email_reg" name="email" required placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="senha_reg">Senha:</label>
                    <input type="password" id="senha_reg" name="senha" required placeholder="•••">
                </div>
                
                <div class="form-group">
                    <label for="senha_confirm">Confirme a Senha:</label>
                    <input type="password" id="senha_confirm" name="senha_confirm" required placeholder="•••">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Cadastrar</button>
            </form>
        </div>
        
    </div>
</section>

<script>
function showTab(tabName, btn) {
    // Esconde todos os tabs
    var tabs = document.querySelectorAll('.form-tab-content');
    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });
    
    // Remove a classe active de todos os botões
    var buttons = document.querySelectorAll('.form-tab-btn');
    buttons.forEach(function(button) {
        button.classList.remove('active');
    });
    
    // Mostra o tab selecionado
    document.getElementById(tabName).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php
// Inclui o rodapé
require_once '../includes/footer.php';
?>
