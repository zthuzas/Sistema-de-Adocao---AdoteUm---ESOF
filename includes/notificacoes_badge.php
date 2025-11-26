<?php
// ===============================================
// COMPONENTE: Badge de Notificações para Header
// ===============================================
// Inclua isto no seu header.php dentro da navegação

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'operacoes') {
    // Obter contagem de notificações não lidas
    $sql_notif = "SELECT COUNT(*) as total FROM notificacoes WHERE user_id = " . (int)$_SESSION['user_id'] . " AND lida = FALSE";
    $result_notif = mysqli_query($conexao, $sql_notif);
    $row_notif = mysqli_fetch_assoc($result_notif);
    $notificacoes_nao_lidas = (int)$row_notif['total'];
    
    if ($notificacoes_nao_lidas > 0):
        ?>
        <li style="position: relative;">
            <a href="<?php echo $base_url; ?>/public/notificacoes.php" class="nav-link" style="display: flex; align-items: center; gap: 8px;">
                🔔 Notificações
                <span style="
                    background: #ff4444;
                    color: white;
                    font-size: 11px;
                    padding: 2px 6px;
                    border-radius: 10px;
                    font-weight: bold;
                    min-width: 18px;
                    text-align: center;
                ">
                    <?php echo $notificacoes_nao_lidas; ?>
                </span>
            </a>
        </li>
        <?php
    else:
        ?>
        <li>
            <a href="<?php echo $base_url; ?>/public/notificacoes.php" class="nav-link">
                🔔 Notificações
            </a>
        </li>
        <?php
    endif;
}
?>
