<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function getAll(): Collection;

    public function findById(int $userId): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): User;

    /**
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User;

    public function delete(User $user): bool;
}
