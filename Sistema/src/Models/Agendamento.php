<?php
declare(strict_types=1);

namespace VetCare\Models;

use PDO;

final class Agendamento
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function search(string $term): array
    {
        $statement = $this->database->prepare(
            'SELECT a.id, a.data_agendamento, a.hora_agendamento, a.motivo, a.observacoes,
                    a.status, a.resumo_conclusao, a.concluido_em,
                    p.id AS pet_id, p.nome AS pet_nome, p.especie, p.raca,
                    t.id AS tutor_id, t.nome AS tutor_nome, t.telefone, t.email
             FROM agendamentos a
             JOIN pets p ON p.id = a.pet_id
             JOIN tutores t ON t.id = p.tutor_id
             WHERE p.nome ILIKE :pet_name OR t.nome ILIKE :tutor_name
                OR a.motivo ILIKE :reason OR a.resumo_conclusao ILIKE :summary
             ORDER BY a.data_agendamento, a.hora_agendamento'
        );
        $pattern = '%' . $term . '%';
        $statement->execute([
            'pet_name' => $pattern,
            'tutor_name' => $pattern,
            'reason' => $pattern,
            'summary' => $pattern,
        ]);

        return $statement->fetchAll();
    }
}
