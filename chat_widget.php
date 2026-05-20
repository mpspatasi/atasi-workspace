<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>

<style>
    /* Pattern Doodle Agrikultur (Abu-abu Kebiruan) */
    .bg-agri-doodle {
        background-color: #ffffff;
        background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%2364748b' stroke-width='1.5' stroke-opacity='0.12' stroke-linecap='round' stroke-linejoin='round'%3E%3C!-- Daun 1 --%3E%3Cpath d='M15 15 Q25 5 35 15 Q25 25 15 15 Z'/%3E%3Cpath d='M15 15 L35 35'/%3E%3C!-- Tunas --%3E%3Cpath d='M60 70 V50 C50 50 50 60 55 65'/%3E%3Cpath d='M60 60 C70 60 70 50 65 45'/%3E%3C!-- Rintik Air --%3E%3Cpath d='M65 15 C65 20 60 25 60 25 C60 25 55 20 55 15 C55 10 60 10 60 10 C60 10 65 10 65 15 Z'/%3E%3C!-- Traktor/Garis Tanah --%3E%3Cpath d='M10 65 L30 65 M15 70 L25 70'/%3E%3Ccircle cx='15' cy='65' r='3'/%3E%3Ccircle cx='25' cy='65' r='5'/%3E%3C!-- Aksen Titik --%3E%3Ccircle cx='40' cy='40' r='1'/%3E%3Ccircle cx='75' cy='35' r='1'/%3E%3C/g%3E%3C/svg%3E");
    }
    
    /* Penyesuaian tampilan Emoji Picker agar match dengan UI */
    emoji-picker {
        --background: #ffffff;
        --border-color: #e2e8f0;
        --indicator-color: #4A85F6;
        --input-border-color: #cbd5e1;
        --input-padding: 0.5rem;
        width: 280px;
        height: 320px;
    }
</style>

