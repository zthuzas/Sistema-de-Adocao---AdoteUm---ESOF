<?php
session_start();
require_once '../config/db.php'; // ../ para voltar uma pasta

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$erro = '';
$sucesso = '';

// 2. PROCESSAMENTO DE AÇÕES (Aprovar / Rejeitar)
// Verificamos se uma ação (via GET) foi solicitada
if (isset($_GET['action']) && isset($_GET['id'])) {

    $action = $_GET['action'];
    $solicitacao_id = (int)$_GET['id'];
    $animal_id = 0; // Vamos descobrir esse ID

    // Primeiro, precisamos do ID do animal associado a esta solicitação
    $sql_get_animal = "SELECT animal_id FROM solicitacoes_adocao WHERE id = ?";
    $stmt_get = mysqli_prepare($conexao, $sql_get_animal);
    mysqli_stmt_bind_param($stmt_get, "i", $solicitacao_id);
    mysqli_stmt_execute($stmt_get);
    $result = mysqli_stmt_get_result($stmt_get);
    if ($row = mysqli_fetch_assoc($result)) {
        $animal_id = (int)$row['animal_id'];
    }
    mysqli_stmt_close($stmt_get);

    if ($animal_id > 0) {
        
        // Inicia a Transação
        mysqli_begin_transaction($conexao);

        try {
            // --- AÇÃO: APROVAR ---
            if ($action == 'aprovar') {
                
                // 1. Marcar esta solicitação como 'Aprovada'
                $sql_aprova = "UPDATE solicitacoes_adocao SET status = 'Aprovada' WHERE id = ?";
                $stmt1 = mysqli_prepare($conexao, $sql_aprova);
                mysqli_stmt_bind_param($stmt1, "i", $solicitacao_id);
                mysqli_stmt_execute($stmt1);

                // 2. Marcar o animal como 'Adotado'
                $sql_adota_animal = "UPDATE animais SET status = 'Adotado' WHERE id = ?";
                $stmt2 = mysqli_prepare($conexao, $sql_adota_animal);
                mysqli_stmt_bind_param($stmt2, "i", $animal_id);
                mysqli_stmt_execute($stmt2);

                // 3. Rejeitar todas as *outras* solicitações 'Pendentes' para este *mesmo animal*
                $sql_rejeita_outros = "UPDATE solicitacoes_adocao 
                                     SET status = 'Rejeitada' 
                                     WHERE animal_id = ? AND status = 'Pendente'";
                $stmt3 = mysqli_prepare($conexao, $sql_rejeita_outros);
                mysqli_stmt_bind_param($stmt3, "i", $animal_id);
                mysqli_stmt_execute($stmt3);

                $sucesso = "Adoção aprovada! O animal agora está 'Adotado' e outras solicitações foram atualizadas.";

            } 
            // --- AÇÃO: REJEITAR ---
            elseif ($action == 'rejeitar') {
                
                // 1. Marcar esta solicitação como 'Rejeitada'
                $sql_rejeita = "UPDATE solicitacoes_adocao SET status = 'Rejeitada' WHERE id = ?";
                $stmt1 = mysqli_prepare($conexao, $sql_rejeita);
                mysqli_stmt_bind_param($stmt1, "i", $solicitacao_id);
                mysqli_stmt_execute($stmt1);

                // 2. Devolver o animal ao status 'Disponível'
                $sql_libera_animal = "UPDATE animais SET status = 'Disponível' WHERE id = ?";
                $stmt2 = mysqli_prepare($conexao, $sql_libera_animal);
                mysqli_stmt_bind_param($stmt2, "i", $animal_id);
                mysqli_stmt_execute($stmt2);
                
                $sucesso = "Solicitação rejeitada. O animal foi devolvido para 'Disponível'.";
            }
            
            // Se tudo deu certo, comita a transação
            mysqli_commit($conexao);

        } catch (mysqli_sql_exception $exception) {
            // Se algo falhou, desfaz tudo
            mysqli_rollback($conexao);
            $erro = "Erro ao processar a ação: " . $exception->getMessage();
        }

    } else {
        $erro = "Solicitação ou animal não encontrado.";
    }

    // Limpa a URL (remove os parâmetros action= e id=) e recarrega a página
    // Isso evita re-executar a ação ao atualizar a página (F5)
    header("Location: solicitacoes.php?sucesso=" . urlencode($sucesso) . "&erro=" . urlencode($erro));
    exit();
}

