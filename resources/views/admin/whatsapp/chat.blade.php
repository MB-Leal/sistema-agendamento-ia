@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="height: calc(100vh - 100px);">
    <div class="row h-100 g-3">
        
        <div class="col-md-4 h-100 d-flex flex-column bg-white rounded shadow-sm border">
            
            <div class="p-3 border-bottom bg-light rounded-top">
                <label class="form-label text-muted small fw-bold">CONEXÃO ATIVA</label>
                <form method="GET" action="{{ route('admin.whatsapp.chat') }}" id="form-connection">
                    <select name="connection_id" class="form-select" onchange="document.getElementById('form-connection').submit();">
                        @foreach($connections as $conn)
                            <option value="{{ $conn->id }}" {{ $selectedConnectionId == $conn->id ? 'selected' : '' }}>
                                🏟️ {{ $conn->name }} ({{ $conn->phone_number }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="p-2 border-bottom bg-light">
                <input type="text" id="searchChat" class="form-control form-control-sm" placeholder="🔍 Pesquisar conversa pelo nome...">
            </div>

            <div class="flex-grow-1 overflow-auto" style="max-height: 70vh;">
                <div class="list-group list-group-flush" id="contactsList">
                    @foreach($contacts as $contact)
                        @php 
                            $isHuman = $contact->is_human_mode;
                            $hasUnread = $contact->unread_count > 0;
                        @endphp
                        <a href="?connection_id={{ $selectedConnectionId }}&chat={{ $contact->remote_jid }}" 
                           class="list-group-item list-group-item-action p-3 border-bottom contact-item {{ $activeChat == $contact->remote_jid ? 'active bg-light text-dark' : '' }}"
                           data-name="{{ strtolower($contact->customer_name ?? $contact->remote_jid) }}">
                            
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-truncate" style="max-width: 70%;">
                                    {{ $contact->customer_name ?? str_replace('@s.whatsapp.net', '', $contact->remote_jid) }}
                                </span>
                                <small class="text-muted text-end" style="font-size: 11px;">
                                    {{ \Carbon\Carbon::parse($contact->last_message_time)->format('H:i') }}
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-muted text-truncate mb-0 small flex-grow-1" style="max-width: 80%;">
                                    {{ $contact->last_message_text }}
                                </p>
                                
                                <div class="d-flex gap-1 align-items-center">
                                    @if($isHuman)
                                        <span class="badge bg-danger animate-pulse" style="font-size: 10px;">HUMANO</span>
                                    @endif

                                    @if($hasUnread)
                                        <span class="badge bg-success rounded-pill small">{{ $contact->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-8 h-100 d-flex flex-column bg-white rounded shadow-sm border">
            @if($activeChat)
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center rounded-top">
                    <div>
                        <h6 class="mb-0 fw-bold">💬 {{ str_replace('@s.whatsapp.net', '', $activeChat) }}</h6>
                        <small class="text-success font-monospace">Atendimento ativo</small>
                    </div>
                </div>

                <div class="flex-grow-1 p-3 overflow-auto bg-light" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); max-height: 62vh;" id="messagesBox">
                    @foreach($messages as $msg)
                        <div class="d-flex mb-2 {{ $msg->from_me ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-2 rounded shadow-sm position-relative" 
                                 style="max-width: 70%; {{ $msg->from_me ? 'background-color: #d9fdd3; color: #000;' : 'background-color: #ffffff; color: #000;' }}">
                                <p class="mb-1 style-message-text" style="white-space: pre-wrap;">{{ $msg->message }}</p>
                                <div class="text-end" style="font-size: 9px; color: #667781;">
                                    {{ \Carbon\Carbon::parse($msg->timestamp)->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3 border-top bg-white rounded-bottom">
                    <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="remote_jid" value="{{ $activeChat }}">
                        <input type="text" name="message" class="form-control" placeholder="Digite uma mensagem..." required autocomplete="off">
                        <button type="submit" class="btn btn-success px-4">
                            Enviar 🚀
                        </button>
                    </form>
                </div>
            @else
                <div class="m-auto text-center p-5 text-muted">
                    <div class="display-1 mb-3 text-muted" style="opacity: 0.3;">📱</div>
                    <h5>Arena Elizeu - Gestão Conversacional</h5>
                    <p class="small">Selecione uma conversa ao lado para visualizar e interagir com o cliente.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    // Rola o chat para o fim automaticamente ao abrir
    const box = document.getElementById('messagesBox');
    if(box) { box.scrollTop = box.scrollHeight; }

    // Pesquisa em tempo real na lista de contatos
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
    .animate-pulse {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); opacity: 0.7; }
        50% { transform: scale(1); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.7; }
    }
</style>
@endsection