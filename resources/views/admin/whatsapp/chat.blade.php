<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('🟢 Central Multiatendimento - WhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-6" style="height: calc(100vh - 160px);">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 h-100">
            <div class="row h-100 g-3">
                
                <div class="col-md-4 h-100 d-flex flex-column bg-white rounded-lg shadow-sm border overflow-hidden">
                    
                    <div class="p-3 border-b bg-gray-50">
                        <label class="form-label text-gray-500 text-xs font-black uppercase tracking-wider mb-2 block">CONEXÃO ATIVA</label>
                        <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection">
                            <select name="connection_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="document.getElementById('form-connection').submit();">
                                @foreach($connections as $conn)
                                    <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                        🏟️ {{ $conn->name }} ({{ $conn->phone_number }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="p-2 border-b bg-gray-50">
                        <input type="text" id="searchChat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="🔍 Pesquisar conversa pelo nome...">
                    </div>

                    <div class="flex-grow overflow-y-auto style-scrollbar" style="max-height: calc(100vh - 340px);">
                        <div class="divide-y divide-gray-100" id="contactsList">
                            @foreach($contacts as $contact)
                                @php 
                                    $isHuman = $contact->is_human_mode;
                                    $hasUnread = $contact->unread_count > 0;
                                @endphp
                                <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                                   class="block p-3 hover:bg-gray-50 transition duration-150 contact-item {{ $activeChat == $contact->remote_jid ? 'bg-indigo-50/70 hover:bg-indigo-50' : '' }}"
                                   data-name="{{ strtolower($contact->customer_name ?? $contact->remote_jid) }}">
                                    
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-sm text-gray-900 truncate" style="max-width: 70%;">
                                            {{ $contact->customer_name ?? str_replace('@s.whatsapp.net', '', $contact->remote_jid) }}
                                        </span>
                                        <small class="text-gray-400 text-xs">
                                            {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                        </small>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <p class="text-gray-500 text-xs truncate mb-0 flex-grow" style="max-width: 75%;">
                                            {{ $contact->last_message_text }}
                                        </p>
                                        
                                        <div class="flex gap-1 items-center ms-2">
                                            @if($isHuman)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xxs font-black bg-red-100 text-red-800 animate-pulse">HUMANO</span>
                                            @endif

                                            @if($hasUnread)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-500 text-white">{{ $contact->unread_count }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-8 h-100 d-flex flex-column bg-white rounded-lg shadow-sm border overflow-hidden">
                    @if($activeChat)
                        <div class="p-3 border-b bg-gray-50 flex justify-between items-center">
                            <div>
                                <h6 class="mb-0 font-bold text-gray-800 text-sm">💬 {{ str_replace('@s.whatsapp.net', '', $activeChat) }}</h6>
                                <small class="text-emerald-600 font-semibold text-xs">Histórico de conversação sincronizado</small>
                            </div>
                        </div>

                        <div class="flex-grow p-3 overflow-y-auto bg-gray-100 flex flex-col gap-2" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); max-height: calc(100vh - 360px);" id="messagesBox">
                            @foreach($messages as $msg)
                                <div class="w-full flex {{ $msg->from_me ? 'justify-end' : 'justify-start' }}">
                                    <div class="p-2 rounded-lg shadow-xs max-w-xl relative" 
                                         style="{{ $msg->from_me ? 'background-color: #d9fdd3; border-top-right-radius: 0;' : 'background-color: #ffffff; border-top-left-radius: 0;' }}">
                                        <p class="text-sm text-gray-900 leading-normal mb-1" style="white-space: pre-wrap;">{{ $msg->message }}</p>
                                        <div class="text-right text-gray-400" style="font-size: 9px;">
                                            {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-3 border-t bg-white">
                            <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                                <input type="text" name="message" class="flex-grow rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Digite uma resposta manual para assumir o controle da conversa..." required autocomplete="off">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Enviar 🚀
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="m-auto text-center p-5 text-gray-400">
                            <div class="text-6xl mb-3 opacity-30">📱</div>
                            <h5 class="text-gray-700 font-bold mb-1">Central de Atendimento Omnichannel</h5>
                            <p class="text-xs max-w-xs mx-auto">Selecione uma conversa na lista lateral para monitorar a IA ou responder de forma humana.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <script>
        const box = document.getElementById('messagesBox');
        if(box) { box.scrollTop = box.scrollHeight; }

        document.getElementById('searchChat').addEventListener('input', function(e) {
            const value = e.target.value.toLowerCase();
            document.querySelectorAll('.contact-item').forEach(item => {
                const name = item.getAttribute('data-name');
                if(name.includes(value)) {
                    item.style.setProperty('display', 'block', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });
    </script>

    <style>
        .text-xxs { font-size: 0.65rem; }
        .style-scrollbar::-webkit-scrollbar { width: 5px; }
        .style-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.1); border-radius: 4px; }
        @keyframes pulse {
            0%, 100% { transform: scale(0.98); opacity: 0.8; }
            50% { transform: scale(1); opacity: 1; }
        }
        .animate-pulse { animation: pulse 2s infinite ease-in-out; }
    </style>
</x-app-layout>