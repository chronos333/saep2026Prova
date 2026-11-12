<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;

final class ClienteController
{
    public function handle(): void
    {
        require_once __DIR__ . '/../Core/bootstrap.php';
        requireCustomer();

        $database = getDB();
        $errors = [];
        $form = ['nome' => '', 'especie' => '', 'raca' => '', 'data_nascimento' => ''];
        $tutorStatement = $database->prepare('SELECT id FROM tutores WHERE usuario_id = :usuario_id');
        $tutorStatement->execute(['usuario_id' => (int) $_SESSION['user_id']]);
        $tutorId = (int) $tutorStatement->fetchColumn();

        if ($tutorId < 1) {
            http_response_code(409);
            exit('Não foi possível encontrar o cadastro vinculado a esta conta. Entre em contato com a equipe da clínica.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $action = (string) ($_POST['action'] ?? '');
            if ($action !== 'add_pet') {
                http_response_code(400);
                exit('Operação inválida.');
            }
            $form = [
                'nome' => trim((string) ($_POST['nome'] ?? '')),
                'especie' => trim((string) ($_POST['especie'] ?? '')),
                'raca' => trim((string) ($_POST['raca'] ?? '')),
                'data_nascimento' => trim((string) ($_POST['data_nascimento'] ?? '')),
            ];

            if ($form['nome'] === '' || strlen($form['nome']) > 400) {
                $errors[] = 'Informe o nome do pet.';
            }
            if (!in_array($form['especie'], ['Cachorro', 'Gato', 'Ave', 'Roedor', 'Réptil', 'Outro'], true)) {
                $errors[] = 'Escolha uma espécie válida.';
            }
            if (strlen($form['raca']) > 400) {
                $errors[] = 'A raça deve ter até 100 caracteres.';
            }
            if ($form['data_nascimento'] !== '' && !validDate($form['data_nascimento'])) {
                $errors[] = 'Informe uma data de nascimento válida.';
            }
            if ($form['data_nascimento'] !== '' && $form['data_nascimento'] > date('Y-m-d')) {
                $errors[] = 'A data de nascimento não pode estar no futuro.';
            }

            if ($errors === []) {
                try {
                    $statement = $database->prepare(
                        'INSERT INTO pets (nome, especie, raca, data_nascimento, tutor_id)
                         VALUES (:nome, :especie, :raca, :data_nascimento, :tutor_id)'
                    );
                    $statement->execute([
                        'nome' => $form['nome'],
                        'especie' => $form['especie'],
                        'raca' => $form['raca'] ?: null,
                        'data_nascimento' => $form['data_nascimento'] ?: null,
                        'tutor_id' => $tutorId,
                    ]);
                    flash('success', 'Seu pet agora faz parte do Clube das Patas!');
                    redirect('cliente.php#pets');
                } catch (PDOException $error) {
                    error_log((string) $error);
                    $errors[] = 'Não foi possível cadastrar o pet. Verifique se já existe um pet com esse nome no seu cadastro.';
                }
            }
        }

        $petsStatement = $database->prepare(
            'SELECT id, nome, especie, raca, data_nascimento
             FROM pets WHERE tutor_id = :tutor_id ORDER BY nome'
        );
        $petsStatement->execute(['tutor_id' => $tutorId]);
        $pets = $petsStatement->fetchAll();

        $appointmentsStatement = $database->prepare(
            'SELECT a.id, a.data_agendamento, a.hora_agendamento, a.motivo, a.observacoes,
                    a.status, a.resumo_conclusao,
                    p.nome AS pet_nome, p.especie
             FROM agendamentos a
             JOIN pets p ON p.id = a.pet_id
             WHERE p.tutor_id = :tutor_id
             ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
             LIMIT 20'
        );
        $appointmentsStatement->execute(['tutor_id' => $tutorId]);
        $appointments = $appointmentsStatement->fetchAll();

        renderHeader('Meu espaço', 'portal');
        require __DIR__ . '/../Views/cliente/dashboard.php';
        renderFooter();
    }
}
