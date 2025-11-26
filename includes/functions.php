<?php
/**
 * ARQUIVO DE FUNÇÕES UTILITÁRIAS
 * ================================
 * Consolidação de funções reutilizáveis para evitar redundâncias
 * Inclua este arquivo em config/db.php ou no início de cada página
 */

// =====================================================
// 1. FUNÇÕES DE SEGURANÇA - Escape e Validação
// =====================================================

/**
 * Escapa uma string para usar em consultas SQL
 * NOTA: Função auxiliar para mysqli_real_escape_string
 * 
 * Parâmetros:
 *   $conexao = Conexão MySQL ativa (global $conexao)
 *   $valor = Valor a ser escapado (string)
 *
 * Retorna: String escapada e segura para SQL
 * 
 * Exemplo:
 *   $nome = escape($conexao, $_POST['nome']);
 */
function escape(&$conexao, $valor) {
    return mysqli_real_escape_string($conexao, $valor);
}

/**
 * Valida e filtra entrada (POST/GET)
 * Remove espaços extras antes e depois (trim)
 * 
 * Parâmetros:
 *   $conexao = Conexão MySQL ativa
 *   $valor = Valor a processar
 *   $trim = Se deve remover espaços (true por padrão)
 *
 * Retorna: String processada e escapada
 * 
 * Exemplo:
 *   $email = filtrar_entrada($conexao, $_POST['email']);
 */
function filtrar_entrada(&$conexao, $valor, $trim = true) {
    if ($trim) {
        $valor = trim($valor);
    }
    return escape($conexao, $valor);
}

/**
 * Valida CPF (apenas dígitos, deve ter 11)
 * Remove caracteres especiais automaticamente
 * 
 * Parâmetros:
 *   $cpf = CPF com ou sem formatação (ex: "123.456.789-00")
 *
 * Retorna:
 *   array('valido' => true/false, 'cpf_limpo' => '12345678900')
 * 
 * Exemplo:
 *   $result = validar_cpf($_POST['cpf']);
 *   if (!$result['valido']) echo "CPF inválido!";
 */
function validar_cpf($cpf) {
    // Remove caracteres especiais
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem exatamente 11 dígitos
    $valido = (strlen($cpf_limpo) === 11);
    
    return array('valido' => $valido, 'cpf_limpo' => $cpf_limpo);
}

// =====================================================
// 2. FUNÇÕES DE DATA E HORA
// =====================================================

/**
 * Formata data/hora para exibição em português
 * Converte formato banco (YYYY-MM-DD HH:mm:ss) para (DD/MM/YYYY HH:mm)
 * 
 * Parâmetros:
 *   $data_str = String de data (ex: "2025-01-15 14:30:00")
 *   $formato = Formato desejado (default: 'd/m/Y H:i')
 *
 * Retorna: String formatada
 * 
 * Exemplo:
 *   echo formatar_data($row['criado_em']); // Saída: 15/01/2025 14:30
 */
function formatar_data($data_str, $formato = 'd/m/Y H:i') {
    if (empty($data_str) || $data_str === '0000-00-00 00:00:00') {
        return 'Não disponível';
    }
    
    try {
        $timestamp = strtotime($data_str);
        return ($timestamp !== false) ? date($formato, $timestamp) : $data_str;
    } catch (Exception $e) {
        return $data_str;
    }
}

/**
 * Formata datetime-local (HTML5) para formato MySQL
 * Converte "2025-01-15T14:30" para "2025-01-15 14:30:00"
 * 
 * Parâmetros:
 *   $datetime_local = String no formato HTML5 datetime-local
 *
 * Retorna: String formatada para MySQL
 * 
 * Exemplo:
 *   $data = datetime_local_para_mysql($_POST['data_agendada']);
 */
