<?php
declare(strict_types=1);

namespace VetCare\Controllers;

use PDOException;

final class HomeController
{
    public function handle(): void
    {
require_once __DIR__ . '/../Core/bootstrap.php';
requireStaff();

try {
    $db = getDB();
    $counts = [
        'clientes' => (int) $db->query('SELECT COUNT(*) FROM tutores')->fetchColumn(),
        'pets' => (int) $db->query('SELECT COUNT(*) FROM pets')->fetchColumn(),
        'agendamentos' => (int) $db->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn(),
    ];
    $upcoming = $db->query(
        'SELECT a.id, a.data_agendamento, a.hora_agendamento, a.motivo, p.nome AS pet_nome, p.especie, t.nome AS tutor_nome
         FROM agendamentos a
         JOIN pets p ON p.id = a.pet_id
         JOIN tutores t ON t.id = p.tutor_id
         WHERE a.data_agendamento >= CURRENT_DATE AND a.status = \'agendado\'
         ORDER BY a.data_agendamento, a.hora_agendamento
         LIMIT 5'
    )->fetchAll();
} catch (PDOException $error) {
    error_log((string) $error);
    http_response_code(503);
    $counts = ['clientes' => 0, 'pets' => 0, 'agendamentos' => 0];
    $upcoming = [];
    flash('error', 'Não foi possível carregar os dados. Confira a conexão e o script do banco.');
}

renderHeader('Visão geral', 'dashboard');
?>
<section class="admin-hero">
    <div class="admin-hero-copy">
        <span class="eyebrow eyebrow-on-dark">CLUBE DAS PATAS · ÁREA DA EQUIPE</span>
        <h1>Um bom dia começa<br>com um pet feliz.</h1>
        <p>Olá, <?= e(explode(' ', trim((string) $_SESSION['user_name']))[0]) ?>! A clínica está cheia de histórias para cuidar.</p>
        <a class="button button-light" href="agendamentos.php?action=create"><span aria-hidden="true">＋</span> Novo atendimento</a>
    </div>
    <div class="admin-hero-art" aria-hidden="true">
        <span class="admin-hero-sun"></span>
        <span class="admin-hero-pet">🐕</span>
        <span class="admin-hero-heart">♡</span>
        <span class="admin-hero-spark">✦</span>
    </div>
</section>
<section class="stats-grid" aria-label="Resumo da clínica">
    <a class="stat-card stat-card-lavender" href="tutores.php"><span class="stat-icon">♡</span><span class="stat-label">Famílias cadastradas</span><strong><?= $counts['clientes'] ?></strong><span class="stat-link">Conhecer clientes <span aria-hidden="true">→</span></span></a>
    <a class="stat-card stat-card-mint" href="pets.php"><span class="stat-icon">🐾</span><span class="stat-label">Amigos no clube</span><strong><?= $counts['pets'] ?></strong><span class="stat-link">Ver pets <span aria-hidden="true">→</span></span></a>
    <a class="stat-card stat-card-peach" href="agendamentos.php"><span class="stat-icon">◷</span><span class="stat-label">Atendimentos na agenda</span><strong><?= $counts['agendamentos'] ?></strong><span class="stat-link">Ver agenda <span aria-hidden="true">→</span></span></a>
</section>
<section class="content-card admin-agenda">
    <div class="section-heading"><div><span class="eyebrow">QUEM VEM HOJE E NOS PRÓXIMOS DIAS</span><h2>Agenda de atendimentos</h2></div><a class="text-link" href="agendamentos.php">Ver agenda completa <span aria-hidden="true">→</span></a></div>
    <?php if ($upcoming === []): ?>
        <div class="empty-state"><span class="empty-icon">◷</span><h3>Nenhum atendimento próximo</h3><p>Quando houver consultas agendadas, elas aparecerão aqui.</p><a class="button button-secondary" href="agendamentos.php?action=create">Agendar atendimento</a></div>
    <?php else: ?>
        <div class="appointment-list">
            <?php foreach ($upcoming as $appointment): ?>
                <article class="appointment-row">
                    <div class="appointment-date"><strong><?= e(date('d', strtotime($appointment['data_agendamento']))) ?></strong><span><?php $months = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ']; ?><?= e($months[(int) date('n', strtotime($appointment['data_agendamento']))]) ?></span></div>
                    <div class="appointment-info"><strong><?= e($appointment['pet_nome']) ?> <span class="pet-species"><?= e($appointment['especie']) ?></span></strong><span><?= e($appointment['motivo']) ?> · tutor(a) <?= e($appointment['tutor_nome']) ?></span></div>
                    <time class="appointment-time" datetime="<?= e($appointment['data_agendamento'] . 'T' . $appointment['hora_agendamento']) ?>"><?= e(substr((string) $appointment['hora_agendamento'], 0, 5)) ?></time>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
<?php
    }
}
