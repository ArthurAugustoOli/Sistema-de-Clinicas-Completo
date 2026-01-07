<?php
/* Nome: chat.php | Caminho: /includes/chat.php */

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    exit;
}

// Incluir configuração do banco de dados
require_once __DIR__ . '/../config/config.php'; // Ajuste o caminho conforme necessário

// Determinar o nível de diretório atual
$currentFile = $_SERVER['PHP_SELF'];
$parts = explode('/', $currentFile);
$directoryDepth = count($parts) - 2; // -2 para ajustar ao padrão de diretórios

// Definir o caminho base de acordo com a profundidade do diretório
$baseUrl = '';
for ($i = 0; $i < $directoryDepth; $i++) {
    $baseUrl .= '../';
}

// Obter ID do usuário atual
$usuario_id = $_SESSION['id'];
$usuario_nome = $_SESSION['nome'];

// Função para obter lista de usuários para chat
function getUsuariosChat() {
    global $conn, $usuario_id;
    $usuarios = [];
    
    // Buscar usuários com quem o usuário atual já conversou
    $sql = "SELECT DISTINCT 
                u.id, 
                u.nome, 
                u.foto_perfil, 
                u.cargo,
                u.status,
                (SELECT MAX(m.data_envio) FROM mensagens m 
                 WHERE (m.remetente_id = u.id AND m.destinatario_id = ?) 
                    OR (m.remetente_id = ? AND m.destinatario_id = u.id)) as ultima_mensagem_data,
                (SELECT m.mensagem FROM mensagens m 
                 WHERE ((m.remetente_id = u.id AND m.destinatario_id = ?) 
                    OR (m.remetente_id = ? AND m.destinatario_id = u.id))
                 ORDER BY m.data_envio DESC LIMIT 1) as ultima_mensagem,
                (SELECT COUNT(*) FROM mensagens m 
                 WHERE m.remetente_id = u.id AND m.destinatario_id = ? AND m.lida = 0) as nao_lidas
            FROM usuarios u
            WHERE u.id != ? AND u.status = 'ativo'
            AND (
                EXISTS (SELECT 1 FROM mensagens m WHERE m.remetente_id = u.id AND m.destinatario_id = ?)
                OR EXISTS (SELECT 1 FROM mensagens m WHERE m.remetente_id = ? AND m.destinatario_id = u.id)
            )
            ORDER BY ultima_mensagem_data DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiiiiiii", $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        $stmt->close();
    }
    
    // Buscar outros usuários ativos que não estão na lista acima
    $sql_outros = "SELECT 
                    u.id, 
                    u.nome, 
                    u.foto_perfil, 
                    u.cargo,
                    u.status,
                    NULL as ultima_mensagem_data,
                    NULL as ultima_mensagem,
                    0 as nao_lidas
                FROM usuarios u
                WHERE u.id != ? AND u.status = 'ativo'
                AND NOT EXISTS (
                    SELECT 1 FROM mensagens m 
                    WHERE (m.remetente_id = u.id AND m.destinatario_id = ?) 
                       OR (m.remetente_id = ? AND m.destinatario_id = u.id)
                )
                ORDER BY u.nome";
    
    if ($stmt = $conn->prepare($sql_outros)) {
        $stmt->bind_param("iii", $usuario_id, $usuario_id, $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        $stmt->close();
    }
    
    return $usuarios;
}

// Função para contar mensagens não lidas
function contarMensagensNaoLidas() {
    global $conn, $usuario_id;
    $count = 0;
    
    $sql = "SELECT COUNT(*) as count FROM mensagens 
            WHERE destinatario_id = ? AND lida = 0";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'];
        }
        $stmt->close();
    }
    
    return $count;
}

$mensagens_nao_lidas = contarMensagensNaoLidas();
?>

