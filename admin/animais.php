<?php
session_start();
require_once '../config/db.php'; // ../ para voltar uma pasta

// 1. VERIFICAÇÃO DE ADMIN
// Protege a página, garantindo que apenas administradores logados possam acessá-la.
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Se não for admin, redireciona para o login de usuário
    header("Location: ../user/login.php");
    exit();
}

$erro = '';
$sucesso = '';
$upload_dir = '../uploads/'; // Diretório de upload (relativo a este arquivo)

// 2. PROCESSAMENTO DE FORMULÁRIOS (POST)
// Esta seção lida com a criação (salvar_novo) e atualização (salvar_edicao) de animais.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    // --- LÓGICA DE UPLOAD DE IMAGEM ---
    $imagem_url = ''; // Variável para guardar o caminho da imagem no DB
    $imagem_antiga = $_POST['imagem_antiga'] ?? ''; // Pega o caminho da imagem antiga (se estiver editando)

    // Verifica se um novo arquivo de imagem foi enviado
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        
        $imagem_temp = $_FILES['imagem']['tmp_name'];
        // Cria um nome único para evitar sobrescrever arquivos
        $imagem_nome = uniqid() . '_' . basename($_FILES['imagem']['name']);
        
        $target_file = $upload_dir . $imagem_nome;

        // Move o arquivo para a pasta /uploads/
        if (move_uploaded_file($imagem_temp, $target_file)) {
            // Caminho a ser salvo no DB (relativo à raiz do projeto)
            $imagem_url = 'uploads/' . $imagem_nome; 
        } else {
            $erro = "Falha ao mover o arquivo de imagem.";
        }
    } else {
        // Se nenhuma nova imagem foi enviada, mantém a antiga (na edição)
        $imagem_url = $imagem_antiga;
    }

    // Se não houver erro no upload, continua para salvar no DB
    if (empty($erro)) {
        
        // Obter dados comuns do formulário
        $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
        $tipo = mysqli_real_escape_string($conexao, $_POST['tipo']);
        $idade = mysqli_real_escape_string($conexao, $_POST['idade']);
        $porte = mysqli_real_escape_string($conexao, $_POST['porte']);
        $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
        $status = mysqli_real_escape_string($conexao, $_POST['status']);

        // Validação básica (REQUISITO)
        if (empty($nome) || empty($tipo) || empty($idade) || empty($porte) || empty($imagem_url)) {
            $erro = "Nome, Tipo, Idade, Porte e Imagem são obrigatórios.";
        } else {

            // --- AÇÃO: SALVAR NOVO ANIMAL ---
            if ($_POST['action'] == 'salvar_novo') {
                $sql = "INSERT INTO animais (nome, tipo, idade, porte, descricao, imagem_url, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conexao, $sql);
                // s = string
                mysqli_stmt_bind_param($stmt, "sssssss", $nome, $tipo, $idade, $porte, $descricao, $imagem_url, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    $sucesso = "Novo animal cadastrado com sucesso!";
                } else {
                    $erro = "Erro ao cadastrar animal: " . mysqli_error($conexao);
                }
            }
            
            // --- AÇÃO: SALVAR EDIÇÃO DO ANIMAL ---
            elseif ($_POST['action'] == 'salvar_edicao') {
                $id = (int)$_POST['id'];
                
                $sql = "UPDATE animais SET nome = ?, tipo = ?, idade = ?, porte = ?, 
                                         descricao = ?, imagem_url = ?, status = ? 
                        WHERE id = ?";
                
                $stmt = mysqli_prepare($conexao, $sql);
                // s = string, i = integer
                mysqli_stmt_bind_param($stmt, "sssssssi", $nome, $tipo, $idade, $porte, $descricao, $imagem_url, $status, $id);

                if (mysqli_stmt_execute($stmt)) {
                    $sucesso = "Animal atualizado com sucesso!";
                } else {
                    $erro = "Erro ao atualizar animal: " . mysqli_error($conexao);
                }
            }
        }
    }
}


// 3. PROCESSAMENTO DE AÇÕES (GET)
// Esta seção lida com o que exibir (listar, novo, editar) ou exclusão.
$action = $_GET['action'] ?? 'listar'; // Padrão é 'listar'
$animal_para_edicao = null; // Variável para pré-preencher o formulário de edição

if ($action == 'editar') {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "SELECT * FROM animais WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($resultado) == 1) {
            $animal_para_edicao = mysqli_fetch_assoc($resultado);
        } else {
            $erro = "Animal não encontrado.";
            $action = 'listar'; // Volta para a lista
        }
    }
}