function datetime_local_para_mysql($datetime_local) {
    try {
        // Tenta parsear como datetime-local (YYYY-MM-DDTHH:mm)
        $obj = DateTime::createFromFormat('Y-m-d\TH:i', $datetime_local);
        
        // Se falhar, tenta formato alternativo
        if ($obj === false) {
            $obj = DateTime::createFromFormat('Y-m-d H:i', $datetime_local);
        }
        
        // Se ainda falhar, retorna null
        if ($obj === false) {
            return null;
        }
        
        return $obj->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Formata data/hora para exibição visual (com "às" entre data e hora)
 * 
 * Parâmetros:
 *   $data_str = String de data (ex: "2025-01-15 14:30:00")
 *
 * Retorna: String formatada (ex: "15/01/2025 às 14:30")
 * 
 * Exemplo:
 *   echo formatar_data_hora_visual($row['data_agendada']);
 */
function formatar_data_hora_visual($data_str) {
    if (empty($data_str)) {
        return 'Não agendado';
    }
    
    try {
        $obj = DateTime::createFromFormat('Y-m-d H:i:s', $data_str);
        if ($obj === false) {
            return $data_str;
        }
        return $obj->format('d/m/Y \à\s H:i');
    } catch (Exception $e) {
        return $data_str;
    }
}

// =====================================================
// 3. FUNÇÕES DE IMAGEM - Processamento e Upload
// =====================================================

/**
 * Redimensiona e salva imagem (JPG ou PNG)
 * Valida formato, redimensiona se necessário, salva em disco
 * 
 * Parâmetros:
 *   $arquivo_upload = $_FILES['campo'] com informações de upload
 *   $diretorio_destino = Caminho de destino (ex: '../uploads/animals/')
 *   $tamanho_maximo_mb = Tamanho máximo em MB (default: 2)
 *   $dimensao_maxima = Máximo width/height em pixels (default: 1024)
 *
 * Retorna:
 *   array('sucesso' => true/false, 'caminho' => '...', 'erro' => 'mensagem erro')
 * 
 * Exemplo:
 *   $resultado = processar_imagem($_FILES['imagem'], '../uploads/');
 *   if ($resultado['sucesso']) {
 *       $url = $base_url . '/uploads/animals/' . basename($resultado['caminho']);
 *   }
 */
function processar_imagem($arquivo_upload, $diretorio_destino, $tamanho_maximo_mb = 2, $dimensao_maxima = 1024) {
    $resposta = array('sucesso' => false, 'caminho' => '', 'erro' => '');
    
    // Validação: Arquivo foi enviado?
    if (!isset($arquivo_upload) || $arquivo_upload['error'] !== UPLOAD_ERR_OK) {
        $resposta['erro'] = 'Erro no upload do arquivo.';
        return $resposta;
    }
    
    // Validação: Tamanho máximo
    $tamanho_maximo_bytes = $tamanho_maximo_mb * 1024 * 1024;
    if ($arquivo_upload['size'] > $tamanho_maximo_bytes) {
        $resposta['erro'] = "Imagem muito grande. Máximo {$tamanho_maximo_mb}MB.";
        return $resposta;
    }
    
    // Validação: Tipo de arquivo (JPG/PNG)
    $info_imagem = getimagesize($arquivo_upload['tmp_name']);
    if ($info_imagem === false) {
        $resposta['erro'] = 'Arquivo não é uma imagem válida.';
        return $resposta;
    }
    
    $mime_type = $info_imagem['mime'];
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
        $resposta['erro'] = 'Formato inválido. Use JPG ou PNG.';
        return $resposta;
    }
    
    // Preparar diretório
    if (!is_dir($diretorio_destino)) {
        mkdir($diretorio_destino, 0755, true);
    }
    
    // Gerar nome único: timestamp + random hex
    $extensao = ($mime_type === 'image/png') ? '.png' : '.jpg';
    $nome_arquivo = time() . '_' . bin2hex(random_bytes(6)) . $extensao;
    $caminho_destino = $diretorio_destino . $nome_arquivo;
    
    // Redimensionar se necessário
    list($largura_orig, $altura_orig) = $info_imagem;
    $nova_largura = $largura_orig;
    $nova_altura = $altura_orig;
    
    if ($largura_orig > $dimensao_maxima || $altura_orig > $dimensao_maxima) {
        $ratio = min($dimensao_maxima / $largura_orig, $dimensao_maxima / $altura_orig);
        $nova_largura = (int)($largura_orig * $ratio);
        $nova_altura = (int)($altura_orig * $ratio);
    }
    
    // Processar imagem (redimensionar e salvar)
    if ($mime_type === 'image/jpeg') {
        $src = imagecreatefromjpeg($arquivo_upload['tmp_name']);
        $dst = imagecreatetruecolor($nova_largura, $nova_altura);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_orig, $altura_orig);
        imagejpeg($dst, $caminho_destino, 85);
        imagedestroy($src);
        imagedestroy($dst);
    } else { // PNG
        $src = imagecreatefrompng($arquivo_upload['tmp_name']);
        $dst = imagecreatetruecolor($nova_largura, $nova_altura);
        imagesavealpha($dst, true);
        $cor_transparente = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $cor_transparente);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_orig, $altura_orig);
        imagepng($dst, $caminho_destino, 6);
        imagedestroy($src);
        imagedestroy($dst);
    }
    
    $resposta['sucesso'] = true;
    $resposta['caminho'] = $caminho_destino;
    return $resposta;
}

/**
 * Deleta imagem do disco (arquivo local)
 * Valida se arquivo pertence ao diretório esperado para segurança
 * 
 * Parâmetros:
 *   $url_imagem = URL completa da imagem (ex: '/SistemaAd0911/uploads/animals/...')
 *   $base_url = URL base da aplicação (ex: '/SistemaAd0911')
 *   $diretorio_base = Diretório base no disco (ex: '../uploads/')
 *
 * Retorna: true se deletado, false se erro
 * 
 * Exemplo:
 *   deletar_imagem($url, $base_url, '../uploads/animals/');
 */