<div id="chatPanel" class="fixed top-20 right-6 w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-300 origin-top-right scale-0 opacity-0 z-[500]" style="height: 520px;">
    
    <div class="bg-white px-5 py-4 border-b border-slate-100 flex justify-between items-center shrink-0 relative z-[600]">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#4A85F6] border border-blue-100 shadow-sm text-xl">
                    😊
                </div>
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-sm">TeamTalk</h3>
                <p class="text-[11px] text-slate-500">Online</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-slate-400">
            <button onclick="toggleSearch()" class="hover:text-[#4A85F6] transition focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <button onclick="startVoiceCall()" class="hover:text-[#4A85F6] transition focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            </button>
            <div class="w-px h-5 bg-slate-200 mx-1"></div>
            <button onclick="toggleChat()" class="hover:text-red-500 transition focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <div id="searchBar" class="hidden bg-slate-50 px-4 py-2 border-b border-slate-100 flex items-center gap-2 relative z-[550]">
        <input type="text" id="searchInput" onkeyup="searchChat()" placeholder="Cari pesan..." class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-[#4A85F6]">
        <button onclick="toggleSearch()" class="text-xs font-bold text-slate-500 hover:text-slate-800">Batal</button>
    </div>

    <div id="fancyToast" class="absolute top-16 left-1/2 transform -translate-x-1/2 -translate-y-10 opacity-0 bg-white/95 backdrop-blur-sm rounded-xl shadow-[0_10px_25px_-5px_rgba(0,0,0,0.15)] border border-slate-100 p-2.5 flex items-center gap-3 transition-all duration-300 z-[1000] pointer-events-none min-w-[240px]">
        <div id="fancyToastIcon" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"></div>
        <div class="flex flex-col">
            <span id="fancyToastTitle" class="text-xs font-bold text-slate-800"></span>
            <span id="fancyToastText" class="text-[10px] text-slate-500 mt-0.5 leading-tight"></span>
        </div>
    </div>

    <div id="chatBox" class="flex-1 p-5 overflow-y-auto flex flex-col relative z-10 bg-agri-doodle scrollbar-hide pb-6">
        <div class="text-center text-xs text-slate-400 mt-2 bg-white/60 inline-block px-3 py-1 rounded-full mx-auto block w-max">Memuat obrolan...</div>
    </div>

    <div id="replyPreview" class="hidden px-4 py-2.5 bg-white border-t border-slate-100 flex justify-between items-center text-xs relative z-[550] shadow-inner">
        <div class="flex-1 overflow-hidden border-l-4 border-[#3E54D3] pl-3">
            <div id="replyPreviewSender" class="font-bold text-[#3E54D3] mb-0.5 text-[11px]"></div>
            <div id="replyPreviewText" class="text-slate-500 truncate text-[10px]"></div>
        </div>
        <button onclick="cancelReply(); cancelEdit();" class="text-slate-400 hover:text-red-500 ml-3 p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="p-4 bg-[#EAF0F9] border-t border-blue-50 flex items-center gap-3 shrink-0 relative z-[600]">
        
        <div class="relative flex items-center justify-center shrink-0" id="emojiContainer">
            <button type="button" onclick="toggleEmojiPicker(event)" class="text-blue-400 hover:text-[#4A85F6] transition focus:outline-none flex items-center justify-center p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
            
            <div id="emojiPickerBox" class="hidden absolute bottom-14 left-0 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[700] overflow-hidden">
                <emoji-picker class="light"></emoji-picker>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl flex items-center px-4 py-2 shadow-sm relative h-11 border border-slate-100">
            <input type="text" id="chatInput" class="flex-1 bg-transparent border-none focus:ring-0 text-sm outline-none text-slate-700 w-full placeholder-slate-400" autocomplete="off" placeholder="Tulis pesan..." onkeypress="if(event.key === 'Enter') sendChat()">
        </div>
        
        <button id="sendBtn" onclick="sendChat()" class="w-11 h-11 bg-[#4A85F6] hover:bg-blue-600 text-white rounded-full flex items-center justify-center transition shrink-0 shadow-md focus:outline-none">
            <svg id="sendIcon" class="w-5 h-5 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
        </button>
    </div>

    <div id="voiceCallOverlay" class="hidden absolute inset-0 bg-[#4A85F6] z-[900] flex flex-col items-center justify-center text-white">
        <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-4 animate-pulse">
            <div class="w-16 h-16 bg-white/40 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            </div>
        </div>
        <h2 class="text-lg font-bold">Memanggil Tim ATASI...</h2>
        <p class="text-xs text-blue-100 mt-1 mb-8">Berdering</p>
        <button onclick="endVoiceCall()" class="w-14 h-14 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center shadow-lg transition">
            <svg class="w-6 h-6 transform rotate-[135deg]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
        </button>
    </div>

    <div id="chatConfirmModal" class="absolute inset-0 z-[2000] hidden flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
        <div id="chatConfirmBox" class="bg-white p-6 rounded-3xl shadow-2xl w-full max-w-[260px] text-center relative z-[2001] transform scale-95 opacity-0 transition-all duration-200">
            <div id="chatConfirmIcon" class="w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4"></div>
            <h4 class="font-bold text-slate-800 mb-2" id="chatConfirmTitle">Konfirmasi</h4>
            <p class="text-[11px] text-slate-500 mb-6 leading-relaxed" id="chatConfirmText">Apakah Anda yakin?</p>
            <div class="flex gap-3">
                <button onclick="closeConfirmModal()" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">Batal</button>
                <button id="chatConfirmBtn" onclick="executeActionChat()" class="flex-1 py-2 text-white rounded-xl text-xs font-bold shadow-md transition">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // VARIABEL GLOBAL & INISIALISASI
    // ==========================================
    const chatPanel = document.getElementById('chatPanel');
    const chatBox = document.getElementById('chatBox');
    const chatInput = document.getElementById('chatInput');
    const currentUser = '<?= isset($_SESSION['nama_lengkap']) ? addslashes($_SESSION['nama_lengkap']) : '' ?>';
    const currentInitial = '<?= isset($_SESSION['inisial']) ? addslashes($_SESSION['inisial']) : 'Me' ?>';
    
    let lastChatId = 0;
    let lastReadId = 0;
    let isChatOpen = false;
    let chatInterval;
    let editingChatId = null; 
    let replyingChatId = null;
    let pendingAction = null;
    let pendingChatId = null;
    let callTimeout = null;

    // Inisialisasi Event Listener Emoji Picker Native
    document.querySelector('emoji-picker').addEventListener('emoji-click', event => {
        chatInput.value += event.detail.unicode;
        chatInput.focus();
    });

    // ==========================================
    // FUNGSI TOGGLE DAN FETCH CHAT
    // ==========================================
    function toggleChat() {
        isChatOpen = !isChatOpen;
        const badge = document.getElementById('chatBadge');

        if (isChatOpen) {
            chatPanel.classList.remove('scale-0', 'opacity-0');
            chatPanel.classList.add('scale-100', 'opacity-100');
            lastReadId = lastChatId; 
            if(badge) { badge.classList.add('hidden'); badge.innerText = '0'; }
            chatInput.focus();
            scrollToBottom();
            fetchChat();
            chatInterval = setInterval(fetchChat, 3000); 
        } else {
            chatPanel.classList.remove('scale-100', 'opacity-100');
            chatPanel.classList.add('scale-0', 'opacity-0');
            clearInterval(chatInterval); 
            cancelEdit();
            cancelReply();
            closeConfirmModal(); 
            document.getElementById('searchBar').classList.add('hidden');
            document.getElementById('emojiPickerBox').classList.add('hidden');
        }
    }

    async function fetchChat() {
        try {
            const response = await fetch('ajax_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'fetch' })
            });
            const res = await response.json();
            if (res.status === 'success') {
                renderMessages(res.data);
            }
        } catch (err) { console.error('Gagal memuat chat'); }
    }

    // ==========================================
    // RENDERER ULTIMATE: COMPACT & STACKING
    // ==========================================
    function renderMessages(data) {
        if(data.length === 0) {
            chatBox.innerHTML = '<div class="text-center text-xs text-slate-400 mt-2 bg-white/60 inline-block px-3 py-1 rounded-full mx-auto block w-max">Belum ada obrolan.</div>';
            return;
        }

        let html = `
        <div class="flex items-center justify-center my-4 relative z-0">
            <div class="h-px bg-slate-200 flex-1 opacity-50"></div>
            <span class="px-3 text-[10px] text-slate-500 font-bold uppercase tracking-wider bg-white/80 rounded-full mx-2 py-0.5 shadow-sm">Obrolan Dimulai</span>
            <div class="h-px bg-slate-200 flex-1 opacity-50"></div>
        </div>`;
        
        let hasNewData = false;
        let unreadCount = 0;
        let lastSender = null;

        data.forEach((msg) => {
            const msgId = parseInt(msg.id);
            if (msgId > lastChatId) { lastChatId = msgId; hasNewData = true; }
            if (!isChatOpen && msgId > lastReadId && msg.sender !== currentUser) { unreadCount++; }

            const isMe = msg.sender === currentUser;
            const timeRaw = new Date(msg.created_at);
            const timeStr = timeRaw.getHours().toString().padStart(2, '0') + ':' + timeRaw.getMinutes().toString().padStart(2, '0');

            // Cek apakah pesan bertumpuk dari orang yang sama
            let isSameSender = (lastSender === msg.sender);
            lastSender = msg.sender;
            let marginTop = isSameSender ? 'mt-1' : 'mt-4';

            let tickSvg = '';
            if (isMe && msg.is_retracted == 0) {
                if (msg.ticks === 2) {
                    tickSvg = `<svg class="w-3.5 h-3.5 text-blue-300 inline-block ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7 M1 13l4 4L15 7"/></svg>`;
                } else {
                    tickSvg = `<svg class="w-3.5 h-3.5 text-blue-200 inline-block ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;
                }
            }

            const editLabel = (msg.is_edited == 1 && msg.is_retracted == 0) ? `<span class="italic text-[8px] opacity-70 ml-1">(Diedit)</span>` : '';

            let replyBlock = '';
            if (msg.reply_to && msg.reply_sender) {
                let repText = msg.reply_retracted == 1 ? 'Pesan telah ditarik' : msg.reply_message;
                if (isMe && msg.is_retracted == 0) {
                    replyBlock = `
                    <div class="bg-black/10 rounded border-l-2 border-blue-200 px-2 py-1 mb-1.5 text-[11px]">
                        <div class="font-bold text-blue-100 mb-0.5 truncate leading-none">${msg.reply_sender}</div>
                        <div class="text-white opacity-90 truncate line-clamp-1 leading-tight">${repText}</div>
                    </div>`;
                } else if (!isMe && msg.is_retracted == 0) {
                    replyBlock = `
                    <div class="bg-slate-100/60 rounded border-l-2 border-[#4A85F6] px-2 py-1 mb-1.5 text-[11px]">
                        <div class="font-bold text-[#4A85F6] mb-0.5 truncate leading-none">${msg.reply_sender}</div>
                        <div class="text-slate-500 truncate line-clamp-1 leading-tight">${repText}</div>
                    </div>`;
                }
            }

            let rawMessage = msg.message;
            let safeMsgForJS = rawMessage.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, '\\n');
            let safeSenderForJS = msg.sender.replace(/'/g, "\\'");

            // Pesan Compact
            let messageContent = `<span class="leading-snug text-[13px] whitespace-pre-wrap break-words inline-block">${rawMessage}</span>`;
            
            let bubbleClass = isMe ? 'bg-[#4A85F6] text-white' : 'bg-white border border-slate-100 text-slate-700';
            let timeClass = isMe ? 'text-blue-100' : 'text-slate-400';
            
            if (msg.is_retracted == 1) {
                messageContent = `<span class="italic text-[13px] text-slate-400 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg> Pesan ini telah ditarik</span>`;
                tickSvg = ''; 
                bubbleClass = 'bg-white/80 backdrop-blur-sm border border-slate-200 text-slate-500';
                timeClass = 'text-slate-400';
                replyBlock = ''; 
            }

            // HTML Tombol Panah Bawah & Menu (Posisi di samping)
            let menuBtnHtml = `
            <div class="relative flex items-center shrink-0">
                <button onclick="toggleMenu(${msg.id})" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-[#4A85F6] focus:outline-none p-1 transition bg-white/70 rounded-full backdrop-blur-sm shadow-sm border border-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div id="menu_${msg.id}" class="chat-menu hidden absolute ${isMe ? 'right-0' : 'left-0'} top-full mt-1 w-36 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-[100] text-sm overflow-hidden">
                    ${msg.is_retracted == 0 ? `
                    <button onclick="replyMode(${msg.id}, '${safeSenderForJS}', '${safeMsgForJS}')" class="w-full flex items-center px-4 py-2 hover:bg-[#EAF0F9] text-[#4A85F6] font-medium transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg> Balas
                    </button>
                    <button onclick="copyToClipboard('${safeMsgForJS}')" class="w-full flex items-center px-4 py-2 hover:bg-[#EAF0F9] text-[#4A85F6] font-medium transition border-t border-slate-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Salin
                    </button>
                    ${isMe ? `
                    <button onclick="editMode(${msg.id}, '${safeMsgForJS}')" class="w-full flex items-center px-4 py-2 hover:bg-[#EAF0F9] text-[#4A85F6] font-medium transition border-t border-slate-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Edit
                    </button>
                    <button onclick="openConfirmModal('retract', ${msg.id})" class="w-full flex items-center px-4 py-2 hover:bg-[#EAF0F9] text-[#4A85F6] font-medium transition border-t border-slate-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg> Tarik
                    </button>` : ''}` : ''}
                    <button onclick="openConfirmModal('delete', ${msg.id})" class="w-full flex items-center px-4 py-2 hover:bg-red-50 text-red-500 font-medium transition border-t border-slate-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Hapus
                    </button>
                </div>
            </div>`;

            // RENDER HTML PER BUBBLE
            if (isMe) {
                html += `
                <div class="chat-bubble-wrapper flex items-end justify-end w-full group relative gap-2 z-10 ${marginTop}">
                    ${menuBtnHtml}
                    
                    <div class="${bubbleClass} px-3 py-1.5 rounded-[18px] ${isSameSender ? 'rounded-tr-md rounded-br-md' : 'rounded-br-sm'} max-w-[250px] shadow-sm">
                        ${replyBlock}
                        ${messageContent}
                        <div class="flex items-center justify-end mt-0.5 gap-1 shrink-0">
                            ${editLabel}
                            <span class="text-[9px] ${timeClass} ml-1">${timeStr}</span>
                            ${tickSvg}
                        </div>
                    </div>

                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-[10px] shrink-0 border border-slate-200 ${isSameSender ? 'invisible' : ''}">
                        ${currentInitial}
                    </div>
                </div>`;
            } else {
                html += `
                <div class="chat-bubble-wrapper flex items-end justify-start w-full group relative gap-2 z-10 ${marginTop}">
                    <div class="w-7 h-7 rounded-full bg-white text-[#4A85F6] flex items-center justify-center font-bold text-[10px] shrink-0 border border-slate-200 shadow-sm ${isSameSender ? 'invisible' : ''}">
                        ${msg.inisial}
                    </div>

                    <div class="flex flex-col items-start">
                        ${!isSameSender ? `<span class="text-[10px] font-bold text-slate-500 ml-1 mb-0.5 drop-shadow-sm">${msg.sender}</span>` : ''}
                        
                        <div class="flex items-end gap-2">
                            <div class="${bubbleClass} px-3 py-1.5 rounded-[18px] ${isSameSender ? 'rounded-tl-md rounded-bl-md' : 'rounded-bl-sm'} max-w-[250px] shadow-sm">
                                ${replyBlock}
                                ${messageContent}
                                <div class="flex items-center justify-end mt-0.5 gap-1 shrink-0">
                                    <span class="text-[9px] ${timeClass} mr-1">${timeStr}</span>
                                    ${editLabel}
                                </div>
                            </div>
                            
                            ${menuBtnHtml}
                        </div>
                    </div>
                </div>`;
            }
        });

        chatBox.innerHTML = html;

        const badge = document.getElementById('chatBadge');
        if (badge && !isChatOpen) {
            if (unreadCount > 0) { badge.innerText = unreadCount > 99 ? '99+' : unreadCount; badge.classList.remove('hidden'); } 
            else { badge.classList.add('hidden'); }
        }
        
        if (hasNewData) { 
            scrollToBottom(); 
            if (!document.getElementById('searchBar').classList.contains('hidden')) searchChat(); 
        } 
    }

    // ==========================================
    // FANCY TOAST IN-WIDGET 
    // ==========================================
    function showFancyToast(title, text, type = 'success') {
        const toast = document.getElementById('fancyToast');
        const iconDiv = document.getElementById('fancyToastIcon');
        document.getElementById('fancyToastTitle').innerText = title;
        document.getElementById('fancyToastText').innerText = text;

        if (type === 'success') {
            iconDiv.className = 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-green-100 text-green-500';
            iconDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        } else if (type === 'info') {
            iconDiv.className = 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-blue-100 text-[#4A85F6]';
            iconDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }

        toast.classList.remove('-translate-y-10', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('-translate-y-10', 'opacity-0');
        }, 3000);
    }

    // ==========================================
    // EMOJI TOGGLE LOGIC
    // ==========================================
    function toggleEmojiPicker(e) {
        e.stopPropagation(); 
        const pickerBox = document.getElementById('emojiPickerBox');
        pickerBox.classList.toggle('hidden');
    }

    // ==========================================
    // FUNGSI UMUM DAN INTERAKSI UI LAINNYA
    // ==========================================
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showFancyToast('Disalin', 'Teks berhasil disalin ke clipboard.', 'success');
        });
        document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden'));
    }

    function toggleMenu(id) {
        const menu = document.getElementById('menu_' + id);
        const isHidden = menu.classList.contains('hidden');
        document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden'));
        if(isHidden) menu.classList.remove('hidden');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.group')) document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden'));
        if (!e.target.closest('#emojiContainer')) document.getElementById('emojiPickerBox').classList.add('hidden');
        if (isChatOpen) {
            const panel = document.getElementById('chatPanel');
            const clickedInsidePanel = panel.contains(e.target);
            const clickedToggleButton = e.target.closest('[onclick="toggleChat()"]');
            const clickedToast = e.target.closest('#fancyToast');
            const clickedModal = e.target.closest('#chatConfirmModal');
            
            if (!clickedInsidePanel && !clickedToggleButton && !clickedToast && !clickedModal) {
                toggleChat(); 
            }
        }
    });

    // ==========================================
    // SEARCH & CALL
    // ==========================================
    function toggleSearch() {
        const bar = document.getElementById('searchBar');
        if (bar.classList.contains('hidden')) {
            bar.classList.remove('hidden');
            document.getElementById('searchInput').focus();
        } else {
            bar.classList.add('hidden');
            document.getElementById('searchInput').value = '';
            searchChat(); 
        }
    }

    function searchChat() {
        const term = document.getElementById('searchInput').value.toLowerCase();
        const bubbles = document.querySelectorAll('.chat-bubble-wrapper');
        bubbles.forEach(b => {
            if (b.innerText.toLowerCase().includes(term)) b.style.display = 'flex';
            else b.style.display = 'none';
        });
    }

    function startVoiceCall() {
        document.getElementById('voiceCallOverlay').classList.remove('hidden');
        callTimeout = setTimeout(() => {
            endVoiceCall();
            showFancyToast('Panggilan Selesai', 'Tidak ada jawaban dari Tim.', 'info');
        }, 4000);
    }

    function endVoiceCall() {
        document.getElementById('voiceCallOverlay').classList.add('hidden');
        if(callTimeout) clearTimeout(callTimeout);
    }

    // ==========================================
    // LOGIKA BALAS, EDIT, DAN KIRIM
    // ==========================================
    function replyMode(id, sender, text) {
        cancelEdit(); 
        replyingChatId = id;
        document.getElementById('replyPreviewSender').innerText = sender;
        document.getElementById('replyPreviewText').innerText = text;
        document.getElementById('replyPreview').classList.remove('hidden');
        chatInput.focus();
        document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden'));
    }

    function cancelReply() {
        replyingChatId = null;
        document.getElementById('replyPreview').classList.add('hidden');
    }

    function editMode(id, text) {
        cancelReply(); 
        editingChatId = id;
        chatInput.value = text;
        chatInput.focus();
        
        document.getElementById('replyPreviewSender').innerText = 'Mengedit Pesan';
        document.getElementById('replyPreviewText').innerText = text;
        document.getElementById('replyPreview').classList.remove('hidden');

        const btn = document.getElementById('sendBtn');
        btn.classList.replace('bg-[#4A85F6]', 'bg-emerald-500');
        btn.classList.replace('hover:bg-blue-600', 'hover:bg-emerald-600');
        document.getElementById('sendIcon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" fill="none" stroke="currentColor"/>';
        document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden'));
    }

    function cancelEdit() {
        editingChatId = null;
        chatInput.value = '';
        document.getElementById('replyPreview').classList.add('hidden');
        const btn = document.getElementById('sendBtn');
        btn.classList.replace('bg-emerald-500', 'bg-[#4A85F6]');
        btn.classList.replace('hover:bg-emerald-600', 'hover:bg-blue-600');
        document.getElementById('sendIcon').innerHTML = '<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" fill="currentColor"/>';
    }

    async function sendChat() {
        const msg = chatInput.value.trim();
        if (!msg) return;

        const payload = { action: editingChatId ? 'edit' : 'send', message: msg };
        if (editingChatId) payload.id = editingChatId;
        if (replyingChatId) payload.reply_to = replyingChatId;

        cancelEdit(); 
        cancelReply();
        chatInput.value = ''; 
        document.getElementById('emojiPickerBox').classList.add('hidden'); 

        try {
            await fetch('ajax_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            fetchChat(); 
            scrollToBottom();
        } catch (err) { alert('Gagal memproses pesan!'); }
    }

    // ==========================================
    // LOGIKA MODAL KONFIRMASI (Hapus / Tarik)
    // ==========================================
    function openConfirmModal(tipe, id) {
        document.querySelectorAll('.chat-menu').forEach(m => m.classList.add('hidden')); 
        
        pendingAction = tipe; 
        pendingChatId = id;
        
        const modal = document.getElementById('chatConfirmModal');
        const box = document.getElementById('chatConfirmBox');
        const title = document.getElementById('chatConfirmTitle');
        const text = document.getElementById('chatConfirmText');
        const btn = document.getElementById('chatConfirmBtn');
        const icon = document.getElementById('chatConfirmIcon');

        if (tipe === 'delete') {
            title.innerText = 'Hapus Pesan'; 
            text.innerText = 'Hapus pesan ini dari layarmu secara permanen?';
            btn.innerText = 'Hapus'; 
            btn.className = 'flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-bold transition shadow-sm';
            icon.className = 'w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 bg-red-50 text-red-500';
            icon.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
        } else if (tipe === 'retract') {
            title.innerText = 'Tarik Pesan'; 
            text.innerText = 'Pesan akan ditarik dan tidak bisa dibaca oleh tim.';
            btn.innerText = 'Tarik'; 
            btn.className = 'flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition shadow-sm';
            icon.className = 'w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 bg-amber-50 text-amber-500';
            icon.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>';
        }
        
        modal.classList.remove('hidden');
        setTimeout(() => { 
            box.classList.remove('scale-95', 'opacity-0'); 
            box.classList.add('scale-100', 'opacity-100'); 
        }, 10);
    }

    function closeConfirmModal() {
        const modal = document.getElementById('chatConfirmModal');
        const box = document.getElementById('chatConfirmBox');
        
        box.classList.remove('scale-100', 'opacity-100'); 
        box.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => { 
            modal.classList.add('hidden'); 
        }, 200);
        
        pendingAction = null; 
        pendingChatId = null;
    }

    async function executeActionChat() {
        if (!pendingAction || !pendingChatId) return;
        const tipe = pendingAction; 
        const id = pendingChatId;
        
        closeConfirmModal(); 
        
        try {
            await fetch('ajax_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: tipe, id: id })
            });
            fetchChat(); 
            if(tipe === 'delete') showFancyToast('Pesan Dihapus', 'Berhasil dihapus dari layar.', 'success');
            if(tipe === 'retract') showFancyToast('Pesan Ditarik', 'Berhasil ditarik dari grup.', 'success');
        } catch (err) { alert('Terjadi kesalahan koneksi!'); }
    }

    function scrollToBottom() { 
        setTimeout(() => { chatBox.scrollTop = chatBox.scrollHeight; }, 50); 
    }

    // ==========================================
    // JALANKAN SERVICE CHAT
    // ==========================================
    setTimeout(fetchChat, 1000); 
    setInterval(() => { if(!isChatOpen) fetchChat(); }, 10000);
</script>