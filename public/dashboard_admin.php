<?php
session_start();

// Inclui a conexão com o banco (que define $base_url)
require_once '../config/db.php';

// Verifica se é admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . $base_url . '/public/login.php');
    exit;
}

// Define o título da página
$page_title = "Dashboard Admin - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';

$mensagem = '';
$tab_ativa = isset($_GET['tab']) ? $_GET['tab'] : 'animais';
$modo_edicao = false;
$animal_edicao = null;
$animal_id_edicao = null;

// ============================================
// PROCESSAR AÇÕES (CREATE, UPDATE, DELETE)
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // CREATE
    if ($_POST['action'] === 'create') {
        $nome = filtrar_entrada($conexao, $_POST['nome']);
        $tipo = filtrar_entrada($conexao, $_POST['tipo']);
        $idade = filtrar_entrada($conexao, $_POST['idade']);
        $descricao = filtrar_entrada($conexao, $_POST['descricao']);
        $status = 'Disponível';

        // Processamento de imagem consolidado
        $stored_image_url = '';
        if (isset($_FILES['imagem_file'])) {
            $resultado_img = processar_imagem(
                $_FILES['imagem_file'],
                dirname(__DIR__) . '/uploads/animals/',
                2,      // 2MB máximo
                1024    // 1024x1024 máximo
            );
            
            if ($resultado_img['sucesso']) {
                $stored_image_url = $base_url . '/uploads/animals/' . basename($resultado_img['caminho']);
            } else {
                $mensagem = "❌ " . $resultado_img['erro'];
            }
        } else {
            $mensagem = "❌ Imagem é obrigatória.";
        }

        if (empty($mensagem)) {
            if (empty($nome) || empty($tipo) || empty($idade) || empty($descricao) || empty($stored_image_url)) {
                $mensagem = "❌ Todos os campos são obrigatórios!";
            } else {
                $imagem_db = escape($conexao, $stored_image_url);
                $sql = "INSERT INTO animais (nome, tipo, idade, descricao, imagem_url, status, criado_em) 
                        VALUES ('$nome', '$tipo', '$idade', '$descricao', '$imagem_db', '$status', NOW())";

                if (mysqli_query($conexao, $sql)) {
                    $mensagem = "✅ Animal cadastrado com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao cadastrar: " . mysqli_error($conexao);
                }
            }
        }
    }
    
    // UPDATE
    elseif ($_POST['action'] === 'update') {
        $animal_id = (int)$_POST['animal_id'];
        $nome = filtrar_entrada($conexao, $_POST['nome']);
        $tipo = filtrar_entrada($conexao, $_POST['tipo']);
        $idade = filtrar_entrada($conexao, $_POST['idade']);
        $descricao = filtrar_entrada($conexao, $_POST['descricao']);

        // Obter URL atual da imagem
        $current_img = '';
        $res_img = mysqli_query($conexao, "SELECT imagem_url FROM animais WHERE id = $animal_id");
        if ($res_img && mysqli_num_rows($res_img) > 0) {
            $rowi = mysqli_fetch_assoc($res_img);
            $current_img = $rowi['imagem_url'];
        }

        // Processar nova imagem se enviada
        $new_image_url = $current_img;
        if (isset($_FILES['imagem_file']) && $_FILES['imagem_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado_img = processar_imagem(
                $_FILES['imagem_file'],
                dirname(__DIR__) . '/uploads/animals/',
                2,      // 2MB máximo
                1024    // 1024x1024 máximo
            );
            
            if ($resultado_img['sucesso']) {
                $new_image_url = $base_url . '/uploads/animals/' . basename($resultado_img['caminho']);
            } else {
                $mensagem = "❌ " . $resultado_img['erro'];
            }
        }

        if (empty($mensagem)) {
            if (empty($nome) || empty($tipo) || empty($idade) || empty($descricao)) {
                $mensagem = "❌ Todos os campos são obrigatórios!";
            } else {
                $imagem_db = escape($conexao, $new_image_url);
                $sql = "UPDATE animais 
                        SET nome = '$nome', tipo = '$tipo', idade = '$idade', descricao = '$descricao', imagem_url = '$imagem_db'
                        WHERE id = $animal_id";

                if (mysqli_query($conexao, $sql)) {
                    // Se nova imagem foi enviada, deletar arquivo anterior
                    if ($new_image_url !== $current_img && !empty($current_img)) {
                        deletar_imagem($current_img, $base_url, dirname(__DIR__) . '/uploads/animals/');
                    }

                    $mensagem = "✅ Animal atualizado com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao atualizar: " . mysqli_error($conexao);
                }
            }
        }
    }
    
    // DELETE
    elseif ($_POST['action'] === 'delete') {
        $animal_id = (int)$_POST['animal_id'];
        
        // Verificar se há solicitações pendentes
        $sql_check = "SELECT COUNT(*) as count FROM solicitacoes_adocao WHERE animal_id = $animal_id AND status = 'Pendente'";
        $result_check = mysqli_query($conexao, $sql_check);
        $row_check = mysqli_fetch_assoc($result_check);
        
        if ($row_check['count'] > 0) {
            $mensagem = "❌ Não é possível excluir este animal. Existem solicitações pendentes.";
        } else {
            // Obter URL da imagem para deletar arquivo
            $img_res = mysqli_query($conexao, "SELECT imagem_url FROM animais WHERE id = $animal_id");
            $img_row = $img_res && mysqli_num_rows($img_res) ? mysqli_fetch_assoc($img_res) : null;
            $sql = "DELETE FROM animais WHERE id = $animal_id";

            if (mysqli_query($conexao, $sql)) {
                // Deletar arquivo de imagem se existir
                if ($img_row && !empty($img_row['imagem_url'])) {
                    deletar_imagem($img_row['imagem_url'], $base_url, dirname(__DIR__) . '/uploads/animals/');
                }

                $mensagem = "✅ Animal excluído com sucesso!";
            } else {
                $mensagem = "❌ Erro ao excluir: " . mysqli_error($conexao);
            }
        }
    }
    
    // APPROVE (solicitações)
    elseif ($_POST['action'] === 'approve') {
        $solicitation_id = (int)$_POST['solicitation_id'];
        
        // Obter dados da solicitação
        $sql_get = "SELECT animal_id FROM solicitacoes_adocao WHERE id = $solicitation_id";
        $result_get = mysqli_query($conexao, $sql_get);
        
        if ($result_get && mysqli_num_rows($result_get) > 0) {
            $row = mysqli_fetch_assoc($result_get);
            $animal_id = $row['animal_id'];
            
            mysqli_begin_transaction($conexao);
            try {
                // Obter dados completos da solicitação
                $sql_details = "SELECT user_id FROM solicitacoes_adocao WHERE id = $solicitation_id";
                $result_details = mysqli_query($conexao, $sql_details);
                $sol_details = mysqli_fetch_assoc($result_details);
                $user_id = $sol_details['user_id'];
                
                // Atualizar solicitação para 'Aprovada'
                $data_aprovacao = date('Y-m-d H:i:s');
                $sql_approve = "UPDATE solicitacoes_adocao SET status = 'Aprovada', data_aprovacao = '$data_aprovacao' WHERE id = $solicitation_id";
                mysqli_query($conexao, $sql_approve);
                
                // Atualizar animal para 'Em Processo'
                $sql_animal = "UPDATE animais SET status = 'Em Processo' WHERE id = $animal_id";
                mysqli_query($conexao, $sql_animal);
                
                // Criar ordem de serviço automaticamente
                $sql_os = "INSERT INTO ordens_servico (solicitacao_adocao_id, animal_id, user_solicitante_id, status) 
                           VALUES ($solicitation_id, $animal_id, $user_id, 'Pendente')";
                mysqli_query($conexao, $sql_os);
                $ordem_id = mysqli_insert_id($conexao);
                
                // Obter nome do animal para notificação
                $sql_animal_name = "SELECT nome FROM animais WHERE id = $animal_id";
                $result_animal_name = mysqli_query($conexao, $sql_animal_name);
                $animal_row = mysqli_fetch_assoc($result_animal_name);
                $animal_nome = $animal_row['nome'];
                
                // Criar notificação para o usuário
                $msg_notif = "Sua solicitação de adoção do '{$animal_nome}' foi aprovada! Aguarde o agendamento da retirada.";
                $sql_notif = "INSERT INTO notificacoes (user_id, solicitacao_adocao_id, ordem_servico_id, tipo, mensagem) 
                              VALUES ($user_id, $solicitation_id, $ordem_id, 'aprovacao', '$msg_notif')";
                mysqli_query($conexao, $sql_notif);
                
                mysqli_commit($conexao);
                $mensagem = "✅ Solicitação aprovada com sucesso! Ordem de serviço gerada.";
            } catch (Exception $e) {
                mysqli_rollback($conexao);
                $mensagem = "❌ Erro ao aprovar solicitação.";
            }
        }
    }
    
    // REJECT (solicitações)
    elseif ($_POST['action'] === 'reject') {
        $solicitation_id = (int)$_POST['solicitation_id'];
        
        // Obter dados da solicitação
        $sql_get = "SELECT animal_id, user_id FROM solicitacoes_adocao WHERE id = $solicitation_id";
        $result_get = mysqli_query($conexao, $sql_get);
        
        if ($result_get && mysqli_num_rows($result_get) > 0) {
            $row = mysqli_fetch_assoc($result_get);
            $animal_id = $row['animal_id'];
            $user_id = $row['user_id'];
            
            mysqli_begin_transaction($conexao);
            try {
                // Atualizar solicitação para 'Rejeitada'
                $sql_reject = "UPDATE solicitacoes_adocao SET status = 'Rejeitada' WHERE id = $solicitation_id";
                mysqli_query($conexao, $sql_reject);
                
                // Retornar animal para 'Disponível'
                $sql_animal = "UPDATE animais SET status = 'Disponível' WHERE id = $animal_id";
                mysqli_query($conexao, $sql_animal);
                
                // Obter nome do animal para notificação
                $sql_animal_name = "SELECT nome FROM animais WHERE id = $animal_id";
                $result_animal_name = mysqli_query($conexao, $sql_animal_name);
                $animal_row = mysqli_fetch_assoc($result_animal_name);
                $animal_nome = $animal_row['nome'];
                
                // Criar notificação para o usuário
                $msg_notif = "Sua solicitação de adoção do '{$animal_nome}' foi rejeitada. Tente novamente com outro animal.";
                $sql_notif = "INSERT INTO notificacoes (user_id, solicitacao_adocao_id, tipo, mensagem) 
                              VALUES ($user_id, $solicitation_id, 'rejeicao', '$msg_notif')";
                mysqli_query($conexao, $sql_notif);
                
                mysqli_commit($conexao);
                $mensagem = "✅ Solicitação rejeitada com sucesso!";
            } catch (Exception $e) {
                mysqli_rollback($conexao);
                $mensagem = "❌ Erro ao rejeitar solicitação.";
            }
        }
    }
}

