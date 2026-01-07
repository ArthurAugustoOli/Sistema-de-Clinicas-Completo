-- Dropar o banco de dados se já existir
DROP DATABASE IF EXISTS clinicadb;

-- Criar o banco de dados
CREATE DATABASE clinicadb /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE clinicadb;

-- Criar a tabela de funcionários
CREATE TABLE funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    data_nasc DATE DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    telefone VARCHAR(15) DEFAULT NULL,
    whatsapp VARCHAR(15) DEFAULT NULL,
    cargo VARCHAR(50) DEFAULT NULL,
    turno VARCHAR(20) DEFAULT NULL,
    horario_trabalho VARCHAR(50) DEFAULT NULL,
    status ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar a tabela de pacientes
CREATE TABLE pacientes (
    cpf VARCHAR(11) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    data_nasc DATE DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    telefone VARCHAR(15) DEFAULT NULL,
    cep VARCHAR(9) DEFAULT NULL,
    logradouro VARCHAR(255) DEFAULT NULL,
    numero VARCHAR(10) DEFAULT NULL,
    complemento VARCHAR(100) DEFAULT NULL,
    bairro VARCHAR(100) DEFAULT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    estado VARCHAR(2) DEFAULT NULL,
    doencas TEXT DEFAULT NULL,
    alergias TEXT DEFAULT NULL,
    tem_convenio TINYINT(1) NOT NULL DEFAULT 0,
    convenio VARCHAR(100) DEFAULT NULL,
    numero_convenio VARCHAR(50) DEFAULT NULL,
    tipo_sanguineo VARCHAR(5) DEFAULT NULL,
    nome_contato_emergencia VARCHAR(100) DEFAULT NULL,
    numero_contato_emergencia VARCHAR(15) DEFAULT NULL,
    filiacao_contato_emergencia VARCHAR(100) DEFAULT NULL,
    condicoes_medicas TEXT DEFAULT NULL,
    remedios_em_uso TEXT DEFAULT NULL,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar a tabela de consultas

-- Criar a tabela de serviços oferecidos
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_servico VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    duracao_minutos INT NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_consulta DATETIME NOT NULL,
    paciente_cpf VARCHAR(11) NOT NULL,
    profissional_id INT NOT NULL,
    servico_id INT NULL,
    procedimento VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    status ENUM('Agendada', 'Cancelada', 'Concluída') NOT NULL DEFAULT 'Agendada',
    FOREIGN KEY (paciente_cpf) REFERENCES pacientes(cpf) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (profissional_id) REFERENCES funcionarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Criar a tabela financeira
CREATE TABLE financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL,
    servico_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status_pagamento ENUM('PENDENTE', 'PAGO') NOT NULL DEFAULT 'PENDENTE',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_pagamento DATETIME NULL,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar a tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso DATETIME NULL,
    tentativas_login INT DEFAULT 0,
    reset_token VARCHAR(100) NULL,
    reset_expira DATETIME NULL,
    foto_perfil VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela de documentos dos pacientes
CREATE TABLE paciente_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_cpf VARCHAR(11) NOT NULL,
    nome_documento VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_cpf) REFERENCES pacientes (cpf) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela para mensagens de chat
CREATE TABLE mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela para notificações
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'warning', 'success', 'danger') NOT NULL DEFAULT 'info',
    criador_id INT NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NULL,
    para_todos TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela para relacionar notificações com usuários
CREATE TABLE notificacoes_usuarios (
    notificacao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (notificacao_id, usuario_id),
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
