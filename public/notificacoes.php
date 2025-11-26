<?php
session_start();

// Inclui a conexão com o banco
require_once '../config/db.php';

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/login.php');
    exit;
}

// Define o título da página
$page_title = "Notificações - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

$mensagem = '';

// ============================================
// PROCESSAR AÇÕES
// ============================================

// Marcar notificação como lida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'marcar_lida') {
    $notificacao_id = (int)$_POST['notificacao_id'];
    $sql = "UPDATE notificacoes SET lida = TRUE WHERE id = $notificacao_id AND user_id = " . $_SESSION['user_id'];
    mysqli_query($conexao, $sql);
    
    // Retornar JSON para requisição AJAX
    if (!empty($_POST['ajax'])) {
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Marcar todas como lidas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'marcar_todas_lidas') {
    $sql = "UPDATE notificacoes SET lida = TRUE WHERE user_id = " . $_SESSION['user_id'] . " AND lida = FALSE";
    mysqli_query($conexao, $sql);
    $mensagem = "✅ Todas as notificações marcadas como lidas!";
}

// ============================================
// OBTER NOTIFICAÇÕES
// ============================================

$sql = "SELECT * FROM notificacoes WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY criado_em DESC";
$result = mysqli_query($conexao, $sql);
$notificacoes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notificacoes[] = $row;
}

// Separar por lida/não lida
$nao_lidas = array_filter($notificacoes, function($n) { return !$n['lida']; });
$lidas = array_filter($notificacoes, function($n) { return $n['lida']; });

?>