<!-- Modal de Chat -->
<div class="modal fade" id="chatModalWindow" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chatModalLabel">Mensagens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="chat-container">
                    <!-- Sidebar de Contatos -->
                    <div class="chat-sidebar">
                        <div class="chat-search">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="chatSearch" placeholder="Buscar contatos...">
                            </div>
                        </div>
                        <div class="chat-contacts" id="chatContactsList">
                            <?php $usuarios = getUsuariosChat(); ?>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <div class="chat-contact-item" data-id="<?= $usuario['id'] ?>" data-nome="<?= htmlspecialchars($usuario['nome']) ?>">
                                        <div class="contact-avatar">
                                            <?php if (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil'])): ?>
                                                <img src="<?= $usuario['foto_perfil'] ?>" alt="<?= htmlspecialchars($usuario['nome']) ?>">
                                            <?php else: ?>
                                                <i class="bi bi-person-circle"></i>
                                            <?php endif; ?>
                                            <span class="status-indicator <?= $usuario['status'] == 'ativo' ? 'online' : 'offline' ?>"></span>
                                        </div>
                                        <div class="contact-info">
                                            <h6 class="contact-name"><?= htmlspecialchars($usuario['nome']) ?></h6>
                                            <p class="contact-last-message">
                                                <?= !empty($usuario['ultima_mensagem']) ? (strlen($usuario['ultima_mensagem']) > 30 ? substr(htmlspecialchars($usuario['ultima_mensagem']), 0, 30) . '...' : htmlspecialchars($usuario['ultima_mensagem'])) : '<span class="text-muted">Nenhuma mensagem</span>' ?>
                                            </p>
                                        </div>
                                        <div class="contact-meta">
                                            <?php if (!empty($usuario['ultima_mensagem_data'])): ?>
                                                <span class="last-time"><?= date('H:i', strtotime($usuario['ultima_mensagem_data'])) ?></span>
                                            <?php endif; ?>
                                            <?php if ($usuario['nao_lidas'] > 0): ?>
                                                <span class="unread-badge"><?= $usuario['nao_lidas'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-contacts">
                                    <div class="empty-icon">
                                        <i class="bi bi-chat-left-dots"></i>
                                    </div>
                                    <p>Nenhum contato disponível</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Área de Conversa -->
                    <div class="chat-area">
                        <div class="chat-header" id="chatHeader">
                            <div class="chat-header-info">
                                <div class="chat-contact-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="chat-contact-details">
                                    <h6 class="chat-contact-name">Selecione um contato</h6>
                                    <p class="chat-contact-status">Inicie uma conversa</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <div class="chat-start-message">
                                <div class="chat-start-icon">
                                    <i class="bi bi-chat-dots"></i>
                                </div>
                                <h5>Bem-vindo ao Chat</h5>
                                <p>Selecione um contato para iniciar uma conversa</p>
                            </div>
                        </div>
                        
                        <div class="chat-input-area" id="chatInputArea" style="display: none;">
                            <form id="chatForm">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="chatMessageInput" placeholder="Digite sua mensagem..." autocomplete="off">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Chat Styles */
.chat-container {
    display: flex;
    height: 500px;
    border-radius: 10px;
    overflow: hidden;
    background-color: #fff;
}

/* Chat Sidebar */
.chat-sidebar {
    width: 280px;
    border-right: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    background-color: #f8f9fa;
}

.chat-search {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.chat-search .input-group-text,
.chat-search .form-control {
    border-color: #e0e0e0;
    box-shadow: none;
}

.chat-search .form-control:focus {
    border-color: #4e73df;
}

.chat-contacts {
    flex-grow: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.chat-contacts::-webkit-scrollbar {
    width: 5px;
}

.chat-contacts::-webkit-scrollbar-track {
    background: transparent;
}

.chat-contacts::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 20px;
}

.chat-contact-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-contact-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.chat-contact-item.active {
    background-color: rgba(78, 115, 223, 0.1);
    border-left: 3px solid #4e73df;
}

.contact-avatar {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 12px;
    background-color: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.contact-avatar i {
    font-size: 24px;
    color: #6c757d;
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.status-indicator.online {
    background-color: #1cc88a;
}

.status-indicator.offline {
    background-color: #858796;
}

.contact-info {
    flex-grow: 1;
    min-width: 0;
}

.contact-name {
    margin: 0 0 3px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-last-message {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: 10px;
    min-width: 40px;
}

.last-time {
    font-size: 11px;
    color: #adb5bd;
    margin-bottom: 5px;
}

.unread-badge {
    background-color: #4e73df;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-contacts {
    padding: 30px 15px;
    text-align: center;
    color: #6c757d;
}

.empty-icon {
    font-size: 40px;
    color: #dee2e6;
    margin-bottom: 10px;
}

/* Chat Area */
.chat-area {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    background-color: #fff;
}

.chat-header {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    background-color: #f8f9fa;
}

.chat-header-info {
    display: flex;
    align-items: center;
}

.chat-contact-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 12px;
    background-color: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-contact-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-contact-avatar i {
    font-size: 24px;
    color: #6c757d;
}

.chat-contact-details {
    flex-grow: 1;
}

.chat-contact-name {
    margin: 0 0 3px;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.chat-contact-status {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}

.chat-messages {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.chat-messages::-webkit-scrollbar {
    width: 5px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 20px;
}

.chat-start-message {
    margin: auto;
    text-align: center;
    color: #6c757d;
    padding: 30px;
}

.chat-start-icon {
    font-size: 50px;
    color: #4e73df;
    margin-bottom: 15px;
    opacity: 0.5;
}

.chat-start-message h5 {
    font-weight: 600;
    margin-bottom: 10px;
}

.chat-message {
    max-width: 70%;
    margin-bottom: 15px;
    clear: both;
    position: relative;
    animation: fadeIn 0.3s ease;
}

.chat-message.outgoing {
    float: right;
    background-color: #4e73df;
    color: #fff;
    border-radius: 15px 15px 0 15px;
    padding: 10px 15px;
    margin-left: auto;
}

.chat-message.incoming {
    float: left;
    background-color: #e9ecef;
    color: #333;
    border-radius: 15px 15px 15px 0;
    padding: 10px 15px;
    margin-right: auto;
}

.message-content {
    word-wrap: break-word;
}

.message-time {
    font-size: 10px;
    margin-top: 5px;
    text-align: right;
    opacity: 0.8;
}

.message-date-separator {
    text-align: center;
    margin: 15px 0;
    clear: both;
    width: 100%;
}

.message-date {
    background-color: rgba(0, 0, 0, 0.1);
    color: #6c757d;
    font-size: 11px;
    padding: 5px 10px;
    border-radius: 15px;
    display: inline-block;
}

.chat-input-area {
    padding: 15px;
    border-top: 1px solid #f0f0f0;
    background-color: #fff;
}

.chat-input-area .form-control {
    border-radius: 20px;
    padding-left: 15px;
    border-color: #e0e0e0;
}

.chat-input-area .form-control:focus {
    box-shadow: none;
    border-color: #4e73df;
}

.chat-input-area .btn {
    border-radius: 20px;
    padding: 0.375rem 1rem;
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsividade */
@media (max-width: 767.98px) {
    .chat-container {
        flex-direction: column;
        height: 70vh;
    }
    
    .chat-sidebar {
        width: 100%;
        height: 40%;
        border-right: none;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .chat-area {
        height: 60%;
    }
    
    .modal-dialog {
        margin: 0.5rem;
        max-width: none;
    }
    
    .modal-content {
        height: calc(100vh - 1rem);
    }
    
    .chat-message {
        max-width: 85%;
    }
}

/* Modo de visualização de mensagens em tela cheia para mobile */
.chat-fullscreen-mode .chat-sidebar {
    display: none;
}

.chat-fullscreen-mode .chat-area {
    height: 100%;
}

.chat-back-button {
    display: none;
    margin-right: 10px;
    background: none;
    border: none;
    color: #4e73df;
    font-size: 20px;
    cursor: pointer;
}

@media (max-width: 767.98px) {
    .chat-fullscreen-mode .chat-back-button {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do chat
    const chatContactsList = document.getElementById('chatContactsList');
    const chatMessages = document.getElementById('chatMessages');
    const chatHeader = document.getElementById('chatHeader');
    const chatInputArea = document.getElementById('chatInputArea');
    const chatForm = document.getElementById('chatForm');
    const chatMessageInput = document.getElementById('chatMessageInput');
    const chatSearch = document.getElementById('chatSearch');
    const chatContainer = document.querySelector('.chat-container');
    
    // Variáveis globais
    let currentContactId = null;
    let currentContactName = null;
    let lastMessageDate = null;
    let messagePollingInterval = null;
    
    // Adicionar botão de voltar para mobile
    const backButton = document.createElement('button');
    backButton.className = 'chat-back-button';
    backButton.innerHTML = '<i class="bi bi-arrow-left"></i>';
    backButton.addEventListener('click', function() {
        chatContainer.classList.remove('chat-fullscreen-mode');
    });
    
    // Inserir o botão de voltar no cabeçalho do chat
    const chatHeaderInfo = chatHeader.querySelector('.chat-header-info');
    chatHeader.insertBefore(backButton, chatHeaderInfo);
    
    // Função para buscar contatos
    chatSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const contactItems = chatContactsList.querySelectorAll('.chat-contact-item');
        
        contactItems.forEach(item => {
            const contactName = item.getAttribute('data-nome').toLowerCase();
            if (contactName.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Função para carregar mensagens de um contato
    function loadMessages(contactId, contactName) {
        currentContactId = contactId;
        currentContactName = contactName;
        
        // Atualizar cabeçalho do chat
        updateChatHeader(contactId, contactName);
        
        // Mostrar área de input
        chatInputArea.style.display = 'block';
        
        // Limpar mensagens anteriores
        chatMessages.innerHTML = '<div class="loading-messages"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
        
        // Buscar mensagens
        fetch('<?= $baseUrl ?>api/chat/get_messages.php?contact_id=' + contactId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                    
                    // Marcar mensagens como lidas
                    markMessagesAsRead(contactId);
                    
                    // Atualizar contador de mensagens não lidas
                    updateUnreadBadge(contactId, 0);
                    
                    // Iniciar polling de novas mensagens
                    startMessagePolling(contactId);
                } else {
                    chatMessages.innerHTML = '<div class="chat-start-message"><div class="chat-start-icon"><i class="bi bi-exclamation-circle"></i></div><h5>Erro</h5><p>' + data.message + '</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar mensagens:', error);
                chatMessages.innerHTML = '<div class="chat-start-message"><div class="chat-start-icon"><i class="bi bi-exclamation-circle"></i></div><h5>Erro</h5><p>Não foi possível carregar as mensagens. Tente novamente mais tarde.</p></div>';
            });
            
        // Em dispositivos móveis, mudar para modo de tela cheia
        if (window.innerWidth < 768) {
            chatContainer.classList.add('chat-fullscreen-mode');
        }
    }
    
    // Função para atualizar o cabeçalho do chat
    function updateChatHeader(contactId, contactName) {
        // Buscar informações do contato
        fetch('<?= $baseUrl ?>api/chat/get_contact_info.php?contact_id=' + contactId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contact = data.contact;
                    let avatarHtml = '';
                    
                    if (contact.foto_perfil && contact.foto_perfil !== '') {
                        avatarHtml = `<img src="${contact.foto_perfil}" alt="${contact.nome}">`;
                    } else {
                        avatarHtml = '<i class="bi bi-person-circle"></i>';
                    }
                    
                    const statusText = contact.status === 'ativo' ? 'Online' : 'Offline';
                    const statusClass = contact.status === 'ativo' ? 'text-success' : 'text-secondary';
                    
                    chatHeader.querySelector('.chat-contact-avatar').innerHTML = avatarHtml;
                    chatHeader.querySelector('.chat-contact-name').textContent = contact.nome;
                    chatHeader.querySelector('.chat-contact-status').innerHTML = `<span class="${statusClass}">● ${statusText}</span> - ${contact.cargo}`;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar informações do contato:', error);
            });
    }
    
    // Função para exibir mensagens
    function displayMessages(messages) {
        chatMessages.innerHTML = '';
        lastMessageDate = null;
        
        if (messages.length === 0) {
            chatMessages.innerHTML = '<div class="chat-start-message"><div class="chat-start-icon"><i class="bi bi-chat"></i></div><h5>Nenhuma mensagem</h5><p>Inicie uma conversa com ' + currentContactName + '</p></div>';
            return;
        }
        
        messages.forEach(message => {
            // Verificar se precisa adicionar separador de data
            const messageDate = new Date(message.data_envio).toLocaleDateString();
            if (lastMessageDate !== messageDate) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'message-date-separator';
                dateSeparator.innerHTML = `<span class="message-date">${messageDate}</span>`;
                chatMessages.appendChild(dateSeparator);
                lastMessageDate = messageDate;
            }
            
            // Criar elemento de mensagem
            const messageElement = document.createElement('div');
            messageElement.className = 'chat-message ' + (message.remetente_id == <?= $usuario_id ?> ? 'outgoing' : 'incoming');
            
            const messageTime = new Date(message.data_envio).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageElement.innerHTML = `
                <div class="message-content">${message.mensagem}</div>
                <div class="message-time">${messageTime}</div>
            `;
            
            chatMessages.appendChild(messageElement);
        });
        
        // Rolar para a última mensagem
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Função para marcar mensagens como lidas
    function markMessagesAsRead(contactId) {
        fetch('<?= $baseUrl ?>api/chat/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `contact_id=${contactId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar contador de mensagens não lidas no botão do chat
                updateChatBadge();
            }
        })
        .catch(error => console.error('Erro ao marcar mensagens como lidas:', error));
    }
    
    // Função para atualizar o badge de mensagens não lidas de um contato
    function updateUnreadBadge(contactId, count) {
        const contactItem = chatContactsList.querySelector(`.chat-contact-item[data-id="${contactId}"]`);
        if (contactItem) {
            let badge = contactItem.querySelector('.unread-badge');
            
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.textContent = count;
                    contactItem.querySelector('.contact-meta').appendChild(badge);
                }
            } else if (badge) {
                badge.remove();
            }
        }
    }
    
    // Função para atualizar o badge de mensagens não lidas no botão do chat
    function updateChatBadge() {
        fetch('<?= $baseUrl ?>api/chat/check_unread.php')
            .then(response => response.json())
            .then(data => {
                const chatBadge = document.querySelector('.chat-badge');
                if (data.count > 0) {
                    chatBadge.textContent = data.count;
                    chatBadge.classList.remove('d-none');
                } else {
                    chatBadge.classList.add('d-none');
                }
            })
            .catch(error => console.error('Erro ao verificar mensagens não lidas:', error));
    }
    
    // Função para iniciar polling de novas mensagens
    function startMessagePolling(contactId) {
        // Limpar intervalo anterior se existir
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
        }
        
        // Iniciar novo intervalo
        messagePollingInterval = setInterval(() => {
            if (currentContactId) {
                fetch('<?= $baseUrl ?>api/chat/get_new_messages.php?contact_id=' + contactId + '&last_check=' + new Date().toISOString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.messages.length > 0) {
                            // Adicionar novas mensagens
                            appendNewMessages(data.messages);
                            
                            // Marcar como lidas
                            markMessagesAsRead(contactId);
                        }
                    })
                    .catch(error => console.error('Erro ao verificar novas mensagens:', error));
            }
        }, 5000); // Verificar a cada 5 segundos
    }
    
    // Função para adicionar novas mensagens
    function appendNewMessages(messages) {
        messages.forEach(message => {
            // Verificar se precisa adicionar separador de data
            const messageDate = new Date(message.data_envio).toLocaleDateString();
            if (lastMessageDate !== messageDate) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'message-date-separator';
                dateSeparator.innerHTML = `<span class="message-date">${messageDate}</span>`;
                chatMessages.appendChild(dateSeparator);
                lastMessageDate = messageDate;
            }
            
            // Criar elemento de mensagem
            const messageElement = document.createElement('div');
            messageElement.className = 'chat-message ' + (message.remetente_id == <?= $usuario_id ?> ? 'outgoing' : 'incoming');
            
            const messageTime = new Date(message.data_envio).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageElement.innerHTML = `
                <div class="message-content">${message.mensagem}</div>
                <div class="message-time">${messageTime}</div>
            `;
            
            chatMessages.appendChild(messageElement);
            
            // Rolar para a última mensagem
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
    
    // Evento de clique nos contatos
    chatContactsList.addEventListener('click', function(e) {
        const contactItem = e.target.closest('.chat-contact-item');
        if (contactItem) {
            // Remover classe ativa de todos os contatos
            const allContacts = this.querySelectorAll('.chat-contact-item');
            allContacts.forEach(item => item.classList.remove('active'));
            
            // Adicionar classe ativa ao contato clicado
            contactItem.classList.add('active');
            
            // Carregar mensagens do contato
            const contactId = contactItem.getAttribute('data-id');
            const contactName = contactItem.getAttribute('data-nome');
            loadMessages(contactId, contactName);
        }
    });
    
    // Enviar mensagem
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = chatMessageInput.value.trim();
        if (message && currentContactId) {
            // Desabilitar input enquanto envia
            chatMessageInput.disabled = true;
            
            // Enviar mensagem para o servidor
            fetch('<?= $baseUrl ?>api/chat/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `destinatario_id=${currentContactId}&mensagem=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpar input
                    chatMessageInput.value = '';
                    
                    // Adicionar mensagem à conversa
                    const newMessage = {
                        id: data.message_id,
                        remetente_id: <?= $usuario_id ?>,
                        destinatario_id: currentContactId,
                        mensagem: message,
                        data_envio: data.timestamp,
                        lida: 0
                    };
                    
                    appendNewMessages([newMessage]);
                    
                    // Atualizar última mensagem no contato
                    updateContactLastMessage(currentContactId, message, data.timestamp);
                } else {
                    alert('Erro ao enviar mensagem: ' + data.message);
                }
                
                // Reabilitar input
                chatMessageInput.disabled = false;
                chatMessageInput.focus();
            })
            .catch(error => {
                console.error('Erro ao enviar mensagem:', error);
                alert('Erro ao enviar mensagem. Tente novamente.');
                chatMessageInput.disabled = false;
            });
        }
    });
    
    // Função para atualizar a última mensagem de um contato na lista
    function updateContactLastMessage(contactId, message, timestamp) {
        const contactItem = chatContactsList.querySelector(`.chat-contact-item[data-id="${contactId}"]`);
        if (contactItem) {
            // Atualizar texto da última mensagem
            const lastMessageElement = contactItem.querySelector('.contact-last-message');
            lastMessageElement.textContent = message.length > 30 ? message.substring(0, 30) + '...' : message;
            
            // Atualizar hora
            const timeElement = contactItem.querySelector('.last-time');
            const messageTime = new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            if (timeElement) {
                timeElement.textContent = messageTime;
            } else {
                const metaElement = contactItem.querySelector('.contact-meta');
                const newTimeElement = document.createElement('span');
                newTimeElement.className = 'last-time';
                newTimeElement.textContent = messageTime;
                metaElement.prepend(newTimeElement);
            }
            
            // Mover contato para o topo da lista
            const parent = contactItem.parentNode;
            parent.prepend(contactItem);
        }
    }
    
    // Limpar polling ao fechar o modal
    document.getElementById('chatModalWindow').addEventListener('hidden.bs.modal', function() {
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
            messagePollingInterval = null;
        }
        
        // Resetar estado
        currentContactId = null;
        currentContactName = null;
        
        // Limpar área de mensagens
        chatMessages.innerHTML = `
            <div class="chat-start-message">
                <div class="chat-start-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h5>Bem-vindo ao Chat</h5>
                <p>Selecione um contato para iniciar uma conversa</p>
            </div>
        `;
        
        // Esconder área de input
        chatInputArea.style.display = 'none';
        
        // Resetar cabeçalho
        chatHeader.querySelector('.chat-contact-name').textContent = 'Selecione um contato';
        chatHeader.querySelector('.chat-contact-status').textContent = 'Inicie uma conversa';
        chatHeader.querySelector('.chat-contact-avatar').innerHTML = '<i class="bi bi-person-circle"></i>';
        
        // Remover classe ativa de todos os contatos
        const allContacts = chatContactsList.querySelectorAll('.chat-contact-item');
        allContacts.forEach(item => item.classList.remove('active'));
        
        // Remover modo tela cheia
        chatContainer.classList.remove('chat-fullscreen-mode');
    });
    
    // Verificar mensagens não lidas ao abrir o modal
    document.getElementById('chatModalWindow').addEventListener('shown.bs.modal', function() {
        updateChatBadge();
    });
    
    // Ajustar layout ao redimensionar a janela
    window.addEventListener('resize', function() {
        if (currentContactId) {
            if (window.innerWidth < 768) {
                chatContainer.classList.add('chat-fullscreen-mode');
            } else {
                chatContainer.classList.remove('chat-fullscreen-mode');
            }
        }
    });
});
</script>

