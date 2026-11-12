<section class="portal-hero">
    <div class="portal-hero-copy">
        <span class="eyebrow eyebrow-on-dark">SEU CANTINHO PET</span>
        <h1>Oi, <?= e(explode(' ', trim((string) $_SESSION['user_name']))[0]) ?>! Que bom ter você aqui <span aria-hidden="true">🐾</span></h1>
        <p>Um lugar especial para cuidar de quem faz sua vida mais feliz.</p>
        <a class="button button-light" href="#pets">Conhecer meus pets <span aria-hidden="true">↓</span></a>
    </div>
    <div class="portal-illustration" aria-hidden="true">
        <span class="illustration-sun"></span>
        <span class="illustration-heart">♡</span>
        <span class="illustration-dog">🐶</span>
        <span class="illustration-spark illustration-spark-one">✦</span>
        <span class="illustration-spark illustration-spark-two">✦</span>
    </div>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error" role="alert">
        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<section id="pets" class="content-card portal-section">
    <div class="section-heading">
        <div><span class="eyebrow">MEU BANDO</span><h2>Os pets da família</h2></div>
        <a class="button button-primary" href="#novo-pet"><span aria-hidden="true">+</span> Cadastrar pet</a>
    </div>
    <div class="pet-grid">
        <?php foreach ($pets as $pet): ?>
            <article class="pet-card portal-pet-card">
                <div class="pet-card-top">
                    <div class="pet-avatar"><?= $pet['especie'] === 'Gato' ? '🐈' : '🐕' ?></div>
                    <span class="species-tag"><?= e($pet['especie']) ?></span>
                </div>
                <h3><?= e($pet['nome']) ?></h3>
                <p class="pet-breed"><?= e($pet['raca'] ?: 'Raça não informada') ?><?= $pet['data_nascimento'] ? ' · ' . e(date('d/m/Y', strtotime($pet['data_nascimento']))) : '' ?></p>
                <div class="portal-pet-love"><span aria-hidden="true">♡</span> Parte da família, parte do clube!</div>
            </article>
        <?php endforeach; ?>
        <?php if ($pets === []): ?>
            <div class="empty-state compact pet-empty">
                <span class="empty-icon">🐾</span><h3>Seu bando está esperando</h3>
                <p>Cadastre seu primeiro pet para acompanhar tudo por aqui.</p>
                <a class="button button-secondary" href="#novo-pet">Cadastrar meu pet</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="novo-pet" class="content-card form-card portal-section">
    <div class="section-heading">
        <div><span class="eyebrow">MAIS UM AMIGO NO CLUBE</span><h2>Adicionar um pet</h2></div>
        <span class="signup-paw" aria-hidden="true">🐾</span>
    </div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="add_pet">
        <label class="field-label">Nome do pet
            <input class="input" name="nome" maxlength="100" value="<?= e($form['nome']) ?>" placeholder="Como ele se chama?" required>
        </label>
        <label class="field-label">Espécie
            <select class="input" name="especie" required><option value="">Selecione</option><?php foreach (['Cachorro', 'Gato', 'Ave', 'Roedor', 'Réptil', 'Outro'] as $species): ?><option value="<?= e($species) ?>" <?= $form['especie'] === $species ? 'selected' : '' ?>><?= e($species) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field-label">Raça<input class="input" name="raca" maxlength="100" value="<?= e($form['raca']) ?>" placeholder="Se souber"></label>
        <label class="field-label">Data de nascimento<input class="input" type="date" name="data_nascimento" value="<?= e($form['data_nascimento']) ?>" max="<?= e(date('Y-m-d')) ?>"></label>
        <div class="form-actions"><button class="button button-primary" type="submit">Salvar meu pet <span aria-hidden="true">♡</span></button></div>
    </form>
</section>

<section id="consultas" class="content-card portal-section">
    <div class="section-heading">
        <div><span class="eyebrow">CARINHO TAMBÉM É CUIDADO</span><h2>Consultas dos meus pets</h2></div>
        <span class="stat-pill"><?= count($appointments) ?> <?= count($appointments) === 1 ? 'consulta' : 'consultas' ?></span>
    </div>
    <?php if ($appointments === []): ?>
        <div class="empty-state compact">
            <span class="empty-icon">♡</span><h3>Um histórico novinho em folha</h3>
            <p>Quando a equipe agendar uma consulta para um dos seus pets, você poderá vê-la aqui.</p>
        </div>
    <?php else: ?>
        <div class="appointment-list">
            <?php foreach ($appointments as $appointment): ?>
                <article class="appointment-row">
                    <div class="appointment-date"><strong><?= e(date('d', strtotime($appointment['data_agendamento']))) ?></strong><span><?php $months = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ']; ?><?= e($months[(int) date('n', strtotime($appointment['data_agendamento']))]) ?></span></div>
                    <div class="appointment-info"><strong><?= e($appointment['pet_nome']) ?> <span class="pet-species"><?= e($appointment['especie']) ?></span> <span class="appointment-status <?= $appointment['status'] === 'concluido' ? 'status-completed' : 'status-scheduled' ?>"><?= $appointment['status'] === 'concluido' ? 'Concluído' : 'Agendado' ?></span></strong><span><?= e($appointment['motivo']) ?><?= $appointment['observacoes'] ? ' · ' . e($appointment['observacoes']) : '' ?><?= $appointment['resumo_conclusao'] ? ' · Resultado: ' . e($appointment['resumo_conclusao']) : '' ?></span></div>
                    <time class="appointment-time" datetime="<?= e($appointment['data_agendamento'] . 'T' . $appointment['hora_agendamento']) ?>"><?= e(substr((string) $appointment['hora_agendamento'], 0, 5)) ?></time>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
