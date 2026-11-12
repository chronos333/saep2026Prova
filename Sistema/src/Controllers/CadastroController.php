<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;

final class CadastroController
{
    public function handle(): void
    {
        require_once __DIR__ . '/../Core/bootstrap.php';

        if (isset($_SESSION['user_id'])) {
            redirect(homeForRole());
        }

        $form = [
            'nome' => '',
            'cpf' => '',
            'telefone' => '',
            'email' => '',
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $form = [
                'nome' => trim((string) ($_POST['nome'] ?? '')),
                'cpf' => cpfDigits((string) ($_POST['cpf'] ?? '')),
                'telefone' => trim((string) ($_POST['telefone'] ?? '')),
                'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            ];
            $password = (string) ($_POST['senha'] ?? '');
            $passwordConfirmation = (string) ($_POST['confirmar_senha'] ?? '');

            if ($form['nome'] === '' || strlen($form['nome']) > 400) {
                $errors[] = 'Informe seu nome completo.';
            }
            if ($form['cpf'] === '') {
                $errors[] = 'Informe o CPF.';
            }
            if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 150) {
                $errors[] = 'Informe um e-mail válido.';
            }
            if ($form['telefone'] === '' || strlen($form['telefone']) > 80) {
                $errors[] = 'Informe seu telefone com até 20 caracteres.';
            }
            if (strlen($password) < 10 || strlen($password) > 72) {
                $errors[] = 'Crie uma senha de 10 a 72 caracteres.';
            }
            if (!hash_equals($password, $passwordConfirmation)) {
                $errors[] = 'As senhas informadas não coincidem.';
            }

            if ($errors === []) {
                $database = getDB();
                try {
                    $database->beginTransaction();
                    $insertUser = $database->prepare(
                        'INSERT INTO usuarios (nome, email, senha, perfil)
                         VALUES (:nome, :email, :senha, \'cliente\')
                         RETURNING id'
                    );
                    $insertUser->execute([
                        'nome' => $form['nome'],
                        'email' => $form['email'],
                        'senha' => password_hash($password, PASSWORD_DEFAULT),
                    ]);
                    $userId = (int) $insertUser->fetchColumn();

                    $insertTutor = $database->prepare(
                        'INSERT INTO tutores (nome, cpf, cpf_hash, telefone, email, usuario_id)
                         VALUES (:nome, :cpf, :cpf_hash, :telefone, :email, :usuario_id)'
                    );
                    $insertTutor->execute([
                        'nome' => $form['nome'],
                        'cpf' => encryptCpf($form['cpf']),
                        'cpf_hash' => cpfSearchHash($form['cpf']),
                        'telefone' => $form['telefone'],
                        'email' => $form['email'],
                        'usuario_id' => $userId,
                    ]);
                    $database->commit();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $form['nome'];
                    $_SESSION['user_role'] = 'cliente';
                    $_SESSION['created_at'] = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    redirect('cliente.php');
                } catch (PDOException $error) {
                    if ($database->inTransaction()) {
                        $database->rollBack();
                    }
                    error_log((string) $error);
                    $errors[] = 'Não foi possível criar sua conta. Confira seus dados ou entre se você já possui cadastro.';
                } catch (\RuntimeException $error) {
                    if ($database->inTransaction()) {
                        $database->rollBack();
                    }
                    error_log((string) $error);
                    $errors[] = 'Não foi possível proteger os dados do cadastro. Tente novamente.';
                }
            }
        }

        $brandName = APP_NAME;
        $title = 'Criar conta';
        require __DIR__ . '/../Views/auth/cadastro.php';
    }
}