// Mensagens de feedback (vindas do redirecionamento)
if (isset($_GET['sucesso'])) $sucesso = htmlspecialchars($_GET['sucesso']);
if (isset($_GET['erro'])) $erro = htmlspecialchars($_GET['erro']);


// 3. BUSCAR DADOS PARA EXIBIÇÃO
// Usamos JOINs para pegar os nomes do usuário e do animal
$sql_list = "SELECT 
                s.id, s.data_solicitacao, s.status, s.telefone, s.endereco, s.mensagem,
                u.nome AS user_nome,
                a.nome AS animal_nome
             FROM solicitacoes_adocao s
             JOIN usuarios u ON s.user_id = u.id
             JOIN animais a ON s.animal_id = a.id
             ORDER BY 
                CASE s.status
                    WHEN 'Pendente' THEN 1
                    WHEN 'Aprovada' THEN 2
                    WHEN 'Rejeitada' THEN 3
                END, 
                s.data_solicitacao DESC";

$resultado = mysqli_query($conexao, $sql_list);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Gerenciar Solicitações</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="admin-header">
        <h1>Admin AdoteUm</h1>
        <a href="animais.php">Gerenciar Animais</a> </header>

    <div class="container">
        <h2>Gerenciar Solicitações de Adoção</h2>

        <?php if (!empty($erro)): ?>
            <p class="msg-error"><?php echo $erro; ?></p>
        <?php endif; ?>
        <?php if (!empty($sucesso)): ?>
            <p class="msg-success"><?php echo $sucesso; ?></p>
        <?php endif; ?>

        <table class="solicitacoes-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Animal</th>
                    <th>Contato (Tel/Endereço)</th>
                    <th>Mensagem</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($resultado) > 0):
                    while ($solicitacao = mysqli_fetch_assoc($resultado)):
                ?>
                    <tr>
                        <td><?php echo $solicitacao['id']; ?></td>
                        <td><?php echo htmlspecialchars($solicitacao['user_nome']); ?></td>
                        <td><?php echo htmlspecialchars($solicitacao['animal_nome']); ?></td>
                        
                        <td>
                            <strong>Tel:</strong> <?php echo htmlspecialchars($solicitacao['telefone']); ?><br>
                            <strong>End:</strong> <?php echo htmlspecialchars($solicitacao['endereco']); ?>
                        </td>
                        
                        <td><?php echo htmlspecialchars($solicitacao['mensagem']); ?></td>
                        
                        <td><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></td>
                        
                        <td>
                            <span class="status status-<?php echo htmlspecialchars($solicitacao['status']); ?>">
                                <?php echo htmlspecialchars($solicitacao['status']); ?>
                            </span>
                        </td>
                        
                        <td class="acoes-cell">
                            <?php if ($solicitacao['status'] == 'Pendente'): ?>
                                
                                <a href="solicitacoes.php?action=aprovar&id=<?php echo $solicitacao['id']; ?>" 
                                   class="btn btn-aprovar" 
                                   onclick="return confirm('Tem certeza que deseja APROVAR esta solicitação? Esta ação é irreversível e marcará o animal como adotado.');">
                                   Aprovar
                                </a>
                                
                                <a href="solicitacoes.php?action=rejeitar&id=<?php echo $solicitacao['id']; ?>" 
                                   class="btn btn-rejeitar"
                                   onclick="return confirm('Tem certeza que deseja REJEITAR esta solicitação? O animal voltará a ficar disponível.');">
                                   Rejeitar
                                </a>
                                
                            <?php else: ?>
                                <span>Processado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="8">Nenhuma solicitação encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>

<?php
// Fecha a conexão no final do script
mysqli_close($conexao);
?>