<x-app-layout>
    <div class="w-full h-[calc(100vh-65px)] flex overflow-hidden bg-[#f0f2f5] font-sans antialiased">
        
        <div class="w-[310px] h-full bg-white border-r border-gray-200 flex flex-col shrink-0 z-10">
            
            <div class="p-3 bg-[#f0f2f5] border-b border-gray-200 shrink-0">
                <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection">
                    <select name="connection_id" class="w-full bg-white rounded-lg border border-gray-300 text-xs font-bold py-2 px-3 text-gray-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer" onchange="document.getElementById('form-connection').submit();">
                        @foreach($connections as $conn)
                            <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                🏟️ {{ $conn->name }} ({{ $conn->phone_number }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="p-2 bg-white border-b border-gray-100 shrink-0">
                <div class="relative w-full">
                    <input type="text" id="searchChat" class="w-full bg-[#f0f2f5] rounded-lg border-none text-xs py-2 pl-9 pr-4 placeholder-gray-500 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="Pesquisar conversa">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-xs">
                        🔍
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 style-scrollbar bg-white">
                @forelse($contacts as $contact)
                    @php 
                        $isHuman = $contact->is_human_mode;
                        $hasUnread = $contact->unread_count > 0;
                        $exibitionNumber = str_replace('@s.whatsapp.net', '', $contact->remote_jid);
                        $exibitionName = (!empty($contact->customer_name) && filter_var($contact->customer_name, FILTER_VALIDATE_EMAIL) === false) ? $contact->customer_name : $exibitionNumber;
                        
                        $bgClass = 'bg-white hover:bg-[#f5f6f6]';
                        if ($activeChat == $contact->remote_jid) {
                            $bgClass = 'bg-[#eaebeb]';
                        } elseif ($isHuman) {
                            $bgClass = 'bg-amber-50/70 hover:bg-amber-100/60 border-l-4 border-amber-500 pl-2';
                        }
                    @endphp
                    <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                       class="flex items-center h-[72px] px-3 transition duration-150 contact-item {{ $bgClass }}"
                       data-name="{{ strtolower($exibitionName) }}">
                        
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-base text-gray-500 shrink-0 select-none">
                            👤
                        </div>

                        <div class="flex-1 min-w-0 ml-3 h-full flex flex-col justify-center border-b border-gray-100 py-1">
                            <div class="flex justify-between items-baseline">
                                <span class="font-semibold text-sm text-gray-900 truncate pr-2">
                                    {{ $exibitionName }}
                                </span>
                                <small class="text-gray-400 text-xs shrink-0 font-mono">
                                    {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                </small>
                            </div>

                            <div class="flex justify-between items-center mt-0.5">
                                <p class="text-gray-500 text-xs truncate flex-1 pr-2">
                                    {{ $contact->last_message_text }}
                                </p>
                                
                                <div class="flex gap-1 items-center shrink-0 ml-2">
                                    @if($isHuman)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black bg-amber-600 text-white animate-pulse tracking-wider">HUMANO</span>
                                    @endif

                                    @if($hasUnread)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full text-[9px] font-bold bg-[#25d366] text-white">{{ $contact->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center text-xs text-gray-400 italic">Nenhuma conversa encontrada.</div>
                @endforelse
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#efeae2] relative min-w-0">
            @if($activeChat)
                <div class="h-14 px-6 bg-[#f0f2f5] border-b border-gray-200 flex items-center justify-between shrink-0 z-10 shadow-xs">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-sm text-emerald-600 select-none mr-3">
                            Public
                        </div>
                        <div>
                            <h6 class="font-bold text-gray-800 text-sm leading-tight">
                                💬 {{ str_replace('@s.whatsapp.net', '', $activeChat) }}
                            </h6>
                            <small class="text-emerald-600 font-semibold text-xs">Atendimento em tempo real</small>
                        </div>
                    </div>
                </div>

                <div class="flex-1 px-8 py-4 overflow-y-auto flex flex-col gap-2.5 bg-whatsapp-pattern style-scrollbar" id="messagesBox">
                    @foreach($messages as $msg)
                        <div class="w-full flex {{ $msg->from_me ? 'justify-end' : 'justify-start' }}">
                            <div class="p-2.5 rounded-lg shadow-xs max-w-xl relative break-words" 
                                 style="{{ $msg->from_me ? 'background-color: #d9fdd3; border-top-right-radius: 0;' : 'background-color: #ffffff; border-top-left-radius: 0;' }}">
                                <p class="text-sm text-gray-900 leading-normal mb-1 whitespace-pre-wrap">{{ $msg->message }}</p>
                                <div class="text-right text-gray-400 select-none font-mono" style="font-size: 9px; margin-top: -2px;">
                                    {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="h-16 px-6 bg-[#f0f2f5] border-t border-gray-200 flex items-center shrink-0 z-10">
                    <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="flex w-full gap-3 items-center">
                        @csrf
                        <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                        <input type="text" name="message" class="flex-1 rounded-lg border-none shadow-xs text-sm py-2.5 px-4 bg-white placeholder-gray-400 focus:ring-1 focus:ring-emerald-500 focus:outline-none" placeholder="Digite uma mensagem" required autocomplete="off">
                        <button type="submit" class="inline-flex items-center justify-center h-9 w-9 rounded-full text-white bg-emerald-600 hover:bg-emerald-700 transition duration-150 shrink-0 shadow-sm text-sm focus:outline-none">
                            🚀
                        </button>
                    </form>
                </div>
            @else
                <div class="m-auto text-center p-8 flex flex-col items-center justify-center z-10 select-none">
                    <div class="w-20 h-20 bg-gray-200/60 rounded-full flex items-center justify-center text-3xl mb-3 text-gray-400/80">
                        🏟️
                    </div>
                    <h5 class="text-gray-700 font-bold text-sm mb-1">Central de Atendimento • {{ $site_info->nome_fantasia ?? 'Arena' }}</h5>
                    <p class="text-gray-400 text-xs max-w-xs leading-relaxed mx-auto">Selecione um cliente na barra lateral para interagir ou monitorar as conversas do WhatsApp.</p>
                </div>
            @endif
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
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>

    <style>
        .style-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .style-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.16); border-radius: 99px; }
        
        .bg-whatsapp-pattern {
            background-color: #efeae2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M9 24c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zM12 9c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zm30 0c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zM9 42c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3z' fill='%23e5ddd5' fill-opacity='0.55' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.96); opacity: 0.85; }
            50% { transform: scale(1.02); opacity: 1; }
        }
        .animate-pulse { animation: pulse 1.8s infinite ease-in-out; }
    </style>
</x-app-layout>