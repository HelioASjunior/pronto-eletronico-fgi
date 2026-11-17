-- =========================================================
-- Banco de dados do PontoEletrônico
-- Arquivo: banco/banco.sql
--
-- Como usar: no phpMyAdmin, abra a aba "Importar" e escolha este arquivo
-- (ou cole o conteúdo na aba "SQL"). Pode rodar mais de uma vez:
-- o que já existe não é apagado.
--
-- Este arquivo cria só a ESTRUTURA (banco e tabelas), sem nenhum dado.
-- O administrador e as senhas ficam cadastrados direto no banco de dados,
-- nunca aqui, porque este arquivo vai para o GitHub.
-- =========================================================

CREATE DATABASE IF NOT EXISTS ponto_eletronico
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE ponto_eletronico;


-- ---------- Tabela de funcionários ----------
-- Guarda quem pode entrar no sistema.
-- cpf: só os 11 números, sem pontos e traço.
-- senha: nunca a senha pura, e sim o "hash" gerado pelo password_hash() do PHP.
CREATE TABLE IF NOT EXISTS funcionarios (
    id_funcionario INT(11)      NOT NULL AUTO_INCREMENT,
    nome           VARCHAR(100) NOT NULL,
    sexo           ENUM('feminino', 'masculino', 'nao_informado') NOT NULL,
    cpf            CHAR(11)     NOT NULL,
    usuario        VARCHAR(50)  NOT NULL,
    senha          VARCHAR(255) NOT NULL,
    cargo          VARCHAR(60)  DEFAULT NULL,
    departamento   VARCHAR(60)  DEFAULT NULL,
    tipo_acesso    ENUM('funcionario', 'administrador') NOT NULL DEFAULT 'funcionario',
    status         ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_funcionario),
    UNIQUE KEY cpf (cpf),
    UNIQUE KEY usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------- Tabela de registros de ponto ----------
-- Cada clique em "Registrar Ponto" vira uma linha aqui.
-- data_hora: dia e horário exatos do registro.
-- tipo: entrada ou saída (o PHP alterna sozinho: entrada, saída, entrada, saída...).
-- observacao: para uso futuro (ex.: ajustes de ponto feitos pelo administrador).
-- A chave estrangeira (FOREIGN KEY) liga o registro ao funcionário e não deixa
-- apagar um funcionário que já tem pontos registrados (para isso existe o status "inativo").
CREATE TABLE IF NOT EXISTS registros_ponto (
    id_registro    INT(11)       NOT NULL AUTO_INCREMENT,
    id_funcionario INT(11)       NOT NULL,
    data_hora      DATETIME      NOT NULL,
    tipo           ENUM('entrada', 'saida') NOT NULL,
    observacao     VARCHAR(255)  DEFAULT NULL,
    PRIMARY KEY (id_registro),
    -- índice para buscar rápido os pontos de um funcionário em um período
    KEY funcionario_data (id_funcionario, data_hora),
    CONSTRAINT fk_registro_funcionario
        FOREIGN KEY (id_funcionario) REFERENCES funcionarios (id_funcionario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
