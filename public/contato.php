<?php
session_start();

// Define o título da página
$page_title = "Contato - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

$mensagem_enviada = '';
$erro_contato = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars($_POST['nome'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $assunto = htmlspecialchars($_POST['assunto'] ?? '');
    $mensagem = htmlspecialchars($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $erro_contato = "Todos os campos são obrigatórios!";
    } else {
        // Aqui você pode adicionar a lógica para enviar um email
        // Por enquanto, apenas exibimos uma mensagem de sucesso
        $mensagem_enviada = "Mensagem enviada com sucesso! Entraremos em contato em breve.";
    }
}
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Entre em Contato</h1>
        <p class="section-subtitle">Nos mande suas dúvidas e sugestões</p>
        
        <div class="form-container" style="max-width: 600px;">
            
            <?php if ($mensagem_enviada): ?>
                <div class="msg msg-success"><?php echo $mensagem_enviada; ?></div>
            <?php endif; ?>
            
            <?php if ($erro_contato): ?>
                <div class="msg msg-error"><?php echo $erro_contato; ?></div>
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
                    <label for="assunto">Assunto:</label>
                    <input type="text" id="assunto" name="assunto" required placeholder="Assunto da mensagem">
                </div>
                
                <div class="form-group">
                    <label for="mensagem">Mensagem:</label>
                    <textarea id="mensagem" name="mensagem" rows="5" required placeholder="Digite sua mensagem..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Enviar Mensagem</button>
            </form>
        </div>
    </div>
</section>

<?php
// Inclui o rodapé
require_once '../includes/footer.php';
?>
