<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('🟢 Central Multiatendimento - WhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-4 h-[calc(100vh-170px)]">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 h-full">
            
            <div class="flex h-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="w-1/3 border-r border-gray-200 flex flex-col h-full bg-white">
                    
                    <div class="p-3 border-b border-gray-100 bg-gray-50">
                        <label class="text-gray-500 text-[10px] font-black uppercase tracking-wider mb-1.5 block">CONEXÃO ATIVA</label>
                        <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection">
                            <select name="connection_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white py-1.5" onchange="document.getElementById('form-connection').submit();">
                                @foreach($connections as $conn)
                                    <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                        🏟️ {{ $conn->name }} ({{ $conn->phone_number }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="p-2 border-b border-gray-100 bg-gray-50">
                        <input type="text" id="searchChat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5" placeholder="🔍 Pesquisar conversa pelo nome...">
                    </div>

                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100 style-scrollbar">
                        @forelse($contacts as $contact)
                            @php 
                                $isHuman = $contact->is_human_mode;
                                $hasUnread = $contact->unread_count > 0;
                            @endphp
                            <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                               class="block p-3 hover:bg-gray-50 transition duration-150 contact-item {{ $activeChat == $contact->remote_jid ? 'bg-indigo-50/80 hover:bg-indigo-50 border-l-4 border-indigo-600 pl-2.5' : '' }}"
                               data-name="{{ strtolower($contact->customer_name ?? $contact->remote_jid) }}">
                                
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-sm text-gray-900 truncate style-chat-name">
                                        {{ $contact->customer_name ?? str_replace('@s.whatsapp.net', '', $contact->remote_jid) }}
                                    </span>
                                    <small class="text-gray-400 text-xs shrink-0 ml-2">
                                        {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                    </small>
                                </div>

                                <div class="flex justify-between items-center">
                                    <p class="text-gray-500 text-xs truncate flex-1 pr-2">
                                        {{ $contact->last_message_text }}
                                    </p>
                                    
                                    <div class="flex gap-1.5 items-center shrink-0">
                                        @if($isHuman)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-red-100 text-red-800 animate-pulse tracking-wider">HUMANO</span>
                                        @endif

                                        @if($hasUnread)
                                            <span class="inline-flex items-center justify-center h-5 min-w-5 px-1 rounded-full text-[10px] font-bold bg-emerald-500 text-white">{{ $contact->unread_count }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-sm text-gray-400 italic">Nenhuma conversa registrada.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex-1 flex flex-col h-full bg-[#efeae2] relative">
                    @if($activeChat)
                        <div class="p-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center z-10 shadow-sm">
                            <div>
                                <h6 class="mb-0 font-bold text-gray-800 text-sm">💬 {{ str_replace('@s.whatsapp.net', '', $activeChat) }}</h6>
                                <small class="text-emerald-600 font-semibold text-xs">Atendimento ativo com o estabelecimento</small>
                            </div>
                        </div>

                        <div class="flex-1 p-4 overflow-y-auto flex flex-col gap-2.5 bg-whatsapp-pattern style-scrollbar" id="messagesBox">
                            @foreach($messages as $msg)
                                <div class="w-full flex {{ $msg->from_me ? 'justify-end' : 'justify-start' }}">
                                    <div class="p-2 rounded-lg shadow-sm max-w-xl relative break-words" 
                                         style="{{ $msg->from_me ? 'background-color: #d9fdd3; border-top-right-radius: 0;' : 'background-color: #ffffff; border-top-left-radius: 0;' }}">
                                        <p class="text-sm text-gray-900 leading-normal mb-1 whitespace-pre-wrap">{{ $msg->message }}</p>
                                        <div class="text-right text-gray-400 select-none" style="font-size: 9px;">
                                            {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-3 border-t border-gray-200 bg-gray-50 z-10">
                            <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                                <input type="text" name="message" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" placeholder="Digite uma resposta manual para assumir o controle da conversa..." required autocomplete="off">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150">
                                    Enviar 🚀
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="m-auto text-center p-6 flex flex-col items-center justify-center z-10">
                            <div class="text-7xl mb-4 opacity-20 select-none">🏟️</div>
                            <h5 class="text-gray-700 font-bold text-base mb-1">Central de Atendimento da Arena</h5>
                            <p class="text-gray-500 text-xs max-w-xs leading-relaxed">Selecione uma conversa na lista lateral para monitorar o robô de IA ou responder o cliente diretamente.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <script>
        // Mantém a rolagem sempre no final ao carregar as mensagens
        const box = document.getElementById('messagesBox');
        if(box) { box.scrollTop = box.scrollHeight; }

        // Filtro de pesquisa em tempo real
        document.getElementById('searchChat').addEventListener('input', function(e) {
            const value = e.target.value.toLowerCase();
            document.querySelectorAll('.contact-item').forEach(item => {
                const name = item.getAttribute('data-name');
                if(name.includes(value)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>

    <style>
        .style-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .style-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.12); border-radius: 99px; }
        .style-chat-name { max-width: calc(100% - 10px); }
        
        /* Textura geométrica sutil autoral simulando o fundo de marca d'água */
        .bg-whatsapp-pattern {
            background-color: #efeae2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M9 24c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zM12 9c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zm30 0c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zM9 42c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3z' fill='%23e5ddd5' fill-opacity='0.5' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.97); opacity: 0.85; }
            50% { transform: scale(1.02); opacity: 1; }
        }
        .animate-pulse { animation: pulse 1.8s infinite ease-in-out; }
    </style>
</x-app-layout>