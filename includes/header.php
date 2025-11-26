<?php
// Inicia a sessão (necessário para acessar $_SESSION em todas as páginas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega configurações globais
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Carrega Google Fonts primeiro para evitar FOUT/flash de fontes diferentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    
    <title><?php echo isset($page_title) ? $page_title : 'AdoteUm'; ?></title>
</head>
<body>
    
    <header class="navbar">
        <div class="container">
            <a href="<?php echo $base_url; ?>/public/index.php" class="navbar-brand">🐾 AdoteUm</a>
            
            <nav class="navbar-nav">
                <ul>
                    <li><a href="<?php echo $base_url; ?>/public/index.php">Home</a></li>
                    <li><a href="<?php echo $base_url; ?>/public/animais.php">Animais</a></li>
                    <li><a href="<?php echo $base_url; ?>/public/sobre.php">Sobre</a></li>
                    <li><a href="<?php echo $base_url; ?>/public/contato.php">Contato</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'solicitante'): ?>
                            <li><a href="<?php echo $base_url; ?>/public/dashboard_usuario.php">Meu Perfil</a></li>
                            <li><a href="<?php echo $base_url; ?>/public/notificacoes.php">🔔 Notificações</a></li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <li><a href="<?php echo $base_url; ?>/public/dashboard_admin.php" class="nav-admin">⚙️ Admin</a></li>
                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'operacoes'): ?>
                            <li><a href="<?php echo $base_url; ?>/public/dashboard_operacoes.php" class="nav-admin">📋 Operações</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo $base_url; ?>/public/logout.php">Sair</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>/public/login.php">Login</a></li>
                    <?php endif; ?>
                    
                </ul>
            </nav>
        </div>
    </header>
    
    <main></main>
