<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('🟢 Central Multiatendimento - WhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-4 h-[calc(100vh-170px)]">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 h-full">
            
            <div class="flex h-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="w-1/3 border-r border-gray-200 flex flex-column h-full bg-white">
                    
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
                                            <span class="inline-flex items-center justify-center h-5 min-w-5 px-1 rounded-full text-xxs font-bold bg-emerald-500 text-white">{{ $contact->unread_count }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-sm text-gray-400 italic">Nenhuma mensagem registrada.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex-1 flex flex-col h-full bg-[#efeae2] relative">
                    @if($activeChat)
                        <div class="p-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center z-10 shadow-xs">
                            <div>
                                <h6 class="mb-0 font-bold text-gray-800 text-sm">💬 {{ str_replace('@s.whatsapp.net', '', $activeChat) }}</h6>
                                <small class="text-emerald-600 font-semibold text-xs">Atendimento ativo com o estabelecimento</small>
                            </div>
                        </div>

                        <div class="flex-1 p-4 overflow-y-auto flex flex-col gap-2.5 bg-whatsapp-pattern style-scrollbar" id="messagesBox">
                            @foreach($messages as $msg)
                                <div class="w-full flex {{ $msg->from_me ? 'justify-end' : 'justify-start' }}">
                                    <div class="p-2 rounded-lg shadow-xs max-w-xl relative break-words" 
                                         style="{{ $msg->from_me ? 'background-color: #d9fdd3; border-top-right-radius: 0;' : 'background-color: #ffffff; border-top-left-radius: 0;' }}">
                                        <p class="text-sm text-gray-900 leading-normal mb-1 whitespace-pre-wrap">{{ $msg->message }}</p>
                                        <div class="text-right text-gray-400 select-none" style="font-size: 9px;">
                                            {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>