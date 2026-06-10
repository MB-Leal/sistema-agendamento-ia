<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::check() && Auth::user()->has_admin_access ? route('dashboard') : route('home') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-9 w-auto fill-current text-indigo-600" />
                        <div class="flex flex-col border-l border-gray-300 pl-3">
                            <span class="text-lg font-black text-gray-900 leading-none uppercase tracking-tighter">
                                {{ $site_info->nome_fantasia ?? 'Arena' }}
                            </span>
                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest leading-tight">
                                Gestão Online
                            </span>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    @if (Auth::check() && Auth::user()->has_admin_access)
                    {{-- Filtro de Módulo: Oculta Arena se for estritamente PDV (2) --}}
                    @if($site_info->modules_active != 2)
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="px-3 py-2">
                        {{ __('Home') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.reservas.index')" :active="request()->routeIs('admin.reservas.*')" class="px-3 py-2 text-sm font-semibold">
                        {{ __('Reservas') }}
                    </x-nav-link>

                    {{-- 🛡️ TRAVA PARA COLABORADOR --}}
                    @if(Auth::user()->is_admin || Auth::user()->is_gestor)
                    <x-nav-link :href="route('admin.arenas.index')" :active="request()->routeIs('admin.arenas.*')" class="px-3 py-2 text-sm font-semibold">
                        {{ __('Gerenciar Quadras') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="px-3 py-2 text-sm font-semibold">
                        {{ __('Usuários') }}
                    </x-nav-link>
                    @endif

                    <x-nav-link :href="route('admin.payment.index')" :active="request()->routeIs('admin.payment.*')" class="px-3 py-2 text-sm font-semibold">
                        {{ __('Caixa') }}
                    </x-nav-link>

                    {{-- 🏟️ CENTRAL MULTI-ATENDIMENTO WHATSAPP WEB INTEGRADO (DESKTOP) --}}
                    <x-nav-link :href="route('admin.whatsapp.chat')" :active="request()->routeIs('admin.whatsapp.*')" class="px-3 py-2 text-sm font-black text-emerald-600 hover:text-emerald-700">
                        {{ __('🟢 WhatsApp') }}
                    </x-nav-link>

                    {{-- 🛡️ TRAVA PARA COLABORADOR --}}
                    @if(Auth::user()->is_admin || Auth::user()->is_gestor)
                    <x-nav-link :href="route('admin.financeiro.dashboard')" :active="request()->routeIs('admin.financeiro.*')" class="px-3 py-2 text-sm font-semibold">
                        {{ __('Relatórios') }}
                    </x-nav-link>
                    @endif
                    @else
                    {{-- Se for só PDV, exibe apenas Home do Bar --}}
                    <x-nav-link :href="route('bar.dashboard')" :active="request()->routeIs('bar.*')" class="px-3 py-2">
                        {{ __('📦 PDV Sistema') }}
                    </x-nav-link>
                    @endif
                    @endif

                    @if (Auth::check() && Auth::user()->is_client)
                    <x-nav-link :href="route('customer.reservations.history')" :active="request()->routeIs('customer.reservations.history')" class="px-3 py-2 text-sm font-bold text-indigo-700">
                        {{ __('Minhas Reservas') }}
                    </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if (Auth::check())
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col text-right me-2">
                                <div class="text-[10px] font-black uppercase text-indigo-500 leading-none">
                                    {{ $site_info->nome_fantasia ?? 'Estabelecimento' }}
                                </div>
                                <div class="text-sm font-bold text-gray-700 leading-tight">
                                    {{ Auth::user()->name }}
                                </div>
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- 🔄 ALTERNAR MÓDULO: Maia, Marcos e Gestores usam se for Combo (3) --}}
                        @if($site_info->modules_active == 3)
                        <div class="block px-4 py-2 text-[10px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100">
                            Mudar Ambiente
                        </div>
                        @if(request()->routeIs('bar.*'))
                        <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold">
                            🏟️ Ir para Arena
                        </x-dropdown-link>
                        @else
                        <x-dropdown-link :href="route('modules.switch', 'pdv')" class="bg-amber-50 text-amber-700 font-bold">
                            🍺 Ir para o Bar/PDV
                        </x-dropdown-link>
                        @endif
                        <div class="border-t border-gray-200"></div>
                        @endif

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Meu Perfil') }}
                        </x-dropdown-link>

                        @if (Auth::user()->has_admin_access)
                        {{-- 🛡️ TRAVA PARA COLABORADOR --}}
                        @if(Auth::user()->is_admin || Auth::user()->is_gestor)
                        <x-dropdown-link :href="route('admin.company.edit')">
                            {{ __('Dados do estabelecimento') }}
                        </x-dropdown-link>
                        @endif

                        {{-- 🛡️ EXCLUSIVO ADMIN (Maia e Marcos) --}}
                        @if(Auth::user()->is_admin)
                        <x-dropdown-link :href="route('admin.plans')" class="text-indigo-600 font-black border-t border-gray-100 bg-gray-50">
                            ⚙️ Gerenciar Plano/Módulos
                        </x-dropdown-link>
                        @endif
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <a href="{{ route('customer.login') }}" class="px-3 py-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                    {{ __('Login Cliente') }}
                </a>
                @endif
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- MENU MOBILE RESPONSIVO --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::check() && Auth::user()->has_admin_access)
            @if($site_info->modules_active == 3)
            <x-responsive-nav-link :href="route('modules.switch', request()->routeIs('bar.*') ? 'arena' : 'pdv')" class="bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-700">
                {{ request()->routeIs('bar.*') ? '🏟️ Mudar para Arena' : '🍺 Mudar para Bar/PDV' }}
            </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Home') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.reservas.index')" :active="request()->routeIs('admin.reservas.*')">
                {{ __('Reservas') }}
            </x-responsive-nav-link>

            {{-- 🛡️ TRAVA PARA COLABORADOR --}}
            @if(Auth::user()->is_admin || Auth::user()->is_gestor)
            <x-responsive-nav-link :href="route('admin.arenas.index')" :active="request()->routeIs('admin.arenas.*')">
                {{ __('Gerenciar Quadras') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                {{ __('Usuários') }}
            </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('admin.payment.index')" :active="request()->routeIs('admin.payment.*')">
                {{ __('Caixa') }}
            </x-responsive-nav-link>
            
            {{-- 🟢 CORREÇÃO DA SINTAXE: Adicionado parêntese de fechamento da rota no Mobile --}}
            <x-responsive-nav-link :href="route('admin.whatsapp.chat')" :active="request()->routeIs('admin.whatsapp.*')" class="text-emerald-600 font-bold">
                {{ __('WhatsApp Central') }}
            </x-responsive-nav-link>

            {{-- 🛡️ TRAVA PARA COLABORADOR --}}
            @if(Auth::user()->is_admin || Auth::user()->is_gestor)
            <x-responsive-nav-link :href="route('admin.financeiro.dashboard')" :active="request()->routeIs('admin.financeiro.*')">
                {{ __('Relatórios') }}
            </x-responsive-nav-link>
            @endif

            <hr class="border-gray-200 my-2">

            {{-- 🛡️ TRAVA PARA COLABORADOR --}}
            @if(Auth::user()->is_admin || Auth::user()->is_gestor)
            <x-responsive-nav-link :href="route('admin.company.edit')" :active="request()->routeIs('admin.company.edit')">
                {{ __('Dados do estabelecimento') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->is_admin)
            <x-responsive-nav-link :href="route('admin.plans')" class="text-indigo-600 font-bold">
                ⚙️ Alterar Plano/Módulos
            </x-responsive-nav-link>
            @endif
            @endif

            @if (Auth::check() && Auth::user()->is_client)
            <x-responsive-nav-link :href="route('customer.reservations.history')" :active="request()->routeIs('customer.reservations.history')">
                {{ __('Minhas Reservas') }}
            </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            <div class="px-4">
                <div class="text-[10px] font-black uppercase text-indigo-600 leading-none mb-1">{{ $site_info->nome_fantasia ?? '' }}</div>
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name ?? '' }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Meu Perfil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>