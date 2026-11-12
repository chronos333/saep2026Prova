<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;
use VetCare\Models\Pet;

final class PetController
{
    public function handle(): void
    {
require_once __DIR__ . '/../Core/bootstrap.php';
requireStaff();
$db = getDB();
$action = (string) ($_GET['action'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$form = ['nome' => '', 'especie' => '', 'raca' => '', 'data_nascimento' => '', 'tutor_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postedAction = (string) ($_POST['action'] ?? '');
    $postedId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    if ($postedAction === 'delete' && $postedId > 0) {
        try {
            $statement = $db->prepare('DELETE FROM pets WHERE id = :id');
            $statement->execute(['id' => $postedId]);
            flash('success', $statement->rowCount() > 0 ? 'Pet removido.' : 'Pet não encontrado.');
        } catch (PDOException $error) {
            dbFailure($error, 'Este pet possui agendamentos vinculados e não pode ser removido.');
        }
        redirect('pets.php');
    }
    $form = [
        'nome' => trim((string) ($_POST['nome'] ?? '')),
        'especie' => trim((string) ($_POST['especie'] ?? '')),
        'raca' => trim((string) ($_POST['raca'] ?? '')),
        'data_nascimento' => trim((string) ($_POST['data_nascimento'] ?? '')),
        'tutor_id' => trim((string) ($_POST['tutor_id'] ?? '')),
    ];
    $action = $postedAction;
    $id = $postedId;
    if ($form['nome'] === '' || strlen($form['nome']) > 400) {
        $errors[] = 'Informe o nome do pet (até 100 caracteres).';
    }
    if ($form['especie'] === '' || strlen($form['especie']) > 200) {
        $errors[] = 'Informe a espécie do pet.';
    }
    if (strlen($form['raca']) > 400) {
        $errors[] = 'A raça deve ter até 100 caracteres.';
    }
    if ($form['data_nascimento'] !== '' && !validDate($form['data_nascimento'])) {
        $errors[] = 'Informe uma data de nascimento válida.';
    }
    $tutorId = filter_var($form['tutor_id'], FILTER_VALIDATE_INT);
    if ($tutorId === false || $tutorId < 1) {
        $errors[] = 'Selecione um tutor responsável.';
    }
    if ($action === 'update' && $id < 1) {
        $errors[] = 'Pet inválido.';
    }
    if ($errors === []) {
        try {
            $values = [
                'nome' => $form['nome'],
                'especie' => $form['especie'],
                'raca' => $form['raca'] ?: null,
                'data_nascimento' => $form['data_nascimento'] ?: null,
                'tutor_id' => $tutorId,
            ];
            if ($action === 'update') {
                $values['id'] = $id;
                $statement = $db->prepare('UPDATE pets SET nome = :nome, especie = :especie, raca = :raca, data_nascimento = :data_nascimento, tutor_id = :tutor_id WHERE id = :id');
                $statement->execute($values);
                flash('success', $statement->rowCount() > 0 ? 'Dados do pet atualizados.' : 'Nenhuma alteração foi necessária.');
            } else {
                $statement = $db->prepare('INSERT INTO pets (nome, especie, raca, data_nascimento, tutor_id) VALUES (:nome, :especie, :raca, :data_nascimento, :tutor_id)');
                $statement->execute($values);
                flash('success', 'Pet cadastrado e associado ao tutor.');
            }
            redirect('pets.php');
        } catch (PDOException $error) {
            dbFailure($error, 'Não foi possível salvar o pet. Confira os dados e o tutor selecionado.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action === 'edit' && $id > 0) {
    $statement = $db->prepare('SELECT id, nome, especie, raca, data_nascimento, tutor_id FROM pets WHERE id = :id');
    $statement->execute(['id' => $id]);
    $pet = $statement->fetch();
    if ($pet === false) {
        flash('error', 'Pet não encontrado.');
        redirect('pets.php');
    }
    $form = [
        'nome' => $pet['nome'],
        'especie' => $pet['especie'],
        'raca' => $pet['raca'] ?? '',
        'data_nascimento' => $pet['data_nascimento'] ?? '',
        'tutor_id' => (string) $pet['tutor_id'],
    ];
}

$tutors = $db->query('SELECT id, nome FROM tutores ORDER BY nome')->fetchAll();
$search = trim((string) ($_GET['q'] ?? ''));
$pets = (new Pet($db))->search($search);

renderHeader('Pets', 'pets');
?>
<section class="page-heading"><div><span class="eyebrow">CADASTROS</span><h1>Pets de todos os clientes</h1><p class="muted">Esta lista inclui também os pets cadastrados pelos clientes no portal.</p></div><a class="button button-primary" href="pets.php?action=create"><span aria-hidden="true">+</span> Novo pet</a></section>
<?php if ($errors !== []): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if (in_array($action, ['create', 'edit', 'update'], true)): ?>
<section class="content-card form-card"><div class="section-heading"><div><span class="eyebrow">FICHA DO PET</span><h2><?= $action === 'edit' || $action === 'update' ? 'Editar pet' : 'Cadastrar pet' ?></h2></div><a class="text-link" href="pets.php">Cancelar</a></div>
    <?php if ($tutors === []): ?><div class="alert alert-warning">Cadastre um tutor antes de registrar um pet. <a class="text-link" href="tutores.php?action=create">Cadastrar tutor →</a></div><?php else: ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="<?= $action === 'edit' || $action === 'update' ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= (int) $id ?>">
        <label class="field-label">Nome do pet<input class="input" name="nome" maxlength="100" value="<?= e($form['nome']) ?>" required></label>
        <label class="field-label">Espécie<select class="input" name="especie" required><option value="">Selecione</option><?php foreach (['Cachorro', 'Gato', 'Ave', 'Roedor', 'Réptil', 'Outro'] as $species): ?><option value="<?= e($species) ?>" <?= $form['especie'] === $species ? 'selected' : '' ?>><?= e($species) ?></option><?php endforeach; ?></select></label>
        <label class="field-label">Raça<input class="input" name="raca" maxlength="100" value="<?= e($form['raca']) ?>"></label>
        <label class="field-label">Data de nascimento<input class="input" type="date" name="data_nascimento" value="<?= e($form['data_nascimento']) ?>" max="<?= e(date('Y-m-d')) ?>"></label>
        <label class="field-label field-full">Tutor responsável<select class="input" name="tutor_id" required><option value="">Selecione o tutor</option><?php foreach ($tutors as $tutor): ?><option value="<?= (int) $tutor['id'] ?>" <?= (string) $tutor['id'] === $form['tutor_id'] ? 'selected' : '' ?>><?= e($tutor['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions"><button class="button button-primary" type="submit">Salvar pet</button><a class="button button-secondary" href="pets.php">Cancelar</a></div>
    </form><?php endif; ?>
</section>
<?php endif; ?>
<section class="content-card"><form method="get" class="search-form"><label class="visually-hidden" for="q">Buscar pet</label><span class="search-icon" aria-hidden="true">⌕</span><input class="input" id="q" name="q" value="<?= e($search) ?>" placeholder="Buscar por pet, tutor ou e-mail do cliente"><button class="button button-secondary" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="button button-quiet" href="pets.php">Limpar</a><?php endif; ?></form>
    <div class="pet-grid"><?php foreach ($pets as $pet): ?><article class="pet-card"><div class="pet-card-top"><div class="pet-avatar"><?= $pet['especie'] === 'Gato' ? '🐈' : '🐕' ?></div><span class="species-tag"><?= e($pet['especie']) ?></span></div><h3><?= e($pet['nome']) ?></h3><p class="pet-breed"><?= e($pet['raca'] ?: 'Raça não informada') ?><?= $pet['data_nascimento'] ? ' · ' . e(date('d/m/Y', strtotime($pet['data_nascimento']))) : '' ?></p><div class="pet-owner"><span class="owner-avatar"><?= e(userInitial($pet['tutor_nome'])) ?></span><span><small>TUTOR(A)</small><strong><?= e($pet['tutor_nome']) ?></strong><small class="pet-account"><?= $pet['cliente_email'] ? 'Cliente do portal · ' . e($pet['cliente_email']) : 'Cadastro da equipe' ?></small></span></div><div class="pet-actions"><a class="button button-quiet button-small" href="pets.php?action=edit&id=<?= (int) $pet['id'] ?>">Editar</a><form method="post" data-confirm="Remover este pet? Pets com agendamentos vinculados não podem ser removidos."><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><button class="button button-danger-quiet button-small" type="submit">Excluir</button></form></div></article><?php endforeach; ?>
    <?php if ($pets === []): ?><div class="empty-state compact pet-empty"><span class="empty-icon">✳</span><h3><?= $search !== '' ? 'Nenhum resultado encontrado' : 'Nenhum pet cadastrado' ?></h3><p><?= $search !== '' ? 'Tente buscar por outro termo.' : 'Cadastre um tutor e, em seguida, seu pet.' ?></p><?php if ($tutors !== []): ?><a class="button button-secondary" href="pets.php?action=create">Cadastrar pet</a><?php endif; ?></div><?php endif; ?></div>
    <div class="table-footer"><?= count($pets) ?> <?= count($pets) === 1 ? 'pet cadastrado' : 'pets cadastrados' ?></div>
</section>
<?php renderFooter(); ?>
<?php
    }
}
