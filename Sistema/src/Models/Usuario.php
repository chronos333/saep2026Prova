<?php
declare(strict_types=1);

namespace VetCare\Models;

use PDO;

final class Usuario
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, nome, email, senha, perfil FROM usuarios WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