function deletar_imagem($url_imagem, $base_url, $diretorio_base) {
    // Validar se URL começa com a URL esperada
    $prefixo_url = $base_url . '/uploads/';
    if (strpos($url_imagem, $prefixo_url) !== 0) {
        return false; // URL não pertence ao diretório esperado
    }
    
    // Extrair nome do arquivo
    $nome_arquivo = substr($url_imagem, strlen($prefixo_url));
    $caminho_arquivo = $diretorio_base . $nome_arquivo;
    
    // Deletar se existe
    if (file_exists($caminho_arquivo)) {
        return unlink($caminho_arquivo);
    }
    
    return false;
}

// =====================================================
// 4. FUNÇÕES DE NOTIFICAÇÃO
// =====================================================

/**
 * Cria uma notificação no banco de dados
 * 
 * Parâmetros:
 *   $conexao = Conexão MySQL ativa
 *   $user_id = ID do usuário que receberá a notificação
 *   $tipo = Tipo de notificação (aprovacao, agendamento, retirada_confirmada, rejeicao)
 *   $mensagem = Texto da mensagem
 *   $solicitacao_id = ID da solicitação (opcional)
 *   $ordem_id = ID da ordem de serviço (opcional)
 *
 * Retorna: true se criada, false se erro
 * 
 * Exemplo:
 *   criar_notificacao($conexao, 5, 'aprovacao', 'Sua solicitação foi aprovada!', 10);
 */
function criar_notificacao(&$conexao, $user_id, $tipo, $mensagem, $solicitacao_id = null, $ordem_id = null) {
    // Escapar valores
    $user_id = (int)$user_id;
    $mensagem = escape($conexao, $mensagem);
    $solicitacao_id = ($solicitacao_id !== null) ? (int)$solicitacao_id : null;
    $ordem_id = ($ordem_id !== null) ? (int)$ordem_id : null;
    
    // Construir SQL
    $cols = 'user_id, tipo, mensagem';
    $vals = "$user_id, '$tipo', '$mensagem'";
    
    if ($solicitacao_id) {
        $cols .= ', solicitacao_adocao_id';
        $vals .= ", $solicitacao_id";
    }
    if ($ordem_id) {
        $cols .= ', ordem_servico_id';
        $vals .= ", $ordem_id";
    }
    
    $sql = "INSERT INTO notificacoes ($cols) VALUES ($vals)";
    return (mysqli_query($conexao, $sql) !== false);
}

// =====================================================
// 5. FUNÇÕES DE VALIDAÇÃO - Formulários
// =====================================================

/**
 * Valida email simples
 * 
 * Parâmetros:
 *   $email = String com email a validar
 *
 * Retorna: true se válido, false se inválido
 * 
 * Exemplo:
 *   if (validar_email($_POST['email'])) { ... }
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida se duas senhas são iguais
 * 
 * Parâmetros:
 *   $senha1 = Primeira senha
 *   $senha2 = Confirmação de senha
 *
 * Retorna: true se iguais, false se diferentes
 * 
 * Exemplo:
 *   if (!validar_senhas_iguais($_POST['senha'], $_POST['confirm'])) {
 *       echo "Senhas não conferem!";
 *   }
 */
function validar_senhas_iguais($senha1, $senha2) {
    return $senha1 === $senha2;
}

/**
 * Valida força da senha (mínimo 6 caracteres, recomendado 8+)
 * 
 * Parâmetros:
 *   $senha = Senha a validar
 *   $minimo = Número mínimo de caracteres (default: 6)
 *
 * Retorna: true se válida, false se fraca
 * 
 * Exemplo:
 *   if (!validar_forca_senha($_POST['senha'], 8)) {
 *       echo "Senha deve ter pelo menos 8 caracteres!";
 *   }
 */
function validar_forca_senha($senha, $minimo = 6) {
    return strlen($senha) >= $minimo;
}

/**
 * Verifica se email já existe no banco
 * 
 * Parâmetros:
 *   $conexao = Conexão MySQL ativa
 *   $email = Email a verificar
 *   $excluir_id = ID de usuário a ignorar (para edição, opcional)
 *
 * Retorna: true se email existe, false se novo
 * 
 * Exemplo:
 *   if (email_ja_existe($conexao, 'teste@email.com')) {
 *       echo "Email já cadastrado!";
 *   }
 */
function email_ja_existe(&$conexao, $email, $excluir_id = null) {
    $email = escape($conexao, $email);
    $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = '$email'";
    
    if ($excluir_id) {
        $excluir_id = (int)$excluir_id;
        $sql .= " AND id != $excluir_id";
    }
    
    $resultado = mysqli_query($conexao, $sql);
    $linha = mysqli_fetch_assoc($resultado);
    return $linha['total'] > 0;
}

?>
