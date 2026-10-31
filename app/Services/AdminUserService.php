<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public const DEFAULT_TEMP_PASSWORD = '12345678';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function listUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    /**
     * @param array{name: string, email: string, password?: ?string, role?: ?string, is_admin?: ?bool} $data
     */
    public function createUser(array $data): User
    {
        $role = isset($data['role']) && in_array($data['role'], ['admin', 'user'], true)
            ? $data['role']
            : (! empty($data['is_admin']) ? 'admin' : 'user');

        $isAdmin = $role === 'admin' || ! empty($data['is_admin']);
        $rawPassword = isset($data['password']) && trim((string) $data['password']) !== ''
            ? trim((string) $data['password'])
            : self::DEFAULT_TEMP_PASSWORD;

        return $this->userRepository->create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($rawPassword),
            'role' => $isAdmin ? 'admin' : 'user',
            'is_admin' => $isAdmin,
            'rule_needs_pct' => 50,
            'rule_wants_pct' => 30,
            'rule_investments_pct' => 20,
        ]);
    }

    /**
     * Redefine a senha do usuário para a senha padrão (12345678).
     */
    public function resetPassword(User $user, string $defaultPassword = self::DEFAULT_TEMP_PASSWORD): string
    {
        $this->userRepository->update($user, [
            'password' => Hash::make($defaultPassword),
        ]);

        return $defaultPassword;
    }
}
