<?php
session_start();

// Inclui a conexão com o banco
require_once '../config/db.php';

// Verifica se é operacoes
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'operacoes') {
    header('Location: ' . $base_url . '/public/login.php');
    exit;
}

// Define o título da página
$page_title = "Dashboard Operações - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

$mensagem = '';
$tipo_mensagem = ''; // 'success' ou 'error'

// ============================================
// PROCESSAR AÇÕES
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // AGENDAR RETIRADA
    if ($_POST['action'] === 'agendar') {
        $ordem_id = (int)$_POST['ordem_id'];
        $data_agendada = filtrar_entrada($conexao, $_POST['data_agendada']);
        $notas = isset($_POST['notas']) ? filtrar_entrada($conexao, $_POST['notas']) : '';

        // Converter formato datetime-local para MySQL e formatado
        $data_mysql = datetime_local_para_mysql($data_agendada);
        
        if ($data_mysql === null) {
            $mensagem = "❌ Formato de data/hora inválido!";
            $tipo_mensagem = 'error';
        } else {
            $sql = "UPDATE ordens_servico SET status = 'Agendada', data_agendada = '$data_mysql', notas = '$notas' WHERE id = $ordem_id";
            if (mysqli_query($conexao, $sql)) {
                // Obter detalhes do pedido para notificação
                $sqlOrder = "SELECT user_solicitante_id, animal_id, solicitacao_adocao_id FROM ordens_servico WHERE id = $ordem_id";
                $resultOrder = mysqli_query($conexao, $sqlOrder);
                $order = mysqli_fetch_assoc($resultOrder);
                
                // Obter nome do animal
                $sqlAnimal = "SELECT nome FROM animais WHERE id = " . $order['animal_id'];
                $resultAnimal = mysqli_query($conexao, $sqlAnimal);
                $animal = mysqli_fetch_assoc($resultAnimal);

                // Formatar data para exibição visual
                $data_formatada = formatar_data_hora_visual($data_mysql);
                $mensagem_notif = escape($conexao, "Sua retirada do animal '{$animal['nome']}' foi agendada para " . $data_formatada . ".");
                
                $sqlNotif = "INSERT INTO notificacoes (user_id, ordem_servico_id, solicitacao_adocao_id, tipo, mensagem) 
                             VALUES ({$order['user_solicitante_id']}, $ordem_id, {$order['solicitacao_adocao_id']}, 'agendamento', '$mensagem_notif')";
                mysqli_query($conexao, $sqlNotif);

                $mensagem = "✅ Retirada agendada com sucesso! Usuário notificado.";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "❌ Erro ao agendar retirada: " . mysqli_error($conexao);
                $tipo_mensagem = 'error';
            }
        }

    }
    // CONFIRMAR RETIRADA
    if ($_POST['action'] === 'confirmar_retirada') {
        $ordem_id = (int)$_POST['ordem_id'];
        $responsavel = filtrar_entrada($conexao, $_POST['responsavel_assinatura']);
        $cpf_responsavel = $_POST['cpf_responsavel'];
        $data_retirada_manual = filtrar_entrada($conexao, $_POST['data_retirada_manual']);
        
        // Validar CPF usando função consolidada
        $validacao_cpf = validar_cpf($cpf_responsavel);
        if (!$validacao_cpf['valido']) {
            $mensagem = "❌ CPF inválido! O CPF deve ter 11 dígitos.";
            $tipo_mensagem = 'error';
        } else {
            // Converter data_retirada_manual para formato MySQL
            $data_retirada_formatada = datetime_local_para_mysql($data_retirada_manual);
            
            if ($data_retirada_formatada === null) {
                $mensagem = "❌ Data/hora inválida!";
                $tipo_mensagem = 'error';
            } else {
                $data_retirada_timestamp = date('Y-m-d H:i:s');

                $sql = "UPDATE ordens_servico SET status = 'Retirada Confirmada', responsavel_assinatura = '$responsavel', cpf_responsavel = '{$validacao_cpf['cpf_limpo']}', data_retirada_manual = '$data_retirada_formatada', data_retirada = '$data_retirada_timestamp' WHERE id = $ordem_id";
                if (mysqli_query($conexao, $sql)) {
                    // Update adoption request to 'Finalizada'
                    $sqlGetSol = "SELECT solicitacao_adocao_id FROM ordens_servico WHERE id = $ordem_id";
                    $resultSol = mysqli_query($conexao, $sqlGetSol);
                    $sol = mysqli_fetch_assoc($resultSol);
                    
                    $sqlUpdateSol = "UPDATE solicitacoes_adocao SET status = 'Finalizada', data_finalizacao = '$data_retirada_timestamp' WHERE id = " . $sol['solicitacao_adocao_id'];
                    mysqli_query($conexao, $sqlUpdateSol);

                    // Also update animal status to 'Adotado'
                    $sqlGetAnimal = "SELECT animal_id FROM ordens_servico WHERE id = $ordem_id";
                    $resultAnimal = mysqli_query($conexao, $sqlGetAnimal);
                    $animalData = mysqli_fetch_assoc($resultAnimal);
                    $sqlUpdateAnimal = "UPDATE animais SET status = 'Adotado' WHERE id = " . $animalData['animal_id'];
                    mysqli_query($conexao, $sqlUpdateAnimal);

                    // Get order details for notification
                    $sqlOrder = "SELECT user_solicitante_id FROM ordens_servico WHERE id = $ordem_id";
                    $resultOrder = mysqli_query($conexao, $sqlOrder);
                    $order = mysqli_fetch_assoc($resultOrder);

                    // Create notification for user with manual date
                    $data_formatada_notif = formatar_data_hora_visual($data_retirada_formatada);
                    $mensagem_notif = escape($conexao, "✅ Sua adoção foi finalizada! Data de retirada: " . $data_formatada_notif . ". Responsável: " . $responsavel);
                    $sqlNotif = "INSERT INTO notificacoes (user_id, ordem_servico_id, tipo, mensagem) 
                                 VALUES ({$order['user_solicitante_id']}, $ordem_id, 'retirada_confirmada', '$mensagem_notif')";
                    mysqli_query($conexao, $sqlNotif);

                    $mensagem = "✅ Retirada confirmada! Adoção finalizada com sucesso.";
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = "❌ Erro ao confirmar retirada: " . mysqli_error($conexao);
                    $tipo_mensagem = 'error';
                }
            }
        }
    }

    // CANCELAR ORDEM DE SERVIÇO
    if ($_POST['action'] === 'cancelar') {
        $ordem_id = (int)$_POST['ordem_id'];
        
        $sql = "UPDATE ordens_servico SET status = 'Cancelada' WHERE id = $ordem_id";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = "✅ Ordem de serviço cancelada.";
            $tipo_mensagem = 'success';
        } else {
            $mensagem = "❌ Erro ao cancelar: " . mysqli_error($conexao);
            $tipo_mensagem = 'error';
        }
    }
}

