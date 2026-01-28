<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService
{
    /**
     * Lista usuarios con filtros opcionales
     */
    public function list(
        ?string $role = null,
        ?bool $activeOnly = null,
        ?string $search = null,
        ?array $include = null
    ): Collection {
        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        if ($activeOnly) {
            $query->where('active', true);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Obtiene todos los usuarios
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene usuarios activos
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Obtiene usuarios por rol
     */
    public function getByRole(string $role): Collection
    {
        return $this->list(role: $role);
    }

    /**
     * Busca usuarios por nombre o email
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea un nuevo usuario
     */
    public function create(array $data): User
    {
        // Hash de la contraseña
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Establecer activo por defecto
        if (!isset($data['active'])) {
            $data['active'] = true;
        }

        $user = User::create($data);

        return $user;
    }

    /**
     * Obtiene un usuario específico
     */
    public function show(User $user, ?array $include = null): User
    {
        if ($include) {
            $user->load($include);
        }

        return $user;
    }

    /**
     * Actualiza un usuario
     */
    public function update(User $user, array $data): User
    {
        // Hash de la contraseña si se proporciona
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user;
    }

    /**
     * Elimina un usuario
     */
    public function delete(User $user): void
    {
        if ($user->sales()->exists()) {
            throw new Exception('USER_HAS_SALES');
        }

        $user->delete();
    }

    /**
     * Activa un usuario
     */
    public function activate(User $user): User
    {
        $user->update(['active' => true]);

        return $user;
    }

    /**
     * Desactiva un usuario
     */
    public function deactivate(User $user): User
    {
        $user->update(['active' => false]);

        return $user;
    }
}
