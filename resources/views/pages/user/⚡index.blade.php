<?php
use Livewire\Component;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public string $search = '';
    public string $filterRole = '';
    public string $filterStatus = '';

    // Edición inline
    public ?int $editingId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editRole = 'cashier';
    public string $editPassword = '';

    // Modal de creación
    public bool $showCreateModal = false;
    public string $newName = '';
    public string $newEmail = '';
    public string $newPassword = '';
    public string $newRole = 'cashier';

    // Eliminación
    public ?int $deletingId = null;
    public string $deletingName = '';

    public string $errorMessage = '';
    public string $successMessage = '';

    public function with(UserService $userService): array
    {
        $role = $this->filterRole ?: null;
        $activeOnly = match ($this->filterStatus) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $users = $userService->list(
            role: $role,
            activeOnly: $activeOnly,
            search: $this->search ?: null,
        );

        // Si filtramos inactivos, list() con activeOnly=false no funciona (el servicio filtra true)
        // Hacemos el filtro manual para inactivos
        if ($this->filterStatus === 'inactive') {
            $users = User::query()
                ->when($role, fn($q) => $q->where('role', $role))
                ->where('active', false)
                ->when($this->search, fn($q) => $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                       ->orWhere('email', 'like', "%{$this->search}%");
                }))
                ->orderBy('name')
                ->get();
        }

        return ['users' => $users];
    }

    public function updatedSearch() { $this->resetMessages(); }
    public function updatedFilterRole() { $this->resetMessages(); }
    public function updatedFilterStatus() { $this->resetMessages(); }

    // ── Creación ─────────────────────────────────────────────────────────────

    public function openCreate()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
        $this->resetMessages();
    }

    public function closeCreate()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function create(UserService $userService)
    {
        $this->validate([
            'newName'     => 'required|string|max:255',
            'newEmail'    => 'required|email|max:255|unique:users,email',
            'newPassword' => 'required|string|min:8',
            'newRole'     => 'required|in:admin,cashier',
        ], [
            'newName.required'     => 'El nombre es obligatorio',
            'newEmail.required'    => 'El email es obligatorio',
            'newEmail.email'       => 'El email no es válido',
            'newEmail.unique'      => 'Ya existe un usuario con ese email',
            'newPassword.required' => 'La contraseña es obligatoria',
            'newPassword.min'      => 'La contraseña debe tener al menos 8 caracteres',
            'newRole.required'     => 'El rol es obligatorio',
        ]);

        try {
            $userService->create([
                'name'     => $this->newName,
                'email'    => $this->newEmail,
                'password' => $this->newPassword,
                'role'     => $this->newRole,
            ]);

            $this->successMessage = "Usuario \"{$this->newName}\" creado exitosamente";
            $this->closeCreate();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al crear el usuario: ' . $e->getMessage();
        }
    }

    // ── Edición ───────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $this->resetMessages();
        $user = User::findOrFail($id);
        $this->editingId  = $id;
        $this->editName   = $user->name;
        $this->editEmail  = $user->email;
        $this->editRole   = $user->role;
        $this->editPassword = '';
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editName = $this->editEmail = $this->editRole = $this->editPassword = '';
    }

    public function save(UserService $userService)
    {
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editEmail' => "required|email|max:255|unique:users,email,{$this->editingId}",
            'editRole'  => 'required|in:admin,cashier',
            'editPassword' => 'nullable|string|min:8',
        ], [
            'editName.required'  => 'El nombre es obligatorio',
            'editEmail.required' => 'El email es obligatorio',
            'editEmail.email'    => 'El email no es válido',
            'editEmail.unique'   => 'Ya existe un usuario con ese email',
            'editRole.required'  => 'El rol es obligatorio',
            'editPassword.min'   => 'La contraseña debe tener al menos 8 caracteres',
        ]);

        try {
            $user = User::findOrFail($this->editingId);
            $data = [
                'name'  => $this->editName,
                'email' => $this->editEmail,
                'role'  => $this->editRole,
            ];
            if ($this->editPassword !== '') {
                $data['password'] = $this->editPassword;
            }
            $userService->update($user, $data);
            $this->successMessage = 'Usuario actualizado exitosamente';
            $this->cancelEdit();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al actualizar: ' . $e->getMessage();
        }
    }

    // ── Activar / Desactivar ──────────────────────────────────────────────────

    public function toggleActive(int $id, UserService $userService)
    {
        $this->resetMessages();

        if ($id === auth()->id()) {
            $this->errorMessage = 'No puedes desactivar tu propia cuenta';
            return;
        }

        $user = User::findOrFail($id);
        if ($user->active) {
            $userService->deactivate($user);
            $this->successMessage = "Usuario \"{$user->name}\" desactivado";
        } else {
            $userService->activate($user);
            $this->successMessage = "Usuario \"{$user->name}\" activado";
        }
    }

    // ── Eliminación ───────────────────────────────────────────────────────────

    public function confirmDelete(int $id)
    {
        $this->resetMessages();
        $user = User::findOrFail($id);

        if ($id === auth()->id()) {
            $this->errorMessage = 'No puedes eliminar tu propia cuenta';
            return;
        }

        $this->deletingId   = $id;
        $this->deletingName = $user->name;
    }

    public function cancelDelete()
    {
        $this->deletingId   = null;
        $this->deletingName = '';
    }

    public function delete(UserService $userService)
    {
        $this->resetMessages();
        try {
            $user = User::findOrFail($this->deletingId);
            $userService->delete($user);
            $this->successMessage = "Usuario \"{$this->deletingName}\" eliminado";
            $this->cancelDelete();
        } catch (\Exception $e) {
            $this->errorMessage = match ($e->getMessage()) {
                'USER_HAS_SALES' => 'No se puede eliminar: el usuario tiene ventas registradas',
                default          => 'Error al eliminar: ' . $e->getMessage(),
            };
            $this->cancelDelete();
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetMessages()
    {
        $this->errorMessage = $this->successMessage = '';
    }

    private function resetCreateForm()
    {
        $this->newName = $this->newEmail = $this->newPassword = '';
        $this->newRole = 'cashier';
        $this->resetValidation();
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Usuarios y Permisos</h1>
        <p class="text-gray-600">Gestiona los usuarios del sistema y sus roles</p>
    </div>

    {{-- Mensajes --}}
    @if ($successMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', '')" class="text-green-700 hover:text-green-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', '')" class="text-red-700 hover:text-red-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Filtros y acciones --}}
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            {{-- Búsqueda --}}
            <div class="flex-1 max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre o email..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Filtro rol --}}
            <select wire:model.live="filterRole"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos los roles</option>
                <option value="admin">Administrador</option>
                <option value="cashier">Cajero</option>
            </select>

            {{-- Filtro estado --}}
            <select wire:model.live="filterStatus"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>

        <button wire:click="openCreate"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Usuario
        </button>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors" wire:key="user-{{ $user->id }}">

                        {{-- Nombre / Email --}}
                        <td class="px-6 py-4">
                            @if ($editingId === $user->id)
                                <div class="space-y-2">
                                    <input type="text" wire:model="editName" placeholder="Nombre"
                                        class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    @error('editName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                                    <input type="email" wire:model="editEmail" placeholder="Email"
                                        class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    @error('editEmail') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                                    <input type="password" wire:model="editPassword" placeholder="Nueva contraseña (opcional)"
                                        class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    @error('editPassword') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            @else
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 flex items-center gap-1">
                                            {{ $user->name }}
                                            @if ($user->id === auth()->id())
                                                <span class="text-xs text-gray-400">(tú)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            @endif
                        </td>

                        {{-- Rol --}}
                        <td class="px-6 py-4">
                            @if ($editingId === $user->id)
                                <select wire:model="editRole"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="admin">Administrador</option>
                                    <option value="cashier">Cajero</option>
                                </select>
                                @error('editRole') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @else
                                @if ($user->role === 'admin')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Administrador
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Cajero
                                    </span>
                                @endif
                            @endif
                        </td>

                        {{-- Estado --}}
                        <td class="px-6 py-4">
                            @if ($user->active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        {{-- Fecha --}}
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>

                        {{-- Acciones --}}
                        <td class="px-6 py-4 text-right">
                            @if ($editingId === $user->id)
                                <div class="flex justify-end gap-2">
                                    <button wire:click="save"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                        Guardar
                                    </button>
                                    <button wire:click="cancelEdit"
                                        class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            @else
                                <div class="flex justify-end gap-1">
                                    {{-- Editar --}}
                                    <button wire:click="edit({{ $user->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    {{-- Activar / Desactivar --}}
                                    <button wire:click="toggleActive({{ $user->id }})"
                                        class="p-2 rounded transition-colors {{ $user->active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50' }}"
                                        title="{{ $user->active ? 'Desactivar' : 'Activar' }}">
                                        @if ($user->active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>

                                    {{-- Eliminar --}}
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="confirmDelete({{ $user->id }})"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                                            title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            @if ($search || $filterRole || $filterStatus)
                                No se encontraron usuarios con los filtros aplicados
                            @else
                                No hay usuarios registrados
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Resumen --}}
    <div class="mt-4 text-sm text-gray-500">
        {{ $users->count() }} {{ $users->count() === 1 ? 'usuario' : 'usuarios' }} encontrados
    </div>

    {{-- Modal: Crear usuario --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Nuevo Usuario</h3>
                    <button wire:click="closeCreate" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" wire:model="newName" placeholder="Nombre completo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('newName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" wire:model="newEmail" placeholder="usuario@ejemplo.com"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('newEmail') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" wire:model="newPassword" placeholder="Mínimo 8 caracteres"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('newPassword') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select wire:model="newRole"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="cashier">Cajero</option>
                            <option value="admin">Administrador</option>
                        </select>
                        @error('newRole') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="closeCreate"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="create"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Crear Usuario
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Confirmar eliminación --}}
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar al usuario <strong>{{ $deletingName }}</strong>?
                    Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
