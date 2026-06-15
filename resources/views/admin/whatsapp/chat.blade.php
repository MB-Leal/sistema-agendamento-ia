<x-app-layout>
    <div class="w-full h-[calc(100vh-65px)] flex overflow-hidden bg-[#d1d7db] font-sans antialiased">
        
        <div class="w-[380px] h-full bg-white border-r border-gray-300 flex flex-col shrink-0 z-10">
            
            <div class="h-16 bg-[#f0f2f5] border-b border-gray-200 shrink-0 flex items-center px-4">
                <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection" class="w-full">
                    <select name="connection_id" class="w-full bg-white rounded-lg border-none text-sm py-2 px-3 text-[#111b21] shadow-sm focus:outline-none focus:ring-0 cursor-pointer" onchange="document.getElementById('form-connection').submit();">
                        @foreach($connections as $conn)
                            <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                📱 {{ $conn->name }} ({{ $conn->phone_number }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="flex-1 overflow-y-auto bg-white style-scrollbar">
                @forelse($contacts as $contact)
                    @php 
                        $isHuman = $contact->is_human_mode;
                        $hasUnread = $contact->unread_count > 0;
                        $exibitionNumber = str_replace('@s.whatsapp.net', '', $contact->remote_jid);
                        $exibitionName = (!empty($contact->customer_name) && filter_var($contact->customer_name, FILTER_VALIDATE_EMAIL) === false) ? $contact->customer_name : $exibitionNumber;
                        
                        $bgClass = 'bg-white hover:bg-[#f5f6f6]';
                        if ($activeChat == $contact->remote_jid) {
                            $bgClass = 'bg-[#f0f2f5]'; // Cor ativa igual ao WA Web
                        } elseif ($isHuman) {
                            $bgClass = 'bg-amber-50/70 hover:bg-amber-100/60 border-l-4 border-amber-500';
                        }
                    @endphp
                    <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                       class="flex items-center h-[72px] pl-3 pr-4 transition-colors duration-150 {{ $bgClass }} cursor-pointer">
                        
                        <div class="w-12 h-12 rounded-full bg-[#dfe5e7] flex items-center justify-center shrink-0 overflow-hidden">
                            <svg viewBox="0 0 24 24" width="28" height="28" class="text-white fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        </div>

                        <div class="flex-1 min-w-0 ml-3 h-full flex flex-col justify-center border-b border-gray-100">
                            <div class="flex justify-between items-baseline mb-0.5">
                                <span class="font-normal text-[16px] text-[#111b21] truncate pr-2">
                                    {{ $exibitionName }}
                                </span>
                                <small class="text-[12px] shrink-0 {{ $hasUnread ? 'text-[#25D366] font-medium' : 'text-[#667781]' }}">
                                    {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                </small>
                            </div>

                            <div class="flex justify-between items-center">
                                <p class="text-[#667781] text-[13px] truncate flex-1 pr-2">
                                    {{ $contact->last_message_text }}
                                </p>
                                
                                <div class="flex gap-1.5 items-center shrink-0">
                                    @if($isHuman)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500 text-white tracking-wider shadow-sm">HUMANO</span>
                                    @endif

                                    @if($hasUnread)
                                        <span class="inline-flex items-center justify-center min-w-[20px] h-[20px] px-1 rounded-full text-[11px] font-bold bg-[#25D366] text-white">
                                            {{ $contact->unread_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-[14px] text-[#667781]">Nenhuma conversa encontrada.</div>
                @endforelse
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#efeae2] relative min-w-0 border-l border-gray-300">
            @if($activeChat)
                
                <div class="h-16 px-4 bg-[#f0f2f5] flex items-center justify-between shrink-0 shadow-sm z-10 border-b border-gray-200">
                    <div class="flex items-center cursor-pointer">
                        <div class="w-10 h-10 rounded-full bg-[#dfe5e7] flex items-center justify-center shrink-0 overflow-hidden mr-3">
                            <svg viewBox="0 0 24 24" width="24" height="24" class="text-white fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        </div>
                        <div class="flex flex-col justify-center">
                            <h6 class="font-normal text-[#111b21] text-[16px] leading-tight mb-0.5">
                                {{ str_replace('@s.whatsapp.net', '', $activeChat) }}
                            </h6>
                            <span class="text-[#667781] text-[13px] leading-none">
                                Clique para ver os dados do contato
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 px-[5%] py-5 overflow-y-auto flex flex-col gap-1.5 bg-whatsapp-pattern style-scrollbar" id="messagesBox">
                    @foreach($messages as $msg)
                        <div class="w-full flex {{ $msg->from_me ? 'justify-end' : 'justify-start' }} mb-1">
                            
                            <div class="relative px-3 pt-1.5 pb-2 text-[14.2px] shadow-sm flex flex-col max-w-[85%] lg:max-w-[65%]" 
                                 style="{{ $msg->from_me ? 'background-color: #d9fdd3; border-radius: 8px 0px 8px 8px;' : 'background-color: #ffffff; border-radius: 0px 8px 8px 8px;' }}">
                                
                                <span class="text-[#111b21] leading-snug whitespace-pre-wrap pr-10">{!! nl2br(e($msg->message)) !!}</span>
                                
                                <span class="text-[#667781] text-[11px] text-right absolute bottom-1 right-2">
                                    {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                    @if($msg->from_me)
                                        <svg viewBox="0 0 16 16" width="16" height="15" class="inline-block ml-0.5 text-[#53bdeb] fill-current"><path d="M15.01 3.316l-.478-.372a.365.365 0 00-.51.063L8.666 9.879a.32.32 0 01-.484.033l-.358-.325a.32.32 0 00-.484.032l-.378.483a.418.418 0 00.036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 00-.064-.512zm-4.1 0l-.478-.372a.365.365 0 00-.51.063L4.566 9.879a.32.32 0 01-.484.033L1.891 7.769a.366.366 0 00-.515.006l-.423.433a.364.364 0 00.006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 00-.063-.51z"/></svg>
                                    @endif
                                </span>
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="min-h-[62px] px-4 py-2.5 bg-[#f0f2f5] flex items-end shrink-0 z-10">
                    <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="flex w-full gap-3 items-center">
                        @csrf
                        <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                        
                        <div class="flex-1 bg-white rounded-lg flex items-center px-4 py-1 border border-transparent shadow-sm overflow-hidden min-h-[42px]">
                            <input type="text" name="message" class="w-full border-none p-0 text-[15px] text-[#111b21] placeholder-[#667781] focus:ring-0 focus:outline-none" placeholder="Digite uma mensagem" required autocomplete="off">
                        </div>
                        
                        <button type="submit" class="flex items-center justify-center w-10 h-10 rounded-full text-[#54656f] hover:text-[#111b21] transition duration-150 shrink-0 focus:outline-none" title="Enviar">
                            <svg viewBox="0 0 24 24" height="24" width="24" preserveAspectRatio="xMidYMid meet" class="fill-current">
                                <path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="m-auto text-center p-8 flex flex-col items-center justify-center z-10 select-none w-full h-full border-b-[6px] border-[#25D366]">
                    <div class="mb-8">
                        <svg viewBox="0 0 100 100" width="250" height="250" class="text-[#e2e8f0] fill-current mx-auto"><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm0 90C27.9 90 10 72.1 10 50S27.9 10 50 10s40 17.9 40 40-17.9 40-40 40z"/><path d="M30 40h40v6H30zm0 14h30v6H30z"/></svg>
                    </div>
                    <h1 class="text-[#41525d] font-light text-[32px] mb-4">Arena WhatsApp Web</h1>
                    <p class="text-[#667781] text-[14px] leading-relaxed max-w-md mx-auto">
                        Selecione um cliente na barra lateral para iniciar o atendimento ou monitorar a IA em tempo real.<br>
                        Mantenha seu celular conectado à internet.
                    </p>
                </div>
            @endif
        </div>

    </div>

    <script>
        const box = document.getElementById('messagesBox');
        if(box) { box.scrollTop = box.scrollHeight; }
    </script>

    <style>
        /* Scrollbar padronizada do Windows/WA Web */
        .style-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .style-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(11,20,26,0.2); border-radius: 3px; }
        .style-scrollbar::-webkit-scrollbar-track { background-color: transparent; }
        
        /* Fundo exato do WhatsApp Web */
        .bg-whatsapp-pattern {
            background-color: #efeae2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M9 24c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zM12 9c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zm30 0c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zM9 42c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3zm30 0c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3z' fill='%23dfd8cf' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
    </style>
</x-app-layout>