// ============================================
// BUSCAR ORDENS DE SERVIÇO
// ============================================

$tab_ativa = isset($_GET['tab']) ? $_GET['tab'] : 'pendentes';
$ordens = [];

if ($tab_ativa === 'pendentes') {
    $sql = "SELECT os.*, a.nome as animal_nome, u.nome as user_nome, u.email as user_email 
            FROM ordens_servico os
            LEFT JOIN animais a ON os.animal_id = a.id
            LEFT JOIN usuarios u ON os.user_solicitante_id = u.id
            WHERE os.status = 'Pendente'
            ORDER BY os.criado_em DESC";
} elseif ($tab_ativa === 'agendadas') {
    $sql = "SELECT os.*, a.nome as animal_nome, u.nome as user_nome, u.email as user_email 
            FROM ordens_servico os
            LEFT JOIN animais a ON os.animal_id = a.id
            LEFT JOIN usuarios u ON os.user_solicitante_id = u.id
            WHERE os.status = 'Agendada'
            ORDER BY os.data_agendada ASC";
} elseif ($tab_ativa === 'finalizadas') {
    $sql = "SELECT os.*, a.nome as animal_nome, u.nome as user_nome, u.email as user_email 
            FROM ordens_servico os
            LEFT JOIN animais a ON os.animal_id = a.id
            LEFT JOIN usuarios u ON os.user_solicitante_id = u.id
            WHERE os.status = 'Retirada Confirmada'
            ORDER BY os.data_retirada DESC";
}

$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ordens[] = $row;
    }
}

?>

