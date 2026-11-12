-- Clube das Patas / SAEP 2026
-- Conecte-se primeiro ao banco saep_db e execute este arquivo.
-- Em uma instalação nova, crie o banco com: CREATE DATABASE saep_db;
-- APP_KEY deve permanecer igual entre execuções: ela protege e permite
-- pesquisar os CPFs criptografados pela aplicação.

BEGIN;

CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) NOT NULL DEFAULT 'equipe' CHECK (perfil IN ('equipe', 'cliente')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS perfil VARCHAR(20) NOT NULL DEFAULT 'equipe';
CREATE UNIQUE INDEX IF NOT EXISTS usuarios_email_lower_unique ON usuarios (LOWER(email));

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'usuarios_perfil_check'
          AND conrelid = 'usuarios'::regclass
    ) THEN
        ALTER TABLE usuarios
            ADD CONSTRAINT usuarios_perfil_check CHECK (perfil IN ('equipe', 'cliente'));
    END IF;
END;
$$;

CREATE TABLE IF NOT EXISTS tutores (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(150),
    endereco VARCHAR(255),
    cpf_hash VARCHAR(64) NOT NULL UNIQUE,
    usuario_id INTEGER UNIQUE REFERENCES usuarios(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE tutores ADD COLUMN IF NOT EXISTS cpf_hash VARCHAR(64);
ALTER TABLE tutores ADD COLUMN IF NOT EXISTS usuario_id INTEGER REFERENCES usuarios(id) ON DELETE CASCADE;
CREATE UNIQUE INDEX IF NOT EXISTS tutores_cpf_hash_unique ON tutores (cpf_hash);
CREATE UNIQUE INDEX IF NOT EXISTS tutores_usuario_id_unique ON tutores (usuario_id);

CREATE TABLE IF NOT EXISTS pets (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(50) NOT NULL,
    raca VARCHAR(100),
    data_nascimento DATE,
    tutor_id INTEGER NOT NULL REFERENCES tutores(id) ON DELETE RESTRICT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tutor_id, nome)
);

CREATE TABLE IF NOT EXISTS agendamentos (
    id SERIAL PRIMARY KEY,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    observacoes TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'agendado',
    resumo_conclusao TEXT,
    concluido_em TIMESTAMP,
    pet_id INTEGER NOT NULL REFERENCES pets(id) ON DELETE RESTRICT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT agendamentos_status_check CHECK (status IN ('agendado', 'concluido')),
    UNIQUE (pet_id, data_agendamento, hora_agendamento)
);

ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'agendado';
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS resumo_conclusao TEXT;
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS concluido_em TIMESTAMP;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'agendamentos_status_check'
          AND conrelid = 'agendamentos'::regclass
    ) THEN
        ALTER TABLE agendamentos
            ADD CONSTRAINT agendamentos_status_check CHECK (status IN ('agendado', 'concluido'));
    END IF;
END;
$$;

CREATE INDEX IF NOT EXISTS agendamentos_data_hora_idx
    ON agendamentos (data_agendamento, hora_agendamento);
CREATE INDEX IF NOT EXISTS pets_tutor_id_idx ON pets (tutor_id);
CREATE INDEX IF NOT EXISTS agendamentos_pet_id_idx ON agendamentos (pet_id);

-- Senha da conta administradora de demonstração: 1234.
-- Senha das outras contas de demonstração: password.
-- Os hashes são bcrypt e compatíveis com password_verify() do PHP.
INSERT INTO usuarios (id, nome, email, senha) VALUES
    (1, 'Administrador', 'admin@clinica.com', '$2y$12$wZVRJx96QLojW7QP0YQ78eJuGD4tK6Zq4alLKF2FrMPNQWGtYjWaq'),
    (2, 'Dr. Carlos Mendes', 'carlos@clinica.com', '$2y$10$BIZzHeOlqLSs9rkolIT1G.zhxFrkobHz7ds.lfQNaUTiRBmh9eiWu'),
    (3, 'Dra. Ana Lima', 'ana@clinica.com', '$2y$10$BIZzHeOlqLSs9rkolIT1G.zhxFrkobHz7ds.lfQNaUTiRBmh9eiWu')
ON CONFLICT (email) DO NOTHING;

-- CPFs sintéticos de demonstração. Formato do envelope: base64(IV):base64(ciphertext):base64(tag).
-- APP_KEY de desenvolvimento documentada no .env.example descriptografa esses registros.
INSERT INTO tutores (id, nome, cpf, telefone, email, endereco, cpf_hash) VALUES
    (1, 'Maria Oliveira', 'AAAAAAAAAAAAAAAB:R6HUUvKkKfaNoDg=:sMaQ/bmWrfC5OzQaaOkP5A==', '(11) 98765-4321', 'maria.oliveira@email.com', 'Rua das Flores, 123, São Paulo - SP', '1db80c24697c3f9fe0066d330f5d471364b321bda9be71a2af0dd205aa9d974f'),
    (2, 'João Silva', 'AAAAAAAAAAAAAAAC:QCil44GDc1S4NIc=:dYbgwJFwijJIIZmegTPRyQ==', '(21) 97654-3210', 'joao.silva@email.com', 'Av. Brasil, 456, Rio de Janeiro - RJ', '10bcc7e265bdd3e7a32d1a9e30207f3b67c374efca691c1579dbf04c5aee910d'),
    (3, 'Fernanda Santos', 'AAAAAAAAAAAAAAAD:NyNAXc2eL4ekHaQ=:qKSM1mbjsKXE9hQ+H7gs+A==', '(31) 96543-2109', 'fernanda.santos@email.com', 'Rua Minas, 789, Belo Horizonte - MG', 'cce7ae35655803b7b25fb06c6a832b5f6e5f582019e3e4cbe8444bf86b12c19c')
ON CONFLICT (id) DO NOTHING;

INSERT INTO pets (id, nome, especie, raca, data_nascimento, tutor_id) VALUES
    (1, 'Rex', 'Cachorro', 'Labrador', '2020-03-15', 1),
    (2, 'Mimi', 'Gato', 'Persa', '2019-07-22', 1),
    (3, 'Thor', 'Cachorro', 'Bulldog Francês', '2021-11-10', 2)
ON CONFLICT (id) DO NOTHING;

INSERT INTO agendamentos (id, data_agendamento, hora_agendamento, motivo, observacoes, pet_id) VALUES
    (1, CURRENT_DATE + 2, '09:00', 'Consulta de rotina', 'Verificar vacinação anual', 1),
    (2, CURRENT_DATE + 2, '10:30', 'Vacinação', 'Vacina antirrábica', 3),
    (3, CURRENT_DATE + 3, '14:00', 'Retorno', 'Acompanhamento de rotina', 2)
ON CONFLICT (id) DO NOTHING;

-- Mantém as sequências prontas para novos cadastros após a inclusão de IDs explícitos.
SELECT setval(pg_get_serial_sequence('usuarios', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM usuarios), 1));
SELECT setval(pg_get_serial_sequence('tutores', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM tutores), 1));
SELECT setval(pg_get_serial_sequence('pets', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM pets), 1));
SELECT setval(pg_get_serial_sequence('agendamentos', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM agendamentos), 1));

COMMIT;
