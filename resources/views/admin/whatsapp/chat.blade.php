<x-app-layout>
    <style>
        /* ================= RESET E VARIÁVEIS ================= */
        :root {
            --bg-app: #d1d7db;
            --bg-panel: #f0f2f5;
            --bg-white: #ffffff;
            --bg-hover: #f5f6f6;
            --bg-active: #f0f2f5;
            --bg-chat: #efeae2;
            --text-primary: #111b21;
            --text-secondary: #667781;
            --text-muted: #8696a0;
            --bubble-in: #ffffff;
            --bubble-out: #d9fdd3;
            --green-whatsapp: #25d366;
            --border-color: #e9edef;
        }

        .wa-container {
            width: 100%;
            height: calc(100vh - 65px);
            background-color: var(--bg-app);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .app-wrapper {
            width: 100%;
            max-width: 1600px;
            height: 100%;
            background: var(--bg-white);
            display: flex;
            box-shadow: 0 6px 18px rgba(11,20,26,.05);
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 30%;
            min-width: 320px;
            max-width: 420px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background: var(--bg-white);
            z-index: 20;
        }

        .header {
            height: 59px;
            background: var(--bg-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            flex-shrink: 0;
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dfe5e7;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .header-icons {
            display: flex;
            gap: 20px;
            color: #54656f;
        }

        .header-icons svg { cursor: pointer; }

        .search-bar {
            background: var(--bg-white);
            padding: 7px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .search-input-container {
            background: var(--bg-panel);
            border-radius: 8px;
            display: flex;
            align-items: center;
            padding: 6px 12px;
            height: 35px;
        }

        .search-input-container svg {
            fill: var(--text-secondary);
            margin-right: 15px;
        }

        .search-input-container select {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            font-size: 15px;
            color: var(--text-primary);
            cursor: pointer;
            -webkit-appearance: none;
        }

        /* Lista de Contatos */
        .contact-list {
            flex: 1;
            overflow-y: auto;
        }

        .contact-item {
            display: flex;
            align-items: center;
            height: 72px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }

        .contact-item:hover { background: var(--bg-hover); }
        .contact-item.active { background: var(--bg-active); }

        .contact-avatar { padding: 0 15px 0 13px; }
        .contact-avatar .avatar-img { width: 49px; height: 49px; }

        .contact-content {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
            padding-right: 15px;
            min-width: 0;
        }

        .contact-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }

        .contact-name {
            font-size: 17px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-time {
            font-size: 12px;
            color: var(--text-secondary);
            margin-left: 6px;
        }

        .contact-time.unread {
            color: var(--green-whatsapp);
            font-weight: 500;
        }

        .contact-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-msg {
            font-size: 14px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            padding-right: 10px;
        }

        .unread-badge {
            background: var(--green-whatsapp);
            color: white;
            font-size: 11px;
            font-weight: bold;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .human-badge {
            color: #f59e0b;
            font-weight: bold;
            font-size: 11px;
            margin-right: 4px;
        }

        /* ================= CHAT AREA ================= */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-chat);
            position: relative;
            z-index: 10;
        }

        .chat-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('https://static.whatsapp.net/rsrc.php/v3/yl/r/gi_DcqO4rcy.png');
            background-repeat: repeat;
            opacity: 0.4;
            z-index: 0;
        }

        .chat-header {
            height: 59px;
            background: var(--bg-panel);
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-left: 1px solid var(--border-color);
            z-index: 10;
        }

        .chat-header-info {
            flex: 1;
            margin-left: 15px;
            display: flex;
            flex-direction: column;
        }

        .chat-header-name {
            font-size: 16px;
            color: var(--text-primary);
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px 9%;
            display: flex;
            flex-direction: column;
            gap: 4px;
            z-index: 10;
        }

        .message-row {
            display: flex;
            width: 100%;
            margin-bottom: 2px;
        }

        .message-row.sent { justify-content: flex-end; }
        .message-row.received { justify-content: flex-start; }

        .bubble {
            max-width: 65%;
            padding: 6px 7px 8px 9px;
            border-radius: 7.5px;
            font-size: 14.2px;
            color: var(--text-primary);
            line-height: 19px;
            position: relative;
            box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        }

        .received .bubble {
            background: var(--bubble-in);
            border-top-left-radius: 0;
        }

        .sent .bubble {
            background: var(--bubble-out);
            border-top-right-radius: 0;
        }

        /* O Rabinho da bolha */
        .bubble::before {
            content: "";
            position: absolute;
            top: 0;
            width: 8px;
            height: 13px;
        }

        .received .bubble::before {
            left: -8px;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 8 13" xmlns="http://www.w3.org/2000/svg"><path fill="%23ffffff" d="M1.533 1H8v11.193l-6.467-8.625C-.526 2.156.042 1 1.812 1z"/></svg>');
        }

        .sent .bubble::before {
            right: -8px;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 8 13" xmlns="http://www.w3.org/2000/svg"><path fill="%23d9fdd3" d="M5.188 1H0v11.193l6.467-8.625C7.526 2.156 6.958 1 5.188 1z"/></svg>');
        }

        .msg-content {
            display: inline-block;
            word-wrap: break-word;
        }

        .msg-meta {
            display: flex;
            float: right;
            align-items: center;
            margin: 10px 0 -4px 10px;
            gap: 3px;
        }

        .msg-time {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* ================= BARRA DE DIGITAÇÃO ================= */
        .input-area {
            min-height: 62px;
            background: var(--bg-panel);
            padding: 10px 16px;
            display: flex;
            align-items: flex-end;
            gap: 16px;
            z-index: 10;
        }

        .input-icon {
            color: #54656f;
            padding-bottom: 8px;
            cursor: pointer;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
        }

        .input-wrapper {
            flex: 1;
            background: var(--bg-white);
            border-radius: 8px;
            min-height: 42px;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }

        .input-wrapper input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 15px;
            color: var(--text-primary);
            background: transparent;
        }

        .input-wrapper input::placeholder {
            color: var(--text-muted);
        }

        .input-icon:hover { color: var(--text-primary); }

        /* TELA VAZIA */
        .empty-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg-panel);
            border-bottom: 6px solid var(--green-whatsapp);
            text-align: center;
            z-index: 10;
        }

        /* SCROLLBARS */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(11,20,26,.2);
            border-radius: 3px;
        }
    </style>

    <div class="wa-container">
        <div class="app-wrapper">

            <div class="sidebar">
                
                <div class="header">
                    <div class="avatar-img">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="#fff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                    </div>
                    <div class="header-icons">
                        <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"></path></svg>
                    </div>
                </div>

                <div class="search-bar">
                    <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection" class="search-input-container">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M15.009 13.805h-.636l-.22-.219a5.184 5.184 0 0 0 1.256-3.386 5.207 5.207 0 1 0-5.207 5.208 5.183 5.183 0 0 0 3.385-1.255l.221.22v.635l4.004 3.999 1.194-1.195-3.997-4.007zm-4.608 0a3.606 3.606 0 1 1 0-7.212 3.606 3.606 0 0 1 0 7.212z"></path></svg>
                        <select name="connection_id" onchange="document.getElementById('form-connection').submit();">
                            @foreach($connections as $conn)
                                <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                    {{ $conn->name }} ({{ $conn->phone_number }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="contact-list custom-scrollbar">
                    @forelse($contacts as $contact)
                        @php 
                            $isHuman = $contact->is_human_mode;
                            $hasUnread = $contact->unread_count > 0;
                            $exibitionNumber = str_replace('@s.whatsapp.net', '', $contact->remote_jid);
                            $exibitionName = (!empty($contact->customer_name) && filter_var($contact->customer_name, FILTER_VALIDATE_EMAIL) === false) ? $contact->customer_name : $exibitionNumber;
                            $isActive = $activeChat == $contact->remote_jid;
                        @endphp
                        
                        <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" class="contact-item {{ $isActive ? 'active' : '' }}">
                            <div class="contact-avatar">
                                <div class="avatar-img">
                                    <svg viewBox="0 0 24 24" width="30" height="30" fill="#fff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                                </div>
                            </div>
                            <div class="contact-content">
                                <div class="contact-top">
                                    <span class="contact-name">{{ $exibitionName }}</span>
                                    <span class="contact-time {{ $hasUnread ? 'unread' : '' }}">
                                        {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                    </span>
                                </div>
                                <div class="contact-bottom">
                                    <span class="contact-msg">
                                        @if($isHuman)
                                            <span class="human-badge">[HUMANO]</span>
                                        @endif
                                        {{ $contact->last_message_text }}
                                    </span>
                                    @if($hasUnread)
                                        <div class="unread-badge">{{ $contact->unread_count }}</div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div style="padding: 2rem; text-align: center; color: var(--text-secondary); font-size: 14px;">
                            Nenhuma conversa encontrada.
                        </div>
                    @endforelse
                </div>

            </div>

            @if($activeChat)
                <div class="chat-area">
                    <div class="chat-bg"></div>

                    <div class="chat-header">
                        <div class="avatar-img">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="#fff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        </div>
                        <div class="chat-header-info">
                            <span class="chat-header-name">{{ str_replace('@s.whatsapp.net', '', $activeChat) }}</span>
                        </div>
                        <div class="header-icons">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M15.9 14.3H15l-.3-.3c1-1.1 1.6-2.7 1.6-4.3 0-3.7-3-6.7-6.7-6.7S3 6 3 9.7s3 6.7 6.7 6.7c1.6 0 3.2-.6 4.3-1.6l.3.3v.8l5.1 5.1 1.5-1.5-5-5.2zm-6.2 0c-2.6 0-4.6-2.1-4.6-4.6s2.1-4.6 4.6-4.6 4.6 2.1 4.6 4.6-2 4.6-4.6 4.6z"></path></svg>
                        </div>
                    </div>

                    <div class="messages-container custom-scrollbar" id="messagesBox">
                        @foreach($messages as $msg)
                            @php $isMe = $msg->from_me; @endphp
                            
                            <div class="message-row {{ $isMe ? 'sent' : 'received' }}">
                                <div class="bubble">
                                    <span class="msg-content">{!! nl2br(e($msg->message)) !!}</span>
                                    <div class="msg-meta">
                                        <span class="msg-time">{{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}</span>
                                        @if($isMe)
                                            <svg viewBox="0 0 16 16" width="16" height="11" fill="#53bdeb"><path d="M15.01 3.316l-.478-.372a.365.365 0 00-.51.063L8.666 9.879a.32.32 0 01-.484.033l-.358-.325a.32.32 0 00-.484.032l-.378.483a.418.418 0 00.036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 00-.064-.512zm-4.1 0l-.478-.372a.365.365 0 00-.51.063L4.566 9.879a.32.32 0 01-.484.033L1.891 7.769a.366.366 0 00-.515.006l-.423.433a.364.364 0 00.006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 00-.063-.51z"/></svg>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="input-area">
                        @csrf
                        <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                        
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M9.153 11.603c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962zm-3.204 1.362c-.026-.307-.131 5.218 6.063 5.551 6.066-.25 6.066-5.551 6.066-5.551-6.078 1.416-12.129 0-12.129 0zm11.363-1.108s-.669 1.959-5.051 1.959c-3.379 0-5.549-1.158-5.549-1.158-.059 4.851 4.544 5.378 5.549 5.378 5.813 0 5.051-6.179 5.051-6.179zm-3.078-1.455c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962z"></path></svg>
                        </div>
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z"></path></svg>
                        </div>
                        
                        <div class="input-wrapper">
                            <input type="text" name="message" placeholder="Mensagem" required autocomplete="off" autofocus>
                        </div>
                        
                        <button type="submit" class="input-icon">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path></svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="empty-chat">
                    <img src="https://static.whatsapp.net/rsrc.php/v3/y6/r/wa66cgRqSqf.png" alt="WhatsApp Web" style="width: 320px; margin-bottom: 30px; opacity: 0.9;">
                    <h1 style="font-size: 32px; font-weight: 300; color: #41525d; margin-bottom: 16px;">Arena WhatsApp Web</h1>
                    <p style="font-size: 14px; color: #667781; line-height: 20px; max-width: 560px;">
                        Envie e receba mensagens sem precisar manter seu celular conectado à internet.<br>
                        Selecione um contato na lateral para iniciar o atendimento.
                    </p>
                    <div style="margin-top: 30px; display: flex; align-items: center; color: #8696a0; font-size: 14px;">
                        <svg viewBox="0 0 10 12" width="10" height="12" fill="currentColor" style="margin-right: 6px;"><path d="M5 0C2.239 0 0 2.239 0 5c0 2.761 2.239 5 5 5s5-2.239 5-5c0-2.761-2.239-5-5-5zm0 8.75A3.75 3.75 0 1 1 5 1.25a3.75 3.75 0 0 1 0 7.5zm-.5-6v3.25h2v1h-3V2.75h1z"/></svg>
                        Protegido com a criptografia de ponta a ponta
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Faz a rolagem automática para baixo assim que a página carrega
        const box = document.getElementById('messagesBox');
        if(box) { box.scrollTop = box.scrollHeight; }
    </script>
</x-app-layout>