<div class="page-content">
    <div class="container">
        <h1>📋 Dashboard de Operações</h1>
        <p style="color: #666; margin-bottom: 30px;">Gerencie as ordens de serviço e agende retiradas de animais.</p>

        <?php if (!empty($mensagem)): ?>
            <div class="msg msg-<?php echo $tipo_mensagem === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="form-tabs">
            <button class="form-tab-btn <?php echo $tab_ativa === 'pendentes' ? 'active' : ''; ?>" 
                    onclick="window.location='?tab=pendentes'">
                ⏳ Pendentes
            </button>
            <button class="form-tab-btn <?php echo $tab_ativa === 'agendadas' ? 'active' : ''; ?>" 
                    onclick="window.location='?tab=agendadas'">
                📅 Agendadas
            </button>
            <button class="form-tab-btn <?php echo $tab_ativa === 'finalizadas' ? 'active' : ''; ?>" 
                    onclick="window.location='?tab=finalizadas'">
                ✅ Finalizadas
            </button>
        </div>

        <!-- ORDENS -->
        <?php if (empty($ordens)): ?>
            <p style="text-align: center; color: #999; margin: 40px 0;">Nenhuma ordem de serviço nesta categoria.</p>
        <?php else: ?>
            <div style="display: grid; gap: 20px; margin-top: 30px;">
                <?php foreach ($ordens as $ordem): ?>
                    <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #007bff;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;"><strong>Animal:</strong></p>
                                <p style="font-size: 16px; color: #222; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($ordem['animal_nome'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;"><strong>Solicitante:</strong></p>
                                <p style="font-size: 16px; color: #222; margin: 0;"><?php echo htmlspecialchars($ordem['user_nome'] ?? 'N/A'); ?></p>
                                <p style="font-size: 12px; color: #999; margin: 5px 0 0 0;"><?php echo htmlspecialchars($ordem['user_email'] ?? 'N/A'); ?></p>
                            </div>
                        </div>

                        <?php if ($tab_ativa === 'agendadas' && !empty($ordem['data_agendada'])): ?>
                            <div style="background: #f0f4ff; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                                <p style="margin: 0; font-weight: 600; color: #007bff;">
                                    📅 Agendado para: <?php echo date('d/m/Y às H:i', strtotime($ordem['data_agendada'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($tab_ativa === 'finalizadas' && !empty($ordem['data_retirada'])): ?>
                            <div style="background: #f0f9f0; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                                <p style="margin: 0; font-weight: 600; color: #28a745;">
                                    ✅ Retirada em: <?php echo date('d/m/Y às H:i', strtotime($ordem['data_retirada'])); ?>
                                </p>
                                <p style="margin: 8px 0 0 0; font-size: 14px;">
                                    Responsável pela assinatura: <strong><?php echo htmlspecialchars($ordem['responsavel_assinatura'] ?? 'N/A'); ?></strong>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($tab_ativa === 'pendentes'): ?>
                            <form method="POST" style="margin-bottom: 15px;">
                                <input type="hidden" name="action" value="agendar">
                                <input type="hidden" name="ordem_id" value="<?php echo $ordem['id']; ?>">
                                
                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data e Hora da Retirada:</label>
                                    <input type="datetime-local" name="data_agendada" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                                </div>

                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Notas (opcional):</label>
                                    <textarea name="notas" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; font-family: var(--font-principal); resize: vertical; min-height: 70px;"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Agendar Retirada</button>
                            </form>
                        <?php elseif ($tab_ativa === 'agendadas'): ?>
                            <form method="POST" style="margin-bottom: 15px;">
                                <input type="hidden" name="action" value="confirmar_retirada">
                                <input type="hidden" name="ordem_id" value="<?php echo $ordem['id']; ?>">
                                
                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Responsável pela Assinatura no Contrato:</label>
                                    <input type="text" name="responsavel_assinatura" placeholder="Nome completo do responsável" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                                </div>

                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">CPF do Responsável:</label>
                                    <input type="text" name="cpf_responsavel" placeholder="000.000.000-00" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;" title="Formato: 000.000.000-00">
                                </div>

                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data e Hora da Retirada (real):</label>
                                    <input type="datetime-local" name="data_retirada_manual" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;" title="Data e hora da retirada efetiva (pode ser diferente da agendada)">
                                    <small style="color: #666; display: block; margin-top: 3px;">ℹ️ Preencha com a data/hora real da retirada (pode ser diferente da agendada)</small>
                                </div>

                                <button type="submit" class="btn btn-primary">Confirmar Retirada</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($tab_ativa !== 'finalizadas'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="cancelar">
                                <input type="hidden" name="ordem_id" value="<?php echo $ordem['id']; ?>">
                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Tem certeza?')">Cancelar Ordem</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