// ============================================
// VERIFICAR SE ESTÁ EM MODO EDIÇÃO
// ============================================

if (isset($_GET['edit'])) {
    $animal_id_edicao = (int)$_GET['edit'];
    $sql_edit = "SELECT * FROM animais WHERE id = $animal_id_edicao";
    $result_edit = mysqli_query($conexao, $sql_edit);
    
    if (mysqli_num_rows($result_edit) > 0) {
        $modo_edicao = true;
        $animal_edicao = mysqli_fetch_assoc($result_edit);
    }
}

// Listar animais
$sql = "SELECT * FROM animais ORDER BY criado_em DESC";
$result = mysqli_query($conexao, $sql);

// Listar solicitações
$sql_solicitacoes = "SELECT sa.id, sa.user_id, sa.animal_id, sa.telefone, sa.endereco, sa.mensagem, sa.status, sa.criado_em, u.nome as user_nome, a.nome as animal_nome 
                     FROM solicitacoes_adocao sa 
                     JOIN usuarios u ON sa.user_id = u.id 
                     JOIN animais a ON sa.animal_id = a.id 
                     ORDER BY sa.criado_em DESC";
$result_solicitacoes = mysqli_query($conexao, $sql_solicitacoes);
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Dashboard do Administrador</h1>
        
        <div style="max-width: 1100px; margin: 0 auto;">
            
            <!-- Abas -->
            <div class="form-tabs" style="margin-bottom: 30px;">
                <a href="?tab=animais" class="form-tab-btn <?php echo $tab_ativa === 'animais' ? 'active' : ''; ?>" style="text-decoration: none; cursor: pointer;">🐾 Gerenciar Animais</a>
                <a href="?tab=solicitacoes" class="form-tab-btn <?php echo $tab_ativa === 'solicitacoes' ? 'active' : ''; ?>" style="text-decoration: none; cursor: pointer;">📋 Solicitações de Adoção</a>
            </div>
            
            <?php if ($mensagem): ?>
                <div class="msg msg-success" style="margin-bottom: 20px; padding: 12px; border-radius: 5px; background-color: #d4edda; color: #155724;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            
            <!-- ABA: GERENCIAR ANIMAIS -->
            <?php if ($tab_ativa === 'animais'): ?>
                
                <!-- FORMULÁRIO DE CADASTRO / EDIÇÃO -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-content">
                        <h2 style="margin-top: 0;">
                            <?php echo $modo_edicao ? '✏️ Editar Animal' : '➕ Cadastrar Novo Animal'; ?>
                        </h2>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $modo_edicao ? 'update' : 'create'; ?>">
                            <?php if ($modo_edicao): ?>
                                <input type="hidden" name="animal_id" value="<?php echo $animal_edicao['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="nome">Nome do Animal:</label>
                                <input type="text" id="nome" name="nome" required value="<?php echo $modo_edicao ? htmlspecialchars($animal_edicao['nome']) : ''; ?>">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="tipo">Tipo/Espécie:</label>
                                    <input type="text" id="tipo" name="tipo" placeholder="Ex: Cão, Gato" required value="<?php echo $modo_edicao ? htmlspecialchars($animal_edicao['tipo']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="idade">Idade:</label>
                                    <input type="text" id="idade" name="idade" placeholder="Ex: 2 anos" required value="<?php echo $modo_edicao ? htmlspecialchars($animal_edicao['idade']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descricao">Descrição:</label>
                                <textarea id="descricao" name="descricao" rows="4" required><?php echo $modo_edicao ? htmlspecialchars($animal_edicao['descricao']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="imagem_file">Imagem do Animal (JPG/PNG, máx 2MB):</label>
                                <?php if ($modo_edicao && !empty($animal_edicao['imagem_url'])): ?>
                                    <div style="margin-bottom:8px;"><img src="<?php echo htmlspecialchars($animal_edicao['imagem_url']); ?>" alt="Imagem atual" style="max-width:140px; max-height:120px; border-radius:6px; object-fit:cover; border:1px solid #ddd;"></div>
                                <?php endif; ?>
                                <input type="file" id="imagem_file" name="imagem_file" accept="image/jpeg,image/png" <?php echo $modo_edicao ? '' : 'required'; ?>>
                                <small style="display:block; color:#666; margin-top:4px;">Recomendado: 800x600px. Máx: 1024x1024px, 2MB. Formatos: JPG/PNG.</small>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    <?php echo $modo_edicao ? '💾 Atualizar Animal' : '➕ Cadastrar Animal'; ?>
                                </button>
                                <?php if ($modo_edicao): ?>
                                    <a href="?tab=animais" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none; padding: 10px;">❌ Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- LISTA DE ANIMAIS -->
                <h2 style="margin-bottom: 20px;">Animais Cadastrados (Total: <?php echo mysqli_num_rows($result); ?>)</h2>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">Nome</th>
                                    <th style="padding: 12px; text-align: left;">Tipo</th>
                                    <th style="padding: 12px; text-align: left;">Idade</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: left;">Criado em</th>
                                    <th style="padding: 12px; text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($animal = mysqli_fetch_assoc($result)): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px;"><strong><?php echo htmlspecialchars($animal['nome']); ?></strong></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($animal['tipo']); ?></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($animal['idade']); ?></td>
                                        <td style="padding: 12px;">
                                            <span style="padding: 5px 10px; border-radius: 5px; background-color: <?php 
                                                if ($animal['status'] === 'Disponível') echo '#d4edda'; 
                                                elseif ($animal['status'] === 'Em Processo') echo '#fff3cd';
                                                else echo '#f8d7da'; 
                                            ?>; color: <?php 
                                                if ($animal['status'] === 'Disponível') echo '#155724'; 
                                                elseif ($animal['status'] === 'Em Processo') echo '#856404';
                                                else echo '#721c24'; 
                                            ?>;">
                                                <?php echo htmlspecialchars($animal['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px; font-size: 12px;">
                                            <?php echo date('d/m/Y H:i', strtotime($animal['criado_em'])); ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <a href="?tab=animais&edit=<?php echo $animal['id']; ?>" class="btn btn-primary" style="padding: 6px 10px; font-size: 12px; text-decoration: none; display: inline-block; background-color: #007bff; color: white; border-radius: 4px; cursor: pointer;">✏️ Editar</a>
                                            
                                            <form action="" method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="animal_id" value="<?php echo $animal['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;" onclick="return confirm('Tem certeza que deseja excluir este animal?');">🗑️ Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-content">
                            <p>Nenhum animal cadastrado ainda.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
            <!-- ABA: SOLICITAÇÕES -->
            <?php elseif ($tab_ativa === 'solicitacoes'): ?>
                
                <h2 style="margin-bottom: 20px;">Solicitações de Adoção</h2>
                
                <?php if (mysqli_num_rows($result_solicitacoes) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">Animal</th>
                                    <th style="padding: 12px; text-align: left;">Solicitante</th>
                                    <th style="padding: 12px; text-align: left;">Telefone</th>
                                    <th style="padding: 12px; text-align: left;">Endereço</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: left;">Mensagem</th>
                                    <th style="padding: 12px; text-align: left;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sol = mysqli_fetch_assoc($result_solicitacoes)): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px;"><strong><?php echo htmlspecialchars($sol['animal_nome']); ?></strong></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($sol['user_nome']); ?></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($sol['telefone']); ?></td>
                                        <td style="padding: 12px; font-size: 12px;"><?php echo htmlspecialchars($sol['endereco']); ?></td>
                                        <td style="padding: 12px;">
                                            <span style="padding: 5px 10px; border-radius: 5px; background-color: <?php 
                                                if ($sol['status'] === 'Pendente') echo '#fff3cd'; 
                                                elseif ($sol['status'] === 'Aprovada') echo '#d4edda'; 
                                                else echo '#f8d7da'; 
                                            ?>; color: <?php 
                                                if ($sol['status'] === 'Pendente') echo '#856404'; 
                                                elseif ($sol['status'] === 'Aprovada') echo '#155724'; 
                                                else echo '#721c24'; 
                                            ?>;">
                                                <?php echo htmlspecialchars($sol['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px; font-size: 12px;">
                                            <?php echo htmlspecialchars(substr($sol['mensagem'], 0, 50)) . (strlen($sol['mensagem']) > 50 ? '...' : ''); ?>
                                        </td>
                                        <td style="padding: 12px;">
                                            <?php if ($sol['status'] === 'Pendente'): ?>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="solicitation_id" value="<?php echo $sol['id']; ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 6px 10px; font-size: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">✓ Aprovar</button>
                                                </form>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="solicitation_id" value="<?php echo $sol['id']; ?>">
                                                    <button type="submit" class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">✗ Rejeitar</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size: 12px; color: #666;">Finalizada</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-content">
                            <p>Nenhuma solicitação de adoção registrada.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
        </div>
    </div>
</section>

<?php
mysqli_close($conexao);
// Inclui o rodapé
require_once '../includes/footer.php';
?>

