<?php
session_start();

// Inclui a conexão com o banco (que define $base_url)
require_once '../config/db.php';

// Limpar sessão
session_unset();
session_destroy();

header('Location: ' . $base_url . '/public/login.php');
exit;
?>