<style>
    .notificacoes-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .notificacoes-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 20px;
    }

    .notificacoes-header h1 {
        font-size: 28px;
        color: #333;
        margin: 0;
    }

    .notificacoes-stats {
        display: flex;
        gap: 20px;
        font-size: 14px;
    }

    .stat-badge {
        background: #f0f0f0;
        padding: 5px 12px;
        border-radius: 20px;
        color: #666;
    }

    .stat-badge.unread {
        background: #ffd700;
        color: #333;
        font-weight: bold;
    }

    .btn-marcar-todas {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s;
    }

    .btn-marcar-todas:hover {
        background: #0056b3;
    }

    .btn-marcar-todas:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .notificacoes-lista {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .notificacao-item {
        background: #f9f9f9;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin-bottom: 12px;
        border-radius: 4px;
        transition: all 0.3s;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .notificacao-item.nao-lida {
        background: #fff9e6;
        border-left-color: #ffc107;
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
    }

    .notificacao-item.lida {
        opacity: 0.7;
        border-left-color: #ccc;
    }

    .notificacao-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .notificacao-conteudo {
        flex: 1;
        min-width: 0;
    }

    .notificacao-tipo {
        display: inline-block;
        font-size: 11px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 12px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .notificacao-tipo.aprovacao {
        background: #d4edda;
        color: #155724;
    }

    .notificacao-tipo.agendamento {
        background: #cfe2ff;
        color: #084298;
    }

    .notificacao-tipo.retirada_confirmada {
        background: #d1ecf1;
        color: #0c5460;
    }

    .notificacao-tipo.rejeicao {
        background: #f8d7da;
        color: #721c24;
    }

    .notificacao-mensagem {
        color: #333;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .notificacao-timestamp {
        font-size: 12px;
        color: #999;
    }

    .notificacao-acoes {
        display: flex;
        gap: 8px;
        align-items: center;
        white-space: nowrap;
    }

    .btn-marcar-lida {
        background: #007bff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s;
    }

    .btn-marcar-lida:hover {
        background: #0056b3;
    }

    .notificacao-item.lida .btn-marcar-lida {
        display: none;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 20px;
    }

    .empty-state h2 {
        color: #666;
        margin: 0 0 10px 0;
    }

    .secao-notificacoes {
        margin-bottom: 30px;
    }

    .secao-titulo {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }
</style>

<div class="notificacoes-container">
    
    <div class="notificacoes-header">
        <div>
            <h1>📬 Notificações</h1>
        </div>
        <div class="notificacoes-stats">
            <span class="stat-badge unread">
                🔔 <?php echo count($nao_lidas); ?> não lidas
            </span>
            <span class="stat-badge">
                ✓ <?php echo count($lidas); ?> lidas
            </span>
            <?php if (count($nao_lidas) > 0): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="marcar_todas_lidas">
                    <button type="submit" class="btn-marcar-todas">Marcar todas como lidas</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <!-- NOTIFICAÇÕES NÃO LIDAS -->
    <?php if (count($nao_lidas) > 0): ?>
        <div class="secao-notificacoes">
            <div class="secao-titulo">🔔 Novas Notificações</div>
            <ul class="notificacoes-lista">
                <?php foreach ($nao_lidas as $notif): ?>
                    <li class="notificacao-item nao-lida" id="notif-<?php echo $notif['id']; ?>">
                        <div class="notificacao-conteudo">
                            <span class="notificacao-tipo <?php echo htmlspecialchars($notif['tipo']); ?>">
                                <?php 
                                $tipo_labels = [
                                    'aprovacao' => '✅ Aprovada',
                                    'agendamento' => '📅 Agendamento',
                                    'retirada_confirmada' => '🎉 Finalizada',
                                    'rejeicao' => '❌ Rejeitada'
                                ];
                                echo $tipo_labels[$notif['tipo']] ?? $notif['tipo'];
                                ?>
                            </span>
                            <div class="notificacao-mensagem">
                                <?php echo htmlspecialchars($notif['mensagem']); ?>
                            </div>
                            <div class="notificacao-timestamp">
                                <?php echo formatar_data($notif['criado_em']); ?>
                            </div>
                        </div>
                        <div class="notificacao-acoes">
                            <button class="btn-marcar-lida" onclick="marcarLida(<?php echo $notif['id']; ?>)">
                                ✓ Lido
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- NOTIFICAÇÕES LIDAS -->
    <?php if (count($lidas) > 0): ?>
        <div class="secao-notificacoes">
            <div class="secao-titulo">📖 Anteriores</div>
            <ul class="notificacoes-lista">
                <?php foreach ($lidas as $notif): ?>
                    <li class="notificacao-item lida" id="notif-<?php echo $notif['id']; ?>">
                        <div class="notificacao-conteudo">
                            <span class="notificacao-tipo <?php echo htmlspecialchars($notif['tipo']); ?>">
                                <?php 
                                $tipo_labels = [
                                    'aprovacao' => '✅ Aprovada',
                                    'agendamento' => '📅 Agendamento',
                                    'retirada_confirmada' => '🎉 Finalizada',
                                    'rejeicao' => '❌ Rejeitada'
                                ];
                                echo $tipo_labels[$notif['tipo']] ?? $notif['tipo'];
                                ?>
                            </span>
                            <div class="notificacao-mensagem">
                                <?php echo htmlspecialchars($notif['mensagem']); ?>
                            </div>
                            <div class="notificacao-timestamp">
                                <?php echo formatar_data($notif['criado_em']); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- ESTADO VAZIO -->
    <?php if (empty($notificacoes)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h2>Nenhuma notificação</h2>
            <p>Você será notificado quando houver atualizações em suas solicitações de adoção.</p>
        </div>
    <?php endif; ?>

</div>

<script>
function marcarLida(notifId) {
    const formData = new FormData();
    formData.append('action', 'marcar_lida');
    formData.append('notificacao_id', notifId);
    formData.append('ajax', '1');
    
    fetch('<?php echo $base_url; ?>/public/notificacoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const notifEl = document.getElementById('notif-' + notifId);
            notifEl.classList.remove('nao-lida');
            notifEl.classList.add('lida');
            
            // Recarregar página após 1 segundo para atualizar contadores
            setTimeout(() => location.reload(), 500);
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
