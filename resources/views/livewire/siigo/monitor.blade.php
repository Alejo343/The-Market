<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SiigoSyncLog;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Artisan;

new class extends Component {
    use WithPagination;

    public string $filterStatus   = '';
    public string $filterType     = '';
    public string $filterDate     = '';
    public ?int   $expandedLog    = null;
    public bool   $syncing        = false;

    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterDate(): void   { $this->resetPage(); }

    public function toggleExpand(int $id): void
    {
        $this->expandedLog = $this->expandedLog === $id ? null : $id;
    }

    public function syncNow(): void
    {
        $this->syncing = true;
        Artisan::call('siigo:sync-updated');
        $this->syncing = false;
        $this->resetPage();
    }

    public function with(): array
    {
        $query = SiigoSyncLog::query()->latest();

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterType) {
            $query->where('event_type', $this->filterType);
        }
        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        $logs = $query->paginate(20);

        $totalToday  = SiigoSyncLog::today()->count();
        $errorsToday = SiigoSyncLog::today()->errors()->count();
        $lastSuccess = SiigoSyncLog::where('status', 'success')->latest()->value('created_at');
        $synced      = ProductVariant::whereNotNull('siigo_id')->count();

        return compact('logs', 'totalToday', 'errorsToday', 'lastSuccess', 'synced');
    }
}; ?>

<div class="px-4 py-8" wire:poll.30s>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Monitor Siigo</h1>
            <p class="text-gray-500 text-sm">Sincronización de productos en tiempo real</p>
        </div>
        <button
            wire:click="syncNow"
            wire:loading.attr="disabled"
            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-50"
        >
            <svg wire:loading.remove wire:target="syncNow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg wire:loading wire:target="syncNow" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            Sincronizar ahora
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-gray-500 text-xs uppercase font-medium">Syncs hoy</p>
            <p class="text-2xl font-bold text-blue-600">{{ $totalToday }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ $errorsToday > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-gray-500 text-xs uppercase font-medium">Errores hoy</p>
            <p class="text-2xl font-bold {{ $errorsToday > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $errorsToday }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-500 text-xs uppercase font-medium">Último éxito</p>
            <p class="text-sm font-semibold text-gray-700 mt-1">
                {{ $lastSuccess ? \Carbon\Carbon::parse($lastSuccess)->diffForHumans() : 'Sin registros' }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <p class="text-gray-500 text-xs uppercase font-medium">Productos sincronizados</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $synced }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Estado</label>
            <select wire:model.live="filterStatus" class="text-sm border border-gray-300 rounded px-3 py-1.5">
                <option value="">Todos</option>
                <option value="success">Éxito</option>
                <option value="error">Error</option>
                <option value="skipped">Omitido</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tipo</label>
            <select wire:model.live="filterType" class="text-sm border border-gray-300 rounded px-3 py-1.5">
                <option value="">Todos</option>
                <option value="import">Importación</option>
                <option value="webhook">Webhook</option>
                <option value="polling">Polling</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Fecha</label>
            <input wire:model.live="filterDate" type="date" class="text-sm border border-gray-300 rounded px-3 py-1.5">
        </div>
        @if($filterStatus || $filterType || $filterDate)
            <button wire:click="$set('filterStatus', ''); $set('filterType', ''); $set('filterDate', '')" class="text-xs text-gray-500 hover:text-red-500 mt-4">
                Limpiar filtros
            </button>
        @endif
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Topic</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensaje</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 {{ $log->status === 'error' ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $log->created_at->format('d/m H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $log->event_type === 'webhook' ? 'bg-purple-100 text-purple-700' :
                                   ($log->event_type === 'import' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($log->event_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs max-w-[160px] truncate" title="{{ $log->topic }}">
                            {{ $log->topic ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $log->siigo_code ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $log->status === 'success' ? 'bg-green-100 text-green-700' :
                                   ($log->status === 'error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ match($log->status) { 'success' => 'Éxito', 'error' => 'Error', default => 'Omitido' } }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-[260px] truncate" title="{{ $log->message }}">
                            {{ $log->message }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($log->payload)
                                <button wire:click="toggleExpand({{ $log->id }})" class="text-xs text-blue-500 hover:underline">
                                    {{ $expandedLog === $log->id ? 'Ocultar' : 'Ver payload' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if($expandedLog === $log->id && $log->payload)
                        <tr class="bg-gray-900">
                            <td colspan="7" class="px-4 py-3">
                                <pre class="text-xs text-green-300 overflow-x-auto max-h-60">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                            No hay registros con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
