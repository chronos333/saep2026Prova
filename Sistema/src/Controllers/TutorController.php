<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;
use RuntimeException;
use VetCare\Models\Tutor;

final class TutorController
{
    public function handle(): void
    {
require_once __DIR__ . '/../Core/bootstrap.php';
requireStaff();
$db = getDB();
$action = (string) ($_GET['action'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$form = ['nome' => '', 'cpf' => '', 'telefone' => '', 'email' => '', 'endereco' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postedAction = (string) ($_POST['action'] ?? '');
    $postedId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    if ($postedAction === 'delete' && $postedId > 0) {
        try {
            $db->beginTransaction();
            $account = $db->prepare(
                'DELETE FROM usuarios
                 WHERE id = (SELECT usuario_id FROM tutores WHERE id = :id)'
            );
            $account->execute(['id' => $postedId]);
            $accountRemoved = $account->rowCount() > 0;
            if (!$accountRemoved) {
                $statement = $db->prepare('DELETE FROM tutores WHERE id = :id');
                $statement->execute(['id' => $postedId]);
                $accountRemoved = $statement->rowCount() > 0;
            }
            $db->commit();
            flash('success', $accountRemoved ? 'Cliente removido.' : 'Cliente não encontrado.');
        } catch (PDOException $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            dbFailure($error, 'Este tutor possui pets vinculados e não pode ser removido.');
        }
        redirect('tutores.php');
    }
    $form = [
        'nome' => trim((string) ($_POST['nome'] ?? '')),
        'cpf' => cpfDigits((string) ($_POST['cpf'] ?? '')),
        'telefone' => trim((string) ($_POST['telefone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'endereco' => trim((string) ($_POST['endereco'] ?? '')),
    ];
    $action = $postedAction;
    $id = $postedId;
    if ($form['nome'] === '' || strlen($form['nome']) > 400) {
        $errors[] = 'Informe o nome do tutor (até 100 caracteres).';
    }
    if ($form['cpf'] === '') {
        $errors[] = 'Informe o CPF do tutor.';
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if ($form['telefone'] !== '' && strlen($form['telefone']) > 80) {
        $errors[] = 'O telefone deve ter até 20 caracteres.';
    }
    if ($form['endereco'] !== '' && strlen($form['endereco']) > 1020) {
        $errors[] = 'O endereço deve ter até 255 caracteres.';
    }
    if ($action === 'update' && $id < 1) {
        $errors[] = 'Tutor inválido.';
    }
    if ($errors === []) {
        try {
            $values = [
                'nome' => $form['nome'],
                'cpf' => encryptCpf($form['cpf']),
                'cpf_hash' => cpfSearchHash($form['cpf']),
                'telefone' => $form['telefone'] ?: null,
                'email' => $form['email'] ?: null,
                'endereco' => $form['endereco'] ?: null,
            ];
            if ($action === 'update') {
                $values['id'] = $id;
                $statement = $db->prepare('UPDATE tutores SET nome = :nome, cpf = :cpf, cpf_hash = :cpf_hash, telefone = :telefone, email = :email, endereco = :endereco WHERE id = :id');
                $statement->execute($values);
                flash('success', $statement->rowCount() > 0 ? 'Dados do tutor atualizados.' : 'Nenhuma alteração foi necessária.');
            } else {
                $statement = $db->prepare('INSERT INTO tutores (nome, cpf, cpf_hash, telefone, email, endereco) VALUES (:nome, :cpf, :cpf_hash, :telefone, :email, :endereco)');
                $statement->execute($values);
                flash('success', 'Tutor cadastrado com segurança.');
            }
            redirect('tutores.php');
        } catch (PDOException $error) {
            dbFailure($error, 'Não foi possível salvar. Verifique se o CPF já está cadastrado.');
        } catch (RuntimeException $error) {
            dbFailure($error);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action === 'edit' && $id > 0) {
    $statement = $db->prepare('SELECT id, nome, cpf, telefone, email, endereco FROM tutores WHERE id = :id');
    $statement->execute(['id' => $id]);
    $tutor = $statement->fetch();
    if ($tutor === false) {
        flash('error', 'Tutor não encontrado.');
        redirect('tutores.php');
    }
    $form = [
        'nome' => $tutor['nome'],
        'cpf' => decryptCpf($tutor['cpf']),
        'telefone' => $tutor['telefone'] ?? '',
        'email' => $tutor['email'] ?? '',
        'endereco' => $tutor['endereco'] ?? '',
    ];
}

$search = trim((string) ($_GET['q'] ?? ''));
$tutors = (new Tutor($db))->search($search);

renderHeader('Clientes', 'tutores');
?>
<section class="page-heading"><div><span class="eyebrow">FAMÍLIAS DO CLUBE</span><h1>Clientes e tutores</h1><p class="muted">Cada família e os responsáveis por seus pets.</p></div><a class="button button-primary" href="tutores.php?action=create"><span aria-hidden="true">+</span> Novo cliente</a></section>
<?php if ($errors !== []): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if (in_array($action, ['create', 'edit', 'update'], true)): ?>
<section class="content-card form-card"><div class="section-heading"><div><span class="eyebrow">DADOS DO RESPONSÁVEL</span><h2><?= $action === 'edit' || $action === 'update' ? 'Editar tutor' : 'Novo tutor' ?></h2></div><a class="text-link" href="tutores.php">Cancelar</a></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="<?= $action === 'edit' || $action === 'update' ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= (int) $id ?>">
        <label class="field-label">Nome completo<input class="input" name="nome" maxlength="100" value="<?= e($form['nome']) ?>" required></label>
        <label class="field-label">CPF<input class="input" name="cpf" inputmode="numeric" maxlength="20" value="<?= e($form['cpf']) ?>" placeholder="000.000.000-00" required><span class="field-hint">Armazenado com proteção.</span></label>
        <label class="field-label">Telefone<input class="input" name="telefone" maxlength="20" value="<?= e($form['telefone']) ?>" placeholder="(11) 99999-9999"></label>
        <label class="field-label">E-mail<input class="input" name="email" type="email" maxlength="150" value="<?= e($form['email']) ?>"></label>
        <label class="field-label field-full">Endereço<input class="input" name="endereco" maxlength="255" value="<?= e($form['endereco']) ?>"></label>
        <div class="form-actions"><button class="button button-primary" type="submit">Salvar tutor</button><a class="button button-secondary" href="tutores.php">Cancelar</a></div>
    </form>
</section>
<?php endif; ?>
<section class="content-card">
    <form method="get" class="search-form"><label class="visually-hidden" for="q">Buscar tutor</label><span class="search-icon" aria-hidden="true">⌕</span><input class="input" id="q" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome, CPF, telefone ou e-mail"><button class="button button-secondary" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="button button-quiet" href="tutores.php">Limpar</a><?php endif; ?></form>
    <div class="table-wrap"><table><thead><tr><th>Tutor</th><th>Contato</th><th>CPF</th><th>Pets</th><th class="table-actions">Ações</th></tr></thead><tbody>
    <?php foreach ($tutors as $tutor): ?><tr><td><strong><?= e($tutor['nome']) ?></strong><span class="table-sub"><?= e($tutor['endereco'] ?: 'Endereço não informado') ?></span></td><td><?= e($tutor['telefone'] ?: '—') ?><span class="table-sub"><?= e($tutor['email'] ?: '—') ?></span></td><td><span class="privacy-tag">Protegido</span></td><td><span class="count-pill"><?= (int) $tutor['pets_total'] ?></span></td><td class="table-actions"><a class="button button-quiet button-small" href="tutores.php?action=edit&id=<?= (int) $tutor['id'] ?>">Editar</a><form method="post" class="inline-form" data-confirm="Remover este tutor? Tutores com pets vinculados não podem ser removidos."><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $tutor['id'] ?>"><button class="button button-danger-quiet button-small" type="submit">Excluir</button></form></td></tr><?php endforeach; ?>
    <?php if ($tutors === []): ?><tr><td colspan="5"><div class="empty-state compact"><span class="empty-icon">♡</span><h3><?= $search !== '' ? 'Nenhum resultado encontrado' : 'Nenhuma família cadastrada' ?></h3><p><?= $search !== '' ? 'Tente buscar por outro termo.' : 'Cadastre o primeiro cliente para começar.' ?></p></div></td></tr><?php endif; ?>
    </tbody></table></div><div class="table-footer"><?= count($tutors) ?> <?= count($tutors) === 1 ? 'tutor' : 'tutores' ?><?= $search !== '' ? ' encontrados' : ' cadastrados' ?></div>
</section>
<?php renderFooter(); ?>
<?php
    }
}
