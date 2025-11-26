<?php
session_start();

// Define o título da página
$page_title = "Sobre - AdoteUm";

// Inclui o cabeçalho
require_once '../includes/header.php';
?>

<section class="page-content">
    <div class="container">
        <h1 class="section-title">Sobre o AdoteUm</h1>
        <p class="section-subtitle">Conheça nossa missão de conectar vidas</p>
        
        <div style="max-width: 800px; margin: 40px auto; line-height: 1.8;">
            <h2 style="color: #007bff; margin-top: 30px; margin-bottom: 15px;">Nossa Missão</h2>
            <p>
                O AdoteUm é uma plataforma dedicada a conectar pessoas apaixonadas por animais com pets que precisam de um lar cheio de amor. 
                Acreditamos que todo animal merece uma família que o acolha e o cuide com carinho.
            </p>
            
            <h2 style="color: #007bff; margin-top: 30px; margin-bottom: 15px;">Por que Adotar?</h2>
            <p>
                Adotar é um ato de amor que muda vidas - tanto a do animal quanto a do adotante. Ao adotar, você:
            </p>
            <ul style="margin-left: 20px;">
                <li>Salva uma vida e oferece um lar digno</li>
                <li>Reduz a população de animais em abrigos</li>
                <li>Ganha um companheiro leal e amoroso</li>
                <li>Contribui para uma sociedade mais compassiva</li>
            </ul>
            
            <h2 style="color: #007bff; margin-top: 30px; margin-bottom: 15px;">Como Funciona</h2>
            <p>
                Nosso processo é simples e transparente:
            </p>
            <ol style="margin-left: 20px;">
                <li>Navegue pelos animais disponíveis em nosso catálogo</li>
                <li>Conheça as histórias e características de cada um</li>
                <li>Envie uma solicitação de adoção</li>
                <li>Nossa equipe analisará sua solicitação</li>
                <li>Realizamos um acompanhamento pós-adoção</li>
            </ol>
            
            <h2 style="color: #007bff; margin-top: 30px; margin-bottom: 15px;">Valores</h2>
            <p>
                <strong>Amor pelos Animais:</strong> Colocamos o bem-estar de cada animal em primeiro lugar.<br>
                <strong>Transparência:</strong> Somos honestos sobre o histórico e necessidades de cada pet.<br>
                <strong>Responsabilidade:</strong> Garantimos que cada adoção resulte em um lar seguro e feliz.
            </p>
        </div>
    </div>
</section>

<?php
// Inclui o rodapé
require_once '../includes/footer.php';
?>
