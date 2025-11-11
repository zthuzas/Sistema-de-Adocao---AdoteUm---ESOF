<?php
// Vamos definir o caminho base para links e assets
// Isso ajuda a evitar problemas com ../
$base_url = '/adoteum'; // Altere se o nome da sua pasta no 'www' for diferente
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    
    <title><?php echo isset($page_title) ? $page_title : 'AdoteUm'; ?></title>
</head>
<body>
    
    <header class="navbar">
        <div class="container">
            <a href="<?php echo $base_url; ?>/index.php" class="navbar-brand">AdoteUm</a>
            
            <nav class="navbar-nav">
                <ul>
                    <li><a href="<?php echo $base_url; ?>/index.php">Home</a></li> <li><a href="<?php echo $base_url; ?>/animais.php">Animais</a></li> <li><a href="#">Sobre</a></li> <li><a href="#">Contato</a></li> <li><a href="<?php echo $base_url; ?>/admin/dashboard.php" class="nav-admin">Admin</a></li>
                    
                    </ul>
            </nav>
        </div>
    </header>
    
    <main></main>