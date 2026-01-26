<aside class="w-64 bg-gray-800 text-white">
    <div class="p-6">
        <h2 class="text-2xl font-bold">Sistema Ventas</h2>
    </div>

    <nav class="mt-6">
        <a href="{{ route('dashboard') }}"
            class="flex items-center px-6 py-3 {{ request()->routeIs('dashboard') ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('products.index') }}"
            class="flex items-center px-6 py-3 {{ request()->routeIs('products.*') ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Productos
        </a>

        <a href="{{ route('sales.index') }}"
            class="flex items-center px-6 py-3 {{ request()->routeIs('sales.*') ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            Ventas
        </a>

        <a href="{{ route('inventory.index') }}"
            class="flex items-center px-6 py-3 {{ request()->routeIs('inventory.*') ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                </path>
            </svg>
            Inventario
        </a>
    </nav>
</aside>
