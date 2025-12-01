## AdoteUm - Sistema de Adoção de Animais 🐾

# 📖 Sobre o Projeto

O AdoteUm é uma plataforma web desenvolvida para facilitar o processo de adoção de animais. O sistema conecta pessoas interessadas em adotar (adotantes) com a administração do abrigo, permitindo visualizar animais disponíveis, solicitar adoção e gerenciar todo o fluxo até a retirada do animal.

Este projeto foi desenvolvido como parte da disciplina de Análise e Desenvolvimento de Sistemas do IFTM Campus Patrocínio.

✨ Funcionalidades Principais

👤 Área Pública e do Usuário

Galeria de Animais: Visualização de todos os animais disponíveis com filtros visuais (cards).

Cadastro e Login: Sistema seguro de autenticação para adotantes.

Solicitação de Adoção: Formulário para manifestar interesse em um animal específico.

Meu Perfil: Acompanhamento do status das solicitações (Pendente, Aprovada, Rejeitada).

🛡️ Área Administrativa (Dashboard)

Gestão de Animais: Cadastro, Edição e Exclusão de animais (CRUD completo com upload de fotos).

Gestão de Solicitações: Visualização de pedidos de adoção pendentes.

Fluxo de Aprovação: Aprovar ou Rejeitar solicitações.

Agendamento: Agendar data e hora para retirada do animal após aprovação (Setor de Operações).

🛠️ Tecnologias Utilizadas

Frontend: HTML5, CSS3 (Design Responsivo, Flexbox/Grid).

Backend: PHP 7/8 (Estruturado).

Banco de Dados: MySQL (Relacional).

Servidor Local: VertrigoServ (Apache + MySQL).

Testes Automatizados: Python + Selenium WebDriver.

Ferramentas: VS Code, Git.

🚀 Como Executar o Projeto

Pré-requisitos

Ter um servidor PHP/MySQL instalado (ex: Vertrigo, XAMPP, Laragon).

Ter o Python instalado (para rodar os testes).

Passo a Passo

Clone o repositório para a pasta pública do seu servidor (ex: www ou htdocs):

git clone [https://github.com/seu-usuario/adoteum.git](https://github.com/seu-usuario/adoteum.git)


Banco de Dados:

Acesse o phpMyAdmin (http://localhost/phpmyadmin).

Crie um banco de dados chamado adoteum_db (ou o nome configurado em config/db.php).

Importe o arquivo database/schema.sql (se disponível) ou execute os comandos SQL de criação das tabelas.

Configuração:

Verifique o arquivo config/db.php e ajuste as credenciais do banco (usuário, senha) se necessário.

Acesse:

Abra o navegador e vá para: http://localhost/adoteum/public/index.php

🤖 Testes Automatizados

O projeto conta com uma suíte de testes de ponta a ponta (E2E) usando Selenium.

Estrutura dos Testes (/tests)

01_home.py: Valida carregamento da página inicial.

02_menu.py: Verifica links de navegação.

03_cadastro_fluxo.py: Testa cadastro de usuário, login e acesso ao perfil.

04_login.py: Valida login com credenciais existentes.

05_adocao.py: Simula um usuário solicitando a adoção de um animal.

06_cadastro_animal.py: Simula o admin cadastrando um animal com foto.

07_admin_validacao_adocao.py: Admin aprova uma solicitação.

08_agendar_retirada.py: Setor de Operações agenda a retirada do animal.

Como Rodar

Instale as dependências: pip install selenium webdriver-manager

Entre na pasta de testes: cd tests

Execute um teste: python 08_agendar_retirada.py

🎨 Design e UX

O layout foi projetado com base em princípios de Fatores Humanos:

Cor Azul: Transmite confiança e segurança.

Gestalt (Proximidade): Informações de animais agrupadas em cards.

Feedback: Mensagens de sucesso/erro claras para o usuário.

Desenvolvido por [Seu Nome]
IFTM Campus Patrocínio - 2025
