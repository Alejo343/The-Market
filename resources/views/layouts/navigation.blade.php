<aside class="w-64 bg-gray-800 text-white min-h-screen">
    <!-- Logo / Header -->
    <div class="p-6 border-b border-gray-700">
        <h2 class="text-2xl font-bold">Sistema Ventas</h2>
        <p class="text-xs text-gray-400 mt-1">Panel de Administración</p>
    </div>

    <nav class="mt-6 px-3">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>

        <!-- VENTAS -->
        <div class="mt-6">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Ventas</h3>

            <a href="{{ route('sales.create') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('sales.create') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Venta (POS)
            </a>

            <a href="/reports/sales" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('sales.index') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                Historial de Ventas
            </a>
        </div>

        <!-- INVENTARIO -->
        <div class="mt-6">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Inventario</h3>

            <a href="/products" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                Productos
            </a>

            <a href="/product-variants" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('variants.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                    </path>
                </svg>
                Variantes y Precios
            </a>

            <a href="/weight-lots" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('weight-lots.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3">
                    </path>
                </svg>
                Lotes de Carnicería
            </a>

            <a href="{{ route('inventory.movements') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('inventory.movements') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Movimientos
            </a>

            <a href="{{ route('inventory.alerts') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('inventory.alerts') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                Alertas de Stock
            </a>
        </div>

        <!-- CATÁLOGO -->
        <div class="mt-6">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Catálogo</h3>

            <a href="/categories" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('categories.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                Categorías
            </a>

            <a href="/brands" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('brands.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                    </path>
                </svg>
                Marcas
            </a>

            <a href="{{ route('taxes.index') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('taxes.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                    </path>
                </svg>
                Impuestos
            </a>
        </div>

        <!-- REPORTES -->
        <div class="mt-6">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Reportes</h3>

            <a href="/reports/sales" wire:navigate
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('reports.sales') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                Ventas por Período
            </a>

            <a href="{{ route('reports.top-products') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('reports.top-products') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                Productos Más Vendidos
            </a>

            <a href="{{ route('reports.inventory') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('reports.inventory') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                Inventario Actual
            </a>

            <a href="{{ route('reports.price-history') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('reports.price-history') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Historial de Precios
            </a>
        </div>

        <!-- CONFIGURACIÓN -->
        <div class="mt-6 mb-6">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Configuración</h3>

            <a href="{{ route('users.index') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                Usuarios
            </a>

            <a href="{{ route('profile.edit') }}"
                class="flex items-center px-4 py-3 mb-1 rounded-lg transition-colors {{ request()->routeIs('profile.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Mi Perfil
            </a>
        </div>
    </nav>
</aside>
