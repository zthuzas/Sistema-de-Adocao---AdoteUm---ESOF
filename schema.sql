-- Schema SQL for AdoteUm - CURRENT PRODUCTION SCHEMA
-- Run: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS `adoteum0911` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `adoteum0911`;

-- =====================================================
-- 1. USUARIOS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `tipo_usuario` ENUM('solicitante','admin','operacoes') NOT NULL DEFAULT 'solicitante',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_tipo` (`tipo_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. ANIMAIS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `animais` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` VARCHAR(50) DEFAULT NULL,
  `idade` VARCHAR(50) DEFAULT NULL,
  `descricao` TEXT,
  `imagem_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Disponível','Em Processo','Adotado') NOT NULL DEFAULT 'Disponível',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. SOLICITACOES_ADOCAO TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `solicitacoes_adocao` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `animal_id` INT NOT NULL,
  `telefone` VARCHAR(30) DEFAULT NULL,
  `endereco` VARCHAR(255) DEFAULT NULL,
  `mensagem` TEXT,
  `status` ENUM('Pendente','Aprovada','Rejeitada','Finalizada') NOT NULL DEFAULT 'Pendente',
  `data_aprovacao` DATETIME DEFAULT NULL,
  `data_finalizacao` DATETIME DEFAULT NULL,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_solicitante` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_animal` FOREIGN KEY (`animal_id`) REFERENCES `animais`(`id`) ON DELETE CASCADE,
  INDEX `idx_solicitacoes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. ORDENS_SERVICO TABLE (Service Orders)
-- =====================================================
CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `solicitacao_adocao_id` INT NOT NULL,
  `animal_id` INT NOT NULL,
  `user_solicitante_id` INT NOT NULL,
  `status` ENUM('Pendente','Agendada','Retirada Confirmada','Cancelada') NOT NULL DEFAULT 'Pendente',
  `data_agendada` DATETIME DEFAULT NULL,
  `data_retirada` DATETIME DEFAULT NULL,
  `data_retirada_manual` DATETIME DEFAULT NULL,
  `responsavel_assinatura` VARCHAR(100) DEFAULT NULL,
  `cpf_responsavel` VARCHAR(14) DEFAULT NULL,
  `notas` TEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_os_solicitacao` FOREIGN KEY (`solicitacao_adocao_id`) REFERENCES `solicitacoes_adocao`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_animal` FOREIGN KEY (`animal_id`) REFERENCES `animais`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_solicitante` FOREIGN KEY (`user_solicitante_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_ordens_status` (`status`),
  INDEX `idx_ordens_data_agendada` (`data_agendada`),
  INDEX `idx_cpf_responsavel` (`cpf_responsavel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. NOTIFICACOES TABLE (User Notifications)
-- =====================================================
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `solicitacao_adocao_id` INT,
  `ordem_servico_id` INT,
  `tipo` ENUM('aprovacao','agendamento','retirada_confirmada','rejeicao') NOT NULL,
  `mensagem` TEXT NOT NULL,
  `lida` BOOLEAN DEFAULT FALSE,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_solicita` FOREIGN KEY (`solicitacao_adocao_id`) REFERENCES `solicitacoes_adocao`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_os` FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE,
  INDEX `idx_notificacoes_user` (`user_id`, `lida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED DATA (OPTIONAL)
-- =====================================================
-- Example animals
INSERT INTO `animais` (`nome`, `tipo`, `idade`, `descricao`, `imagem_url`) VALUES
('Bella', 'Cão', '2 anos', 'Cachorra dócil, vacinada e castrada.', '/uploads/animals/bella.jpg'),
('Mia', 'Gato', '1 ano', 'Gatinha carinhosa e brincalhona.', '/uploads/animals/mia.jpg');

-- =====================================================
-- SCHEMA SUMMARY
-- =====================================================
-- Tables:
--   1. usuarios: User accounts (solicitante, admin, operacoes)
--   2. animais: Animal records with status tracking
--   3. solicitacoes_adocao: Adoption requests with approval/finalization dates
--   4. ordens_servico: Service orders for withdrawal scheduling and confirmation
--      - NEW FIELDS: data_retirada_manual, cpf_responsavel
--   5. notificacoes: User notifications for all workflow events
--
-- Note: Create admin and operations users via PHP helper to ensure proper bcrypt hashing.
-- Example: password_hash('senha123', PASSWORD_BCRYPT)

