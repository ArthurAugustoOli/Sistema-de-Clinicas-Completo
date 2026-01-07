-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 07/01/2026 às 19:38
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u566100020_ClinicaTemplat`
--
CREATE DATABASE IF NOT EXISTS `u566100020_ClinicaTemplat` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `u566100020_ClinicaTemplat`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atividades`
--

CREATE TABLE `atividades` (
  `id` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `data` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `atividades`
--

INSERT INTO `atividades` (`id`, `descricao`, `data`, `usuario_id`) VALUES
(11, 'Novo funcionário cadastrado: Jamile', '2025-06-27 18:13:55', NULL),
(12, 'Novo funcionário cadastrado: Giovana', '2025-07-22 16:30:51', NULL),
(13, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 16:37:21', NULL),
(14, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 16:38:38', NULL),
(15, 'Novo funcionário e usuário criados: Leticia', '2025-07-23 16:40:29', NULL),
(16, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 16:47:23', NULL),
(17, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 17:02:45', NULL),
(18, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 17:08:02', NULL),
(19, 'Novo funcionário e usuário criados: Romulo Ferreira de Oliveira', '2025-07-23 18:18:08', NULL),
(20, 'Novo funcionário e usuário criados: Teste', '2025-07-23 18:20:27', NULL),
(21, 'Funcionário #13 excluído', '2025-07-23 18:21:43', NULL),
(22, 'Funcionário #14 excluído', '2025-07-23 18:21:46', NULL),
(23, 'Novo funcionário e usuário criados: eu', '2025-07-24 18:53:16', NULL),
(24, 'Funcionário #15 excluído', '2025-07-24 18:53:32', NULL),
(25, 'Funcionário #5 excluído', '2025-07-24 18:53:36', NULL),
(26, 'Novo funcionário e usuário criados: Funcionario Teste 1', '2025-09-25 02:35:41', NULL),
(27, 'Funcionário #16 excluído', '2025-09-25 02:36:04', NULL),
(28, 'Novo funcionário e usuário criados: Dr. Robert', '2025-10-09 11:41:34', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `data_consulta` datetime NOT NULL,
  `paciente_cpf` varchar(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `procedimento` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('Agendada','Cancelada','Concluída') NOT NULL DEFAULT 'Agendada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `consultas`
--

INSERT INTO `consultas` (`id`, `data_consulta`, `paciente_cpf`, `profissional_id`, `servico_id`, `procedimento`, `observacoes`, `status`) VALUES
(111, '2025-10-09 08:00:00', '14229253664', 17, 13, 'Limpeza bocal', '', 'Agendada'),
(112, '2025-10-09 08:00:00', '14229253664', 17, 13, 'Massagem Terapeutica', '', 'Concluída');

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id_despesa` int(11) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `data_despesa` date DEFAULT NULL,
  `status` enum('paga','pendente') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas`
--

CREATE TABLE `entradas` (
  `id_entrada` int(11) NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_hora` datetime NOT NULL,
  `forma_pagamento` enum('boleto','pix','dinheiro','credito','debito') NOT NULL,
  `parcelas` int(11) DEFAULT NULL,
  `parcelas_pagas` int(11) NOT NULL DEFAULT 0,
  `valor_parcela` decimal(10,2) DEFAULT NULL,
  `montante` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolucoes`
--

CREATE TABLE `evolucoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `paciente_cpf` varchar(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `data_horario` datetime NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro`
--

CREATE TABLE `financeiro` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status_pagamento` enum('PENDENTE','PAGO') NOT NULL DEFAULT 'PENDENTE',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_pagamento` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `financeiro`
--

INSERT INTO `financeiro` (`id`, `consulta_id`, `servico_id`, `valor`, `status_pagamento`, `data_criacao`, `data_pagamento`) VALUES
(101, 111, 13, 199.90, 'PENDENTE', '2025-10-09 11:41:59', NULL),
(102, 112, 13, 199.90, 'PAGO', '2025-10-09 11:42:20', '2025-10-09 11:42:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int(11) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data_nasc` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `whatsapp` varchar(15) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `turno` varchar(20) DEFAULT NULL,
  `horario_trabalho` varchar(50) DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `cpf`, `nome`, `data_nasc`, `email`, `telefone`, `whatsapp`, `cargo`, `turno`, `horario_trabalho`, `status`) VALUES
(17, '12312312312', 'Dr. Robert', '1996-06-09', 'rob_2k@gmail.com', '31989809600', '31989809600', 'CEO', 'Integral', 'Segunda a Sexta - 08:00 - 17:00', 'Ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('info','warning','success','danger') NOT NULL DEFAULT 'info',
  `criador_id` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime DEFAULT NULL,
  `para_todos` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_usuarios`
--

CREATE TABLE `notificacoes_usuarios` (
  `notificacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `cpf` varchar(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data_nasc` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `doencas` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `tem_convenio` tinyint(1) NOT NULL DEFAULT 0,
  `convenio` varchar(100) DEFAULT NULL,
  `numero_convenio` varchar(50) DEFAULT NULL,
  `tipo_sanguineo` varchar(5) DEFAULT NULL,
  `nome_contato_emergencia` varchar(100) DEFAULT NULL,
  `numero_contato_emergencia` varchar(15) DEFAULT NULL,
  `filiacao_contato_emergencia` varchar(100) DEFAULT NULL,
  `condicoes_medicas` text DEFAULT NULL,
  `remedios_em_uso` text DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`cpf`, `nome`, `data_nasc`, `email`, `telefone`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `doencas`, `alergias`, `tem_convenio`, `convenio`, `numero_convenio`, `tipo_sanguineo`, `nome_contato_emergencia`, `numero_contato_emergencia`, `filiacao_contato_emergencia`, `condicoes_medicas`, `remedios_em_uso`, `foto_perfil`, `observacoes`) VALUES
('14229253664', 'Arthu', '2000-07-10', 'a@a.com', '31983094506', '', '', '', '', '', '', '', '', '0', 0, '', '', '', '', NULL, '', '', '', NULL, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `paciente_documentos`
--

CREATE TABLE `paciente_documentos` (
  `id` int(11) NOT NULL,
  `paciente_cpf` varchar(11) NOT NULL,
  `nome_documento` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `data_upload` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id_pagamento` int(11) NOT NULL,
  `id_entrada` int(11) NOT NULL,
  `ano` smallint(6) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `data_pagto` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome_servico` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `duracao_minutos` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome_servico`, `preco`, `duracao_minutos`) VALUES
(13, 'Massagem terapeutica', 199.90, 45);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_criacao` datetime DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nome`, `email`, `cargo`, `status`, `data_criacao`, `ultimo_acesso`, `tentativas_login`, `reset_token`, `reset_expira`, `foto_perfil`) VALUES
(15, 'Romulo Ferreira de Oliveira', 'p', 'Romulo Ferreira de Oliveira', 'openspacemidiasocial@gmail.com', 'Médico(a)', 'ativo', '2025-07-23 15:18:08', NULL, 0, NULL, NULL, NULL),
(16, 'Teste', '$2y$10$L0bQHRbYwGWhr3osiHQzAeGLF8Gys55hb6YHMxOgdh1whvOjR6kpu', 'Teste', 'gu@gu.gu', 'Enfermeiro(a)', 'ativo', '2025-07-23 15:20:27', NULL, 0, NULL, NULL, NULL),
(17, 'Gustavo Alves', '$2y$10$rjOcmxnm6AH4b9l08P9uo.LZ5NdzMpAsJIZUCIG7y1CdcwJwjuGlu', 'Gustavo', 'gustavo@clinica.com', 'Estudante', 'ativo', '2025-07-24 18:51:58', NULL, 0, NULL, NULL, NULL),
(18, 'admin', '$2y$10$NrMhKuc9XbkP845YN9LuBet3ztGQg0a.iQFICZAtdfJ5dpTw1uPXm', 'Admaster', 'a22@a.a', 'Administrador', 'ativo', '2025-07-24 18:52:27', NULL, 0, NULL, NULL, NULL),
(19, 'eu', '$2y$10$GyjsGWVcRoyLjgjNw87N5eHKGRWbcH3PqT/miYiwWOm7cMtAJviJK', 'eu', 'a@a.a', 'a', 'ativo', '2025-07-24 15:53:16', NULL, 0, NULL, NULL, NULL),
(20, 'Funcionario Teste 1', '$2y$10$ESstIziSECA7GoRG9x9c1.eWx1CRUIPrdv9IYsskOD3HlTnT6oj4i', 'Funcionario Teste 1', 'funcionario1@example.com', 'Recepcionista', 'ativo', '2025-09-24 23:35:41', NULL, 0, NULL, NULL, NULL),
(21, 'Dr. Robert', '$2y$10$xrae1upv4oFhKvpLwvWL8..1hBvnWD0RMdzOB08EaSGprpQpY9l5.', 'Dr. Robert', 'rob_2k@gmail.com', 'CEO', 'ativo', '2025-10-09 08:41:34', NULL, 0, NULL, NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `atividades`
--
ALTER TABLE `atividades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_cpf` (`paciente_cpf`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id_despesa`);

--
-- Índices de tabela `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id_entrada`);

--
-- Índices de tabela `evolucoes`
--
ALTER TABLE `evolucoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paciente_cpf` (`paciente_cpf`);

--
-- Índices de tabela `financeiro`
--
ALTER TABLE `financeiro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consulta_id` (`consulta_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remetente_id` (`remetente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criador_id` (`criador_id`);

--
-- Índices de tabela `notificacoes_usuarios`
--
ALTER TABLE `notificacoes_usuarios`
  ADD PRIMARY KEY (`notificacao_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`cpf`);

--
-- Índices de tabela `paciente_documentos`
--
ALTER TABLE `paciente_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_cpf` (`paciente_cpf`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id_pagamento`),
  ADD KEY `id_entrada` (`id_entrada`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `atividades`
--
ALTER TABLE `atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `id_despesa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `entradas`
--
ALTER TABLE `entradas`
  MODIFY `id_entrada` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `evolucoes`
--
ALTER TABLE `evolucoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `financeiro`
--
ALTER TABLE `financeiro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `paciente_documentos`
--
ALTER TABLE `paciente_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id_pagamento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`paciente_cpf`) REFERENCES `pacientes` (`cpf`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `consultas_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `consultas_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `evolucoes`
--
ALTER TABLE `evolucoes`
  ADD CONSTRAINT `fk_evolucoes_paciente` FOREIGN KEY (`paciente_cpf`) REFERENCES `pacientes` (`cpf`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `financeiro`
--
ALTER TABLE `financeiro`
  ADD CONSTRAINT `financeiro_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `financeiro_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`criador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notificacoes_usuarios`
--
ALTER TABLE `notificacoes_usuarios`
  ADD CONSTRAINT `notificacoes_usuarios_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_usuarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `paciente_documentos`
--
ALTER TABLE `paciente_documentos`
  ADD CONSTRAINT `paciente_documentos_ibfk_1` FOREIGN KEY (`paciente_cpf`) REFERENCES `pacientes` (`cpf`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
