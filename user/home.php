<?php
// 1. Iniciar sessão e verificar login
session_start();

// Se 'user_id' não estiver na sessão, redireciona para login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir conexão com o banco
require_once '../config/db.php';

// 2. Buscar animais disponíveis
// **LINHA CORRIGIDA ABAIXO**
$sql = "SELECT id, nome, idade, porte, imagem_url 
        FROM animais 
        WHERE status = 'Disponível' 
        ORDER BY criado_em DESC";

$resultado = mysqli_query($conexao, $sql);

// Guardar o nome do usuário da sessão para saudação
$user_nome = $_SESSION['user_nome'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - AdoteUm</title>
   <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="header">
        <h1>AdoteUm</h1>
        <a href="logout.php">Sair</a>
    </header>

    <div class="container">
        
        <h2 class="boas-vindas">Olá, <?php echo htmlspecialchars($user_nome); ?>! Encontre seu novo amigo:</h2>

        <div class="grid-animais">
            
            <?php
            // 3. Loop para exibir os cards
            if (mysqli_num_rows($resultado) > 0) {
                
                // Itera sobre cada animal encontrado
                while ($animal = mysqli_fetch_assoc($resultado)) {
            ?>
                
                <div class="card">
                    
                    <img src="../<?php echo htmlspecialchars($animal['imagem_url']); ?>" alt="Foto de <?php echo htmlspecialchars($animal['nome']); ?>">
                    
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($animal['nome']); ?></h3>
                        
                        <p><strong>Idade:</strong> <?php echo htmlspecialchars($animal['idade']); ?></p>
                        <p><strong>Porte:</strong> <?php echo htmlspecialchars($animal['porte']); ?></p>
                        
                        <a href="solicitar.php?id=<?php echo $animal['id']; ?>" class="btn-detalhes">
                            Ver Detalhes
                        </a>
                    </div>
                </div>
                <?php
                } // Fim do while
            } else {
                // 4. Mensagem caso não haja animais
                echo "<p class='no-results'>Nenhum animal disponível para adoção no momento. 😢</p>";
            }
            
            // Fecha a conexão após o uso
            mysqli_close($conexao);
            ?>
            
        </div> </div> </body>
</html>