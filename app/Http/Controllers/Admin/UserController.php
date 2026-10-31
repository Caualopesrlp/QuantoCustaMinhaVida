<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {}

    public function index(): View
    {
        $users = $this->adminUserService->listUsers();

        return view('admin.users.index', [
            'users' => $users,
            'defaultPassword' => AdminUserService::DEFAULT_TEMP_PASSWORD,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $usedDefaultPassword = empty(trim((string) ($validated['password'] ?? '')));

        $user = $this->adminUserService->createUser($validated);

        $passwordInfo = $usedDefaultPassword
            ? ' (Senha inicial padrão: ' . AdminUserService::DEFAULT_TEMP_PASSWORD . ')'
            : '';

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuário {$user->name} ({$user->email}) cadastrado com sucesso.{$passwordInfo}");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $tempPassword = $this->adminUserService->resetPassword($user);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Senha de {$user->name} ({$user->email}) redefinida para a senha padrão: {$tempPassword}");
    }
}
