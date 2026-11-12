<?php
declare(strict_types=1);

namespace VetCare\Models;

use PDO;

final class Pet
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function search(string $term): array
    {
        $statement = $this->database->prepare(
            'SELECT p.id, p.nome, p.especie, p.raca, p.data_nascimento, t.id AS tutor_id, t.nome AS tutor_nome, t.telefone,
                    u.email AS cliente_email
             FROM pets p JOIN tutores t ON t.id = p.tutor_id
             LEFT JOIN usuarios u ON u.id = t.usuario_id
             WHERE p.nome ILIKE :pet_name OR p.especie ILIKE :species OR p.raca ILIKE :breed OR t.nome ILIKE :tutor_name OR u.email ILIKE :client_email
             ORDER BY p.nome'
        );
        $pattern = '%' . $term . '%';
        $statement->execute([
            'pet_name' => $pattern,
            'species' => $pattern,
            'breed' => $pattern,
            'tutor_name' => $pattern,
            'client_email' => $pattern,
        ]);

        return $statement->fetchAll();
    }
}
