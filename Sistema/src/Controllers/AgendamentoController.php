<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;
use VetCare\Models\Agendamento;

final class AgendamentoController
{
    public function handle(): void
    {
require_once __DIR__ . '/../Core/bootstrap.php';
requireStaff();
$db = getDB();
$action = (string) ($_GET['action'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$form = ['data_agendamento' => '', 'hora_agendamento' => '', 'motivo' => '', 'observacoes' => '', 'pet_id' => ''];
$completionSummary = '';
$completionAppointment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postedAction = (string) ($_POST['action'] ?? '');
    $postedId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    if ($postedAction === 'complete') {
        if ($postedId < 1) {
            flash('error', 'Atendimento inválido.');
            redirect('agendamentos.php');
        }
        $action = 'complete';
        $id = $postedId;
        $completionSummary = trim((string) ($_POST['resumo_conclusao'] ?? ''));
        if ($completionSummary === '') {
            $errors[] = 'Informe o resumo ou procedimento realizado.';
        } elseif (mb_strlen($completionSummary, 'UTF-8') > 5000) {
            $errors[] = 'O resumo deve ter até 5.000 caracteres.';
        }
        if ($errors === []) {
            try {
                $statement = $db->prepare(
                    "UPDATE agendamentos
                     SET status = 'concluido',
                         resumo_conclusao = :resumo_conclusao,
                         concluido_em = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = 'agendado'"
                );
                $statement->execute([
                    'resumo_conclusao' => $completionSummary,
                    'id' => $postedId,
                ]);
                if ($statement->rowCount() > 0) {
                    flash('success', 'Atendimento concluído e registrado.');
                } else {
                    $existing = $db->prepare('SELECT status FROM agendamentos WHERE id = :id');
                    $existing->execute(['id' => $postedId]);
                    $status = $existing->fetchColumn();
                    flash(
                        $status === false ? 'error' : 'warning',
                        $status === false ? 'Atendimento não encontrado.' : 'Este atendimento já foi concluído.'
                    );
                }
                redirect('agendamentos.php');
            } catch (PDOException $error) {
                dbFailure($error, 'Não foi possível registrar a conclusão do atendimento.');
                redirect('agendamentos.php');
            }
        }
    } elseif ($postedAction === 'delete' && $postedId > 0) {
        $statement = $db->prepare("DELETE FROM agendamentos WHERE id = :id AND status = 'agendado'");
        $statement->execute(['id' => $postedId]);
        $deleted = $statement->rowCount() > 0;
        flash($deleted ? 'success' : 'error', $deleted ? 'Agendamento cancelado.' : 'Agendamento não encontrado ou já concluído.');
        redirect('agendamentos.php');
    }
    if ($postedAction !== 'complete') {
    $form = [
        'data_agendamento' => trim((string) ($_POST['data_agendamento'] ?? '')),
        'hora_agendamento' => trim((string) ($_POST['hora_agendamento'] ?? '')),
        'motivo' => trim((string) ($_POST['motivo'] ?? '')),
        'observacoes' => trim((string) ($_POST['observacoes'] ?? '')),
        'pet_id' => trim((string) ($_POST['pet_id'] ?? '')),
    ];
    $action = $postedAction;
    $id = $postedId;
    if (!validDate($form['data_agendamento'])) {
        $errors[] = 'Informe uma data válida para o atendimento.';
    }
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $form['hora_agendamento'])) {
        $errors[] = 'Informe um horário válido.';
    }
    if ($form['motivo'] === '' || strlen($form['motivo']) > 1020) {
        $errors[] = 'Informe o motivo do atendimento (até 255 caracteres).';
    }
    if (strlen($form['observacoes']) > 20000) {
        $errors[] = 'As observações devem ter até 5.000 caracteres.';
    }
    $petId = filter_var($form['pet_id'], FILTER_VALIDATE_INT);
    if ($petId === false || $petId < 1) {
        $errors[] = 'Selecione um pet para o atendimento.';
    }
    if ($action === 'update' && $id < 1) {
        $errors[] = 'Agendamento inválido.';
    }
    if ($errors === []) {
        try {
            $values = [
                'data_agendamento' => $form['data_agendamento'],
                'hora_agendamento' => $form['hora_agendamento'],
                'motivo' => $form['motivo'],
                'observacoes' => $form['observacoes'] ?: null,
                'pet_id' => $petId,
            ];
            if ($action === 'update') {
                $values['id'] = $id;
                $statement = $db->prepare("UPDATE agendamentos SET data_agendamento = :data_agendamento, hora_agendamento = :hora_agendamento, motivo = :motivo, observacoes = :observacoes, pet_id = :pet_id WHERE id = :id AND status = 'agendado'");
                $statement->execute($values);
                $updated = $statement->rowCount() > 0;
                flash($updated ? 'success' : 'error', $updated ? 'Agendamento atualizado.' : 'Agendamento não encontrado ou já concluído.');
            } else {
                $statement = $db->prepare('INSERT INTO agendamentos (data_agendamento, hora_agendamento, motivo, observacoes, pet_id) VALUES (:data_agendamento, :hora_agendamento, :motivo, :observacoes, :pet_id)');
                $statement->execute($values);
                flash('success', 'Atendimento agendado.');
            }
            redirect('agendamentos.php');
        } catch (PDOException $error) {
            dbFailure($error, 'Não foi possível salvar o agendamento. Confira a data e o pet selecionado.');
        }
    }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action === 'edit' && $id > 0) {
    $statement = $db->prepare("SELECT id, data_agendamento, hora_agendamento, motivo, observacoes, pet_id FROM agendamentos WHERE id = :id AND status = 'agendado'");
    $statement->execute(['id' => $id]);
    $appointment = $statement->fetch();
    if ($appointment === false) {
        flash('error', 'Agendamento não encontrado ou já concluído.');
        redirect('agendamentos.php');
    }
    $form = [
        'data_agendamento' => $appointment['data_agendamento'],
        'hora_agendamento' => substr((string) $appointment['hora_agendamento'], 0, 5),
        'motivo' => $appointment['motivo'],
        'observacoes' => $appointment['observacoes'] ?? '',
        'pet_id' => (string) $appointment['pet_id'],
    ];
}

if ($action === 'complete') {
    if ($id < 1) {
        flash('error', 'Atendimento inválido.');
        redirect('agendamentos.php');
    }
    $statement = $db->prepare(
        'SELECT a.id, a.data_agendamento, a.hora_agendamento, a.motivo, a.status,
                p.nome AS pet_nome, t.nome AS tutor_nome
         FROM agendamentos a
         JOIN pets p ON p.id = a.pet_id
         JOIN tutores t ON t.id = p.tutor_id
         WHERE a.id = :id'
    );
    $statement->execute(['id' => $id]);
    $completionAppointment = $statement->fetch();
    if ($completionAppointment === false) {
        flash('error', 'Atendimento não encontrado.');
        redirect('agendamentos.php');
    }
    if ($completionAppointment['status'] !== 'agendado') {
        flash('warning', 'Este atendimento já foi concluído.');
        redirect('agendamentos.php');
    }
}

$pets = $db->query('SELECT p.id, p.nome, p.especie, t.nome AS tutor_nome FROM pets p JOIN tutores t ON t.id = p.tutor_id ORDER BY p.nome')->fetchAll();
$search = trim((string) ($_GET['q'] ?? ''));
$appointments = (new Agendamento($db))->search($search);

renderHeader('Agendamentos', 'agendamentos');
?>
<section class="page-heading"><div><span class="eyebrow">ROTINA DE ATENDIMENTOS</span><h1>Agendamentos</h1><p class="muted">Acompanhe a agenda e registre os atendimentos concluídos.</p></div><a class="button button-primary" href="agendamentos.php?action=create"><span aria-hidden="true">+</span> Novo agendamento</a></section>
<?php if ($errors !== []): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if ($completionAppointment !== null): ?>
<section class="content-card form-card completion-card">
    <div class="section-heading"><div><span class="eyebrow">REGISTRO DO ATENDIMENTO</span><h2>Finalizar atendimento</h2></div><a class="text-link" href="agendamentos.php">Voltar à agenda</a></div>
    <p class="completion-context"><strong><?= e($completionAppointment['pet_nome']) ?></strong> · <?= e($completionAppointment['tutor_nome']) ?> · <?= e(date('d/m/Y', strtotime($completionAppointment['data_agendamento']))) ?> às <?= e(substr((string) $completionAppointment['hora_agendamento'], 0, 5)) ?><br><?= e($completionAppointment['motivo']) ?></p>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="id" value="<?= (int) $completionAppointment['id'] ?>">
        <label class="field-label field-full">Resumo ou procedimento realizado
            <textarea class="input textarea" name="resumo_conclusao" rows="5" maxlength="5000" required><?= e($completionSummary) ?></textarea>
        </label>
        <div class="form-actions"><button class="button button-primary" type="submit">Finalizar atendimento</button><a class="button button-secondary" href="agendamentos.php">Cancelar</a></div>
    </form>
</section>
<?php endif; ?>
<?php if (in_array($action, ['create', 'edit', 'update'], true)): ?>
<section class="content-card form-card"><div class="section-heading"><div><span class="eyebrow">DETALHES DO ATENDIMENTO</span><h2><?= $action === 'edit' || $action === 'update' ? 'Editar agendamento' : 'Novo agendamento' ?></h2></div><a class="text-link" href="agendamentos.php">Cancelar</a></div>
    <?php if ($pets === []): ?><div class="alert alert-warning">Cadastre um pet antes de agendar um atendimento. <a class="text-link" href="pets.php?action=create">Cadastrar pet →</a></div><?php else: ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="<?= $action === 'edit' || $action === 'update' ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= (int) $id ?>">
        <label class="field-label">Pet e tutor<select class="input" name="pet_id" required><option value="">Selecione o pet</option><?php foreach ($pets as $pet): ?><option value="<?= (int) $pet['id'] ?>" <?= (string) $pet['id'] === $form['pet_id'] ? 'selected' : '' ?>><?= e($pet['nome']) ?> · <?= e($pet['especie']) ?> — <?= e($pet['tutor_nome']) ?></option><?php endforeach; ?></select></label>
        <label class="field-label">Motivo<input class="input" name="motivo" maxlength="255" value="<?= e($form['motivo']) ?>" placeholder="Ex.: consulta de rotina" required></label>
        <label class="field-label">Data<input class="input" type="date" name="data_agendamento" value="<?= e($form['data_agendamento']) ?>" required></label>
        <label class="field-label">Horário<input class="input" type="time" name="hora_agendamento" value="<?= e($form['hora_agendamento']) ?>" required></label>
        <label class="field-label field-full">Observações<textarea class="input textarea" name="observacoes" rows="3" maxlength="5000"><?= e($form['observacoes']) ?></textarea></label>
        <div class="form-actions"><button class="button button-primary" type="submit">Salvar agendamento</button><a class="button button-secondary" href="agendamentos.php">Cancelar</a></div>
    </form><?php endif; ?>
</section>
<?php endif; ?>
<section class="content-card"><form method="get" class="search-form"><label class="visually-hidden" for="q">Buscar agendamento</label><span class="search-icon" aria-hidden="true">⌕</span><input class="input" id="q" name="q" value="<?= e($search) ?>" placeholder="Buscar por pet, tutor, motivo ou resumo"><button class="button button-secondary" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="button button-quiet" href="agendamentos.php">Limpar</a><?php endif; ?></form>
    <div class="table-wrap"><table><thead><tr><th>Data e horário</th><th>Pet</th><th>Tutor</th><th>Atendimento</th><th>Situação</th><th class="table-actions">Ações</th></tr></thead><tbody>
    <?php foreach ($appointments as $appointment): ?><tr><td><strong><?= e(date('d/m/Y', strtotime($appointment['data_agendamento']))) ?></strong><span class="table-sub"><?= e(substr((string) $appointment['hora_agendamento'], 0, 5)) ?></span></td><td><strong><?= e($appointment['pet_nome']) ?></strong><span class="table-sub"><?= e($appointment['especie']) ?><?= $appointment['raca'] ? ' · ' . e($appointment['raca']) : '' ?></span></td><td><strong><?= e($appointment['tutor_nome']) ?></strong><span class="table-sub"><?= e($appointment['telefone'] ?: $appointment['email'] ?: 'Contato não informado') ?></span></td><td><strong><?= e($appointment['motivo']) ?></strong><span class="table-sub"><?= e($appointment['observacoes'] ?: 'Sem observações') ?></span></td><td><span class="appointment-status <?= $appointment['status'] === 'concluido' ? 'status-completed' : 'status-scheduled' ?>"><?= $appointment['status'] === 'concluido' ? 'Concluído' : 'Agendado' ?></span><?php if ($appointment['status'] === 'concluido'): ?><span class="table-sub">Em <?= e(date('d/m/Y H:i', strtotime($appointment['concluido_em']))) ?></span><span class="completion-summary"><?= e($appointment['resumo_conclusao']) ?></span><?php endif; ?></td><td class="table-actions"><?php if ($appointment['status'] === 'agendado'): ?><a class="button button-quiet button-small" href="agendamentos.php?action=edit&id=<?= (int) $appointment['id'] ?>">Editar</a><a class="button button-complete button-small" href="agendamentos.php?action=complete&id=<?= (int) $appointment['id'] ?>">Concluir</a><form method="post" class="inline-form" data-confirm="Cancelar este agendamento?"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>"><button class="button button-danger-quiet button-small" type="submit">Cancelar</button></form><?php else: ?><span class="table-sub">Atendimento encerrado</span><?php endif; ?></td></tr><?php endforeach; ?>
    <?php if ($appointments === []): ?><tr><td colspan="6"><div class="empty-state compact"><span class="empty-icon">◷</span><h3><?= $search !== '' ? 'Nenhum resultado encontrado' : 'Agenda vazia' ?></h3><p><?= $search !== '' ? 'Tente buscar por outro termo.' : 'Os atendimentos aparecerão aqui em ordem cronológica.' ?></p></div></td></tr><?php endif; ?>
    </tbody></table></div><div class="table-footer"><?= count($appointments) ?> <?= count($appointments) === 1 ? 'agendamento' : 'agendamentos' ?><?= $search !== '' ? ' encontrados' : ' cadastrados' ?></div>
</section>
<?php renderFooter(); ?>
<?php
    }
}
