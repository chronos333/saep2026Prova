<?php
declare(strict_types=1);

namespace VetCare\Models;

use PDO;

final class Tutor
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function search(string $term): array
    {
        $sql = 'SELECT t.id, t.nome, t.cpf, t.telefone, t.email, t.endereco, COUNT(p.id) AS pets_total
                FROM tutores t LEFT JOIN pets p ON p.tutor_id = t.id';
        $parameters = [];
        if ($term !== '') {
            $sql .= ' WHERE t.nome ILIKE :search OR COALESCE(t.email, \'\') ILIKE :search OR COALESCE(t.telefone, \'\') ILIKE :search';
            $parameters['search'] = '%' . $term . '%';
            $digits = \cpfDigits($term);
            if (preg_match('/^\d{11}$/', $digits)) {
                $sql .= ' OR t.cpf_hash = :cpf_hash';
                $parameters['cpf_hash'] = \cpfSearchHash($digits);
            }
        }
        $sql .= ' GROUP BY t.id ORDER BY t.nome';
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
