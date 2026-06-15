<x-app-layout>
    <div class="flex w-full h-[calc(100vh-64px)] bg-[#d1d7db] overflow-hidden font-sans">
        
        <div class="flex w-full h-full max-w-[1600px] mx-auto bg-white shadow-sm overflow-hidden 2xl:py-0">
            
            <div class="w-[30%] min-w-[320px] max-w-[420px] flex flex-col bg-white border-r border-[#d1d7db] z-20">
                
                <div class="h-[59px] bg-[#f0f2f5] flex items-center px-4 justify-between shrink-0">
                    <div class="w-10 h-10 rounded-full bg-[#dfe5e7] overflow-hidden cursor-pointer">
                        <img src="https://ui-avatars.com/api/?name=Arena&background=dfe5e7&color=111b21" alt="Perfil" class="w-full h-full object-cover">
                    </div>
                    <div class="flex gap-4 text-[#54656f]">
                        <svg viewBox="0 0 24 24" width="24" height="24" class="fill-current cursor-pointer"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"></path></svg>
                    </div>
                </div>

                <div class="bg-white border-b border-[#f2f2f2] p-2 shrink-0">
                    <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection" class="w-full flex bg-[#f0f2f5] rounded-lg px-3 py-[6px] items-center">
                        <svg viewBox="0 0 24 24" width="20" height="20" class="fill-current text-[#54656f] mr-4 shrink-0"><path d="M15.009 13.805h-.636l-.22-.219a5.184 5.184 0 0 0 1.256-3.386 5.207 5.207 0 1 0-5.207 5.208 5.183 5.183 0 0 0 3.385-1.255l.221.22v.635l4.004 3.999 1.194-1.195-3.997-4.007zm-4.608 0a3.606 3.606 0 1 1 0-7.212 3.606 3.606 0 0 1 0 7.212z"></path></svg>
                        <select name="connection_id" class="w-full bg-transparent border-none p-0 text-[15px] text-[#111b21] focus:ring-0 cursor-pointer" onchange="document.getElementById('form-connection').submit();">
                            @foreach($connections as $conn)
                                <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                    {{ $conn->name }} ({{ $conn->phone_number }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="flex-1 overflow-y-auto bg-white custom-scrollbar">
                    @forelse($contacts as $contact)
                        @php 
                            $isHuman = $contact->is_human_mode;
                            $hasUnread = $contact->unread_count > 0;
                            $exibitionNumber = str_replace('@s.whatsapp.net', '', $contact->remote_jid);
                            $exibitionName = (!empty($contact->customer_name) && filter_var($contact->customer_name, FILTER_VALIDATE_EMAIL) === false) ? $contact->customer_name : $exibitionNumber;
                            $isActive = $activeChat == $contact->remote_jid;
                        @endphp
                        
                        <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                           class="flex items-center h-[72px] hover:bg-[#f5f6f6] cursor-pointer {{ $isActive ? 'bg-[#f0f2f5]' : '' }}">
                            
                            <div class="px-[13px] shrink-0">
                                <div class="w-[49px] h-[49px] rounded-full bg-[#dfe5e7] flex items-center justify-center overflow-hidden">
                                    <svg viewBox="0 0 24 24" width="30" height="30" class="text-white fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                                </div>
                            </div>

                            <div class="flex-1 h-full flex flex-col justify-center pr-[15px] border-b border-[#f2f2f2] min-w-0">
                                <div class="flex justify-between items-center mb-[2px]">
                                    <span class="text-[17px] text-[#111b21] truncate font-normal leading-tight">
                                        {{ $exibitionName }}
                                    </span>
                                    <span class="text-[12px] {{ $hasUnread ? 'text-[#25D366] font-medium' : 'text-[#667781]' }} shrink-0 ml-2">
                                        {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] text-[#667781] truncate pr-2">
                                        @if($isHuman)
                                            <span class="text-amber-500 font-bold text-[11px] mr-1">[HUMANO]</span>
                                        @endif
                                        {{ $contact->last_message_text }}
                                    </span>
                                    @if($hasUnread)
                                        <div class="bg-[#25D366] text-white text-[11px] font-bold rounded-full min-w-[20px] h-[20px] flex items-center justify-center px-1 shrink-0">
                                            {{ $contact->unread_count }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-[#667781] text-[14px]">Nenhuma conversa encontrada.</div>
                    @endforelse
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-[#efeae2] relative min-w-0 z-10 w-[70%] border-l border-[#d1d7db]">
                @if($activeChat)
                    
                    <div class="h-[59px] bg-[#f0f2f5] flex items-center px-4 justify-between shrink-0 z-20">
                        <div class="flex items-center cursor-pointer">
                            <div class="w-10 h-10 rounded-full bg-[#dfe5e7] flex items-center justify-center overflow-hidden mr-[15px] shrink-0">
                                <svg viewBox="0 0 24 24" width="24" height="24" class="text-white fill-current"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[16px] text-[#111b21] truncate font-normal leading-normal">
                                    {{ str_replace('@s.whatsapp.net', '', $activeChat) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-6 text-[#54656f]">
                            <svg viewBox="0 0 24 24" width="24" height="24" class="fill-current cursor-pointer"><path d="M15.9 14.3H15l-.3-.3c1-1.1 1.6-2.7 1.6-4.3 0-3.7-3-6.7-6.7-6.7S3 6 3 9.7s3 6.7 6.7 6.7c1.6 0 3.2-.6 4.3-1.6l.3.3v.8l5.1 5.1 1.5-1.5-5-5.2zm-6.2 0c-2.6 0-4.6-2.1-4.6-4.6s2.1-4.6 4.6-4.6 4.6 2.1 4.6 4.6-2 4.6-4.6 4.6z"></path></svg>
                            <svg viewBox="0 0 24 24" width="24" height="24" class="fill-current cursor-pointer"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"></path></svg>
                        </div>
                    </div>

                    <div class="absolute inset-0 z-0 opacity-[0.4]" style="background-image: url('https://static.whatsapp.net/rsrc.php/v3/yl/r/gi_DcqO4rcy.png'); background-repeat: repeat;"></div>

                    <div class="flex-1 overflow-y-auto px-[5%] lg:px-[9%] py-4 flex flex-col gap-[3px] z-10 custom-scrollbar" id="messagesBox">
                        @foreach($messages as $msg)
                            @php
                                $isMe = $msg->from_me;
                                $bubbleBg = $isMe ? 'bg-[#d9fdd3]' : 'bg-white';
                                $bubbleRadius = $isMe ? 'rounded-lg rounded-tr-none' : 'rounded-lg rounded-tl-none';
                            @endphp
                            
                            <div class="flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }} mb-1 relative">
                                
                                @if($isMe)
                                    <span class="absolute top-0 -right-[8px] text-[#d9fdd3] z-20">
                                        <svg viewBox="0 0 8 13" width="8" height="13" class="fill-current"><path d="M5.188 1H0v11.193l6.467-8.625C7.526 2.156 6.958 1 5.188 1z"></path></svg>
                                    </span>
                                @else
                                    <span class="absolute top-0 -left-[8px] text-white z-20">
                                        <svg viewBox="0 0 8 13" width="8" height="13" class="fill-current"><path d="M5.188 1H0v11.193l6.467-8.625C7.526 2.156 6.958 1 5.188 1z" transform="scale(-1, 1) translate(-8, 0)"></path></svg>
                                    </span>
                                @endif

                                <div class="relative max-w-[85%] lg:max-w-[70%] {{ $bubbleBg }} {{ $bubbleRadius }} shadow-[0_1px_0.5px_rgba(11,20,26,.13)] px-[9px] py-[6px] flex flex-col z-10">
                                    
                                    <div class="text-[14.2px] text-[#111b21] leading-[19px] whitespace-pre-wrap pb-[12px] break-words">{!! nl2br(e($msg->message)) !!}</div>
                                    
                                    <div class="absolute bottom-[4px] right-[7px] flex items-center gap-[3px] text-[11px] text-[#667781] leading-none bg-transparent">
                                        {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                        @if($isMe)
                                            <svg viewBox="0 0 16 16" width="16" height="11" class="text-[#53bdeb] fill-current"><path d="M15.01 3.316l-.478-.372a.365.365 0 00-.51.063L8.666 9.879a.32.32 0 01-.484.033l-.358-.325a.32.32 0 00-.484.032l-.378.483a.418.418 0 00.036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 00-.064-.512zm-4.1 0l-.478-.372a.365.365 0 00-.51.063L4.566 9.879a.32.32 0 01-.484.033L1.891 7.769a.366.366 0 00-.515.006l-.423.433a.364.364 0 00.006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 00-.063-.51z"/></svg>
                                        @endif
                                    </div>
                                    
                                    <div class="h-0 block clear-both float-right w-[50px]"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="min-h-[62px] bg-[#f0f2f5] px-4 py-[10px] flex items-end gap-4 shrink-0 z-20">
                        <div class="text-[#54656f] shrink-0 cursor-pointer pb-2">
                            <svg viewBox="0 0 24 24" width="26" height="26" class="fill-current"><path d="M9.153 11.603c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962zm-3.204 1.362c-.026-.307-.131 5.218 6.063 5.551 6.066-.25 6.066-5.551 6.066-5.551-6.078 1.416-12.129 0-12.129 0zm11.363-1.108s-.669 1.959-5.051 1.959c-3.379 0-5.549-1.158-5.549-1.158-.059 4.851 4.544 5.378 5.549 5.378 5.813 0 5.051-6.179 5.051-6.179zm-3.078-1.455c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962z"></path></svg>
                        </div>
                        <div class="text-[#54656f] shrink-0 cursor-pointer pb-2">
                            <svg viewBox="0 0 24 24" width="24" height="24" class="fill-current"><path d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z"></path></svg>
                        </div>
                        
                        <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="flex-1 flex items-center gap-4 h-full">
                            @csrf
                            <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                            <div class="flex-1 bg-white rounded-lg h-[42px] px-3 flex items-center">
                                <input type="text" name="message" class="w-full h-full bg-transparent border-none focus:ring-0 text-[#111b21] text-[15px] placeholder-[#8696a0]" placeholder="Mensagem" required autocomplete="off" autofocus>
                            </div>
                            <button type="submit" class="text-[#54656f] shrink-0 hover:text-[#111b21] cursor-pointer pb-1">
                                <svg viewBox="0 0 24 24" width="26" height="26" class="fill-current"><path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path></svg>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center bg-[#f0f2f5] border-b-[6px] border-[#25D366] text-center px-4 relative z-10">
                        <img src="https://static.whatsapp.net/rsrc.php/v3/y6/r/wa66cgRqSqf.png" alt="WhatsApp Web" class="w-[320px] mb-8 opacity-90">
                        <h1 class="text-[32px] font-light text-[#41525d] mb-4">Arena WhatsApp Web</h1>
                        <p class="text-[14px] text-[#667781] leading-[20px] max-w-[560px]">
                            Envie e receba mensagens sem precisar manter seu celular conectado à internet.<br>
                            Use o WhatsApp em até 4 aparelhos conectados e 1 celular ao mesmo tempo.
                        </p>
                        <div class="mt-8 flex items-center text-[#8696a0] text-[14px]">
                            <svg viewBox="0 0 10 12" width="10" height="12" class="fill-current mr-1.5"><path d="M5 0C2.239 0 0 2.239 0 5c0 2.761 2.239 5 5 5s5-2.239 5-5c0-2.761-2.239-5-5-5zm0 8.75A3.75 3.75 0 1 1 5 1.25a3.75 3.75 0 0 1 0 7.5zm-.5-6v3.25h2v1h-3V2.75h1z"/></svg>
                            Protegido com a criptografia de ponta a ponta
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const box = document.getElementById('messagesBox');
        if(box) { box.scrollTop = box.scrollHeight; }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(11,20,26,.2);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        input[type="text"]:focus, select:focus {
            box-shadow: none !important;
            border-color: transparent !important;
        }
    </style>
</x-app-layout>