if ($action == 'excluir') {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];

        // (Opcional, mas recomendado: excluir a imagem antiga do servidor)
        
        $sql = "DELETE FROM animais WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $sucesso = "Animal excluído com sucesso!";
        } else {
            $erro = "Erro ao excluir animal.";
        }
        $action = 'listar'; // Volta para a lista após excluir
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Gerenciar Animais</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="admin-header">
        <h1>Admin AdoteUm</h1>
        <a href="../user/logout.php">Sair</a>
    </header>

    <div class="container">

        <?php if (!empty($erro)): ?>
            <p class="msg-error"><?php echo $erro; ?></p>
        <?php endif; ?>
        <?php if (!empty($sucesso)): ?>
            <p class="msg-success"><?php echo $sucesso; ?></p>
        <?php endif; ?>

        <?php
        // Oculta a lista se estivermos na tela de 'novo' ou 'editar'
        if ($action == 'listar'): 
        ?>
            <h2>Animais Cadastrados</h2>
            <a href="animais.php?action=novo" class="btn btn-add">Adicionar Novo Animal</a>
            
            <table class="animal-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Porte</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Busca todos os animais para listar
                    $sql_list = "SELECT id, nome, tipo, porte, status, imagem_url FROM animais ORDER BY id DESC";
                    $resultado = mysqli_query($conexao, $sql_list);
                    
                    if (mysqli_num_rows($resultado) > 0):
                        while ($animal = mysqli_fetch_assoc($resultado)):
                    ?>
                        <tr>
                            <td><?php echo $animal['id']; ?></td>
                            <td><img src="../<?php echo htmlspecialchars($animal['imagem_url']); ?>" alt="Foto"></td>
                            <td><?php echo htmlspecialchars($animal['nome']); ?></td>
                            <td><?php echo htmlspecialchars($animal['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($animal['porte']); ?></td>
                            <td><?php echo htmlspecialchars($animal['status']); ?></td>
                            <td>
                                <a href="animais.php?action=editar&id=<?php echo $animal['id']; ?>" class="btn btn-edit">Editar</a>
                                <a href="animais.php?action=excluir&id=<?php echo $animal['id']; ?>" class="btn btn-delete" 
                                   onclick="return confirm('Tem certeza que deseja excluir <?php echo htmlspecialchars($animal['nome']); ?>?');">
                                   Excluir
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7">Nenhum animal cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php 
        // 4.2 TELA DE ADIÇÃO (novo) OU EDIÇÃO (editar)
        elseif ($action == 'novo' || $action == 'editar'):
            
            // Define os valores para o formulário
            // Se for 'editar', usa $animal_para_edicao. Se for 'novo', usa '' (vazio).
            $edit = ($action == 'editar');
            $form_action = $edit ? 'salvar_edicao' : 'salvar_novo';
            $titulo = $edit ? 'Editar Animal' : 'Adicionar Novo Animal';
            
            $id_val = $edit ? $animal_para_edicao['id'] : '';
            $nome_val = $edit ? $animal_para_edicao['nome'] : '';
            $tipo_val = $edit ? $animal_para_edicao['tipo'] : '';
            $idade_val = $edit ? $animal_para_edicao['idade'] : '';
            $porte_val = $edit ? $animal_para_edicao['porte'] : '';
            $status_val = $edit ? $animal_para_edicao['status'] : 'Disponível';
            $descricao_val = $edit ? $animal_para_edicao['descricao'] : '';
            $imagem_val = $edit ? $animal_para_edicao['imagem_url'] : '';
        ?>

            <h2><?php echo $titulo; ?></h2>
            <a href="animais.php">Voltar para a lista</a>

            <form action="animais.php" method="POST" class="form-animal" enctype="multipart/form-data">
                
                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                <input type="hidden" name="id" value="<?php echo $id_val; ?>">
                <input type="hidden" name="imagem_antiga" value="<?php echo $imagem_val; ?>">

                <div class="form-group">
                    <label for="nome">Nome do Animal:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_val); ?>" required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="Cachorro" <?php echo ($tipo_val == 'Cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                        <option value="Gato" <?php echo ($tipo_val == 'Gato') ? 'selected' : ''; ?>>Gato</option>
                        <option value="Outro" <?php echo ($tipo_val == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="idade">Idade (ex: "Filhote", "2 anos"):</label>
                    <input type="text" id="idade" name="idade" value="<?php echo htmlspecialchars($idade_val); ?>" required>
                </div>

                <div class="form-group">
                    <label for="porte">Porte:</label>
                    <select id="porte" name="porte" required>
                        <option value="Pequeno" <?php echo ($porte_val == 'Pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                        <option value="Médio" <?php echo ($porte_val == 'Médio') ? 'selected' : ''; ?>>Médio</option>
                        <option value="Grande" <?php echo ($porte_val == 'Grande') ? 'selected' : ''; ?>>Grande</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Disponível" <?php echo ($status_val == 'Disponível') ? 'selected' : ''; ?>>Disponível</option>
                        <option value="Em Processo" <?php echo ($status_val == 'Em Processo') ? 'selected' : ''; ?>>Em Processo</option>
                        <option value="Adotado" <?php echo ($status_val == 'Adotado') ? 'selected' : ''; ?>>Adotado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição (História do animal):</label>
                    <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($descricao_val); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="imagem">Foto do Animal:</label>
                    <?php if ($edit && !empty($imagem_val)): ?>
                        <p>Imagem atual: <img src="../<?php echo htmlspecialchars($imagem_val); ?>" height="50"> (Deixe em branco para manter)</p>
                    <?php endif; ?>
                    <input type="file" id="imagem" name="imagem" accept="image/*" <?php echo $edit ? '' : 'required'; ?>>
                </div>

                <button type="submit" class="btn btn-add"><?php echo $titulo; ?></button>
            </form>

        <?php endif; // Fim do if/else $action ?>

    </div>

</body>
</html>

<?php
// Fecha a conexão no final do script
mysqli_close($conexao);
?>