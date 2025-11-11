<?php
/*
 * Arquivo: config/db.php
 * Descrição: Define as constantes de conexão com o banco de dados
 * e estabelece a conexão com o MySQL usando mysqli.
 */

// 1. Definição das Constantes de Conexão
// Altere os valores conforme a configuração do seu VERTRIGO/MySQL

/**
 * Define o servidor do banco de dados (geralmente localhost).
 */
define('HOST', 'localhost');

/**
 * Define o usuário do banco de dados (geralmente 'root' no Vertrigo).
 */
define('USER', 'root');

/**
 * Define a senha do banco de dados (geralmente 'vertrigo' no Vertrigo).
 * Se não houver senha, deixe em branco: ''
 */
define('PASS', 'vertrigo');

/**
 * Define o nome do banco de dados que será criado para o AdoteUm.
 */
define('DB', 'adoteum0911');


// 2. Estabelecimento da Conexão
// Tenta conectar ao banco de dados MySQL usando as constantes definidas.

$conexao = mysqli_connect(HOST, USER, PASS, DB);


// 3. Verificação da Conexão
// Verifica se a conexão foi bem-sucedida.
// Se falhar, exibe uma mensagem de erro detalhada e interrompe o script.

if (!$conexao) {
    // A função die() interrompe a execução do script e exibe a mensagem.
    // mysqli_connect_error() retorna a descrição do erro da última tentativa de conexão.
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}

/*
 * Opcional: Definir o charset para UTF-8 (Recomendado para evitar problemas com acentuação)
 * Descomente a linha abaixo se necessário.
 */
// mysqli_set_charset($conexao, 'utf8');

?>