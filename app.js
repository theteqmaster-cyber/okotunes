/**
 * Okotune (s) AI — Sovereign Intelligence Studio Controller
 * Powered by Groq LPU with Multimodal Vision & Tailwind CSS UI
 */

document.addEventListener('DOMContentLoaded', () => {
  // DOM Elements - Auth & Screens
  const loginScreen = document.getElementById('loginScreen');
  const appLayout = document.getElementById('appLayout');
  const loginForm = document.getElementById('loginForm');
  const authEmailInput = document.getElementById('authEmailInput');
  const authPasswordInput = document.getElementById('authPasswordInput');
  const btnLoginSubmit = document.getElementById('btnLoginSubmit');
  const loginErrorMsg = document.getElementById('loginErrorMsg');
  const btnSignOut = document.getElementById('btnSignOut');

  // DOM Elements - Workspace & Navigation
  const sidebar = document.getElementById('sidebar');
  const btnMobileToggle = document.getElementById('btnMobileToggle');
  const btnNewChat = document.getElementById('btnNewChat');
  const modelSelect = document.getElementById('modelSelect');
  const activeModelBadge = document.getElementById('activeModelBadge');
  const threadsList = document.getElementById('threadsList');
  const threadTitle = document.getElementById('threadTitle');
  const btnClearThread = document.getElementById('btnClearThread');
  const chatScrollArea = document.getElementById('chatScrollArea');
  const chatContainer = document.getElementById('chatContainer');
  const welcomeHero = document.getElementById('welcomeHero');
  const chatInput = document.getElementById('chatInput');
  const btnSend = document.getElementById('btnSend');

  // Image Attachment Elements
  const btnAttachImage = document.getElementById('btnAttachImage');
  const imageFileInput = document.getElementById('imageFileInput');
  const imagePreviewDock = document.getElementById('imagePreviewDock');
  const imagePreviewThumb = document.getElementById('imagePreviewThumb');
  const imagePreviewFilename = document.getElementById('imagePreviewFilename');
  const btnRemoveImage = document.getElementById('btnRemoveImage');

  // Lore Drawer Elements
  const btnOpenLore = document.getElementById('btnOpenLore');
  const btnCloseDrawer = document.getElementById('btnCloseDrawer');
  const drawerOverlay = document.getElementById('drawerOverlay');
  const loreDrawer = document.getElementById('loreDrawer');
  const directivesList = document.getElementById('directivesList');
  const formAddDirective = document.getElementById('formAddDirective');
  const inputNewDirective = document.getElementById('inputNewDirective');

  // State
  let currentThreadId = null;
  let isGenerating = false;
  let currentMessages = [];
  let attachedImageBase64 = null;

  // =========================================================================
  // 1. Authentication Check & Submission
  // =========================================================================
  async function checkAuth() {
    try {
      const res = await fetch('/api/auth.php');
      const data = await res.json();
      if (data.authenticated) {
        showApp();
      } else {
        showLogin();
      }
    } catch (e) {
      showLogin();
    }
  }

  function showLogin() {
    loginScreen.classList.remove('hidden');
    loginScreen.style.display = 'flex';
    appLayout.classList.add('hidden');
    appLayout.style.display = 'none';
    authPasswordInput.value = '';
    authEmailInput.focus();
  }

  function showApp() {
    loginScreen.classList.add('hidden');
    loginScreen.style.display = 'none';
    appLayout.classList.remove('hidden');
    appLayout.style.display = 'flex';
    initApp();
  }

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    loginErrorMsg.classList.add('hidden');
    btnLoginSubmit.disabled = true;
    btnLoginSubmit.textContent = 'Logging in...';

    const email = authEmailInput.value.trim();
    const password = authPasswordInput.value.trim();

    try {
      const res = await fetch('/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      if (data.success) {
        showApp();
      } else {
        loginErrorMsg.textContent = data.error || 'Incorrect password. Please try again.';
        loginErrorMsg.classList.remove('hidden');
      }
    } catch (err) {
      loginErrorMsg.textContent = 'Connection error. Please try again.';
      loginErrorMsg.classList.remove('hidden');
    } finally {
      btnLoginSubmit.disabled = false;
      btnLoginSubmit.textContent = 'Log in';
    }
  });

  btnSignOut.addEventListener('click', async () => {
    try {
      await fetch('/api/auth.php', { method: 'DELETE' });
    } catch (err) {}
    currentThreadId = null;
    currentMessages = [];
    showLogin();
  });

  function initApp() {
    loadThreads();
    loadLore();
    setupEventListeners();
  }

  // =========================================================================
  // 2. Multimodal Image Attachment & Vision
  // =========================================================================
  btnAttachImage.addEventListener('click', () => {
    imageFileInput.click();
  });

  imageFileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) handleImageFile(file);
  });

  function handleImageFile(file) {
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file (PNG, JPG, WEBP, GIF).');
      return;
    }
    const reader = new FileReader();
    reader.onload = (event) => {
      attachedImageBase64 = event.target.result;
      imagePreviewThumb.src = attachedImageBase64;
      imagePreviewFilename.textContent = file.name;
      imagePreviewDock.classList.remove('hidden');
      imagePreviewDock.classList.add('flex');

      // Auto-switch to Vision model
      modelSelect.value = 'qwen/qwen3.8-27b';
      activeModelBadge.textContent = 'Qwen 3.8 27B Vision';

      btnSend.disabled = false;
      chatInput.focus();
    };
    reader.readAsDataURL(file);
  }

  btnRemoveImage.addEventListener('click', () => {
    clearAttachedImage();
  });

  function clearAttachedImage() {
    attachedImageBase64 = null;
    imageFileInput.value = '';
    imagePreviewDock.classList.add('hidden');
    imagePreviewDock.classList.remove('flex');
    btnSend.disabled = chatInput.value.trim().length === 0;
  }

  // Paste handler for screenshots (Ctrl+V)
  window.addEventListener('paste', (e) => {
    if (loginScreen.style.display !== 'none' && !loginScreen.classList.contains('hidden')) return;
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let i = 0; i < items.length; i++) {
      if (items[i].type.indexOf('image') !== -1) {
        const file = items[i].getAsFile();
        handleImageFile(file);
        break;
      }
    }
  });

  // Drag and drop handler
  window.addEventListener('dragover', (e) => e.preventDefault());
  window.addEventListener('drop', (e) => {
    if (loginScreen.style.display !== 'none' && !loginScreen.classList.contains('hidden')) return;
    e.preventDefault();
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const file = e.dataTransfer.files[0];
      if (file.type.startsWith('image/')) {
        handleImageFile(file);
      }
    }
  });

  // =========================================================================
  // 3. Threads Management
  // =========================================================================
  async function loadThreads() {
    try {
      const res = await fetch('/api/threads.php');
      const data = await res.json();
      if (data.success) {
        renderThreadsList(data.threads);
        if (data.threads.length > 0 && !currentThreadId) {
          selectThread(data.threads[0].id, data.threads[0].title);
        } else if (!currentThreadId) {
          createNewThread();
        }
      }
    } catch (e) {
      console.error('Error loading threads:', e);
    }
  }

  function renderThreadsList(threads) {
    threadsList.innerHTML = '<div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono">Conversations</div>';
    if (!threads || threads.length === 0) {
      const emptyDiv = document.createElement('div');
      emptyDiv.className = 'px-3 py-2 text-xs text-slate-500 font-mono';
      emptyDiv.textContent = 'No past conversations';
      threadsList.appendChild(emptyDiv);
      return;
    }

    threads.forEach((t) => {
      const isActive = t.id === currentThreadId;
      const item = document.createElement('div');
      item.className = `group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium cursor-pointer transition ${
        isActive 
          ? 'bg-indigo-600/20 text-white border border-indigo-500/30 font-semibold' 
          : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
      }`;
      item.dataset.id = t.id;

      item.innerHTML = `
        <span class="truncate flex-1" title="${escapeHtml(t.title)}">${escapeHtml(t.title)}</span>
        <button class="btn-del-thread opacity-0 group-hover:opacity-100 p-1 hover:text-rose-400 transition" title="Delete conversation">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </button>
      `;

      item.addEventListener('click', (e) => {
        if (e.target.closest('.btn-del-thread')) {
          e.stopPropagation();
          deleteThread(t.id);
          return;
        }
        selectThread(t.id, t.title);
      });

      threadsList.appendChild(item);
    });
  }

  async function selectThread(id, title) {
    if (isGenerating) return;
    currentThreadId = id;
    threadTitle.textContent = title || 'Conversation';

    document.querySelectorAll('#threadsList > div[data-id]').forEach((el) => {
      const match = el.dataset.id === id;
      el.className = `group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium cursor-pointer transition ${
        match 
          ? 'bg-indigo-600/20 text-white border border-indigo-500/30 font-semibold' 
          : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
      }`;
    });

    sidebar.classList.add('-translate-x-full');

    try {
      const res = await fetch(`/api/threads.php?id=${id}`);
      const data = await res.json();
      currentMessages = data.messages || [];
      renderChatMessages();
    } catch (e) {
      console.error('Failed to load thread messages:', e);
    }
  }

  async function createNewThread() {
    if (isGenerating) return;
    try {
      const res = await fetch('/api/threads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'create', title: 'New Conversation', model: modelSelect.value })
      });
      const data = await res.json();
      if (data.success) {
        currentThreadId = data.thread.id;
        currentMessages = [];
        threadTitle.textContent = 'New Conversation';
        renderChatMessages();
        loadThreads();
        chatInput.focus();
      }
    } catch (e) {
      console.error('Failed to create new thread:', e);
    }
  }

  async function deleteThread(id) {
    if (!confirm('Delete this conversation?')) return;
    try {
      await fetch(`/api/threads.php?id=${id}`, { method: 'DELETE' });
      if (currentThreadId === id) {
        currentThreadId = null;
        createNewThread();
      } else {
        loadThreads();
      }
    } catch (e) {
      console.error('Failed to delete thread:', e);
    }
  }

  // =========================================================================
  // 4. Message Rendering & Markdown Parser
  // =========================================================================
  function renderChatMessages() {
    const existingRows = chatContainer.querySelectorAll('.chat-msg-row');
    existingRows.forEach((r) => r.remove());

    if (!currentMessages || currentMessages.length === 0) {
      welcomeHero.classList.remove('hidden');
      welcomeHero.classList.add('flex');
    } else {
      welcomeHero.classList.add('hidden');
      welcomeHero.classList.remove('flex');
      currentMessages.forEach((msg, idx) => {
        appendMessageElement(msg.role, msg.content, msg.image || null, idx);
      });
      scrollToBottom();
    }
  }

  function appendMessageElement(role, textContent, imageBase64 = null, index = null) {
    welcomeHero.classList.add('hidden');
    welcomeHero.classList.remove('flex');

    const row = document.createElement('div');
    row.className = 'chat-msg-row w-full flex gap-3 transition-all';
    if (index !== null) row.dataset.index = index;

    if (role === 'user') {
      row.classList.add('justify-end');
      row.innerHTML = `
        <div class="max-w-[85%] bg-gradient-to-r from-indigo-600/25 to-purple-600/25 border border-indigo-500/30 rounded-2xl rounded-tr-sm p-4 text-white text-sm shadow-md backdrop-blur-md">
          ${imageBase64 ? `<div class="mb-3"><img src="${imageBase64}" alt="Attached" class="max-w-xs max-h-60 rounded-xl border border-indigo-400/30 object-cover shadow-lg"></div>` : ''}
          <div class="whitespace-pre-wrap">${escapeHtml(textContent)}</div>
        </div>
        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-mono text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm">
          MPAS
        </div>
      `;
      chatContainer.appendChild(row);
      return row.querySelector('.whitespace-pre-wrap');
    } else {
      row.classList.add('justify-start');
      row.innerHTML = `
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white font-mono text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm shadow-indigo-500/20">
          OKO
        </div>
        <div class="max-w-[88%] bg-dark-900/90 border border-slate-800 rounded-2xl rounded-tl-sm p-4 text-slate-200 text-sm shadow-md backdrop-blur-md">
          <div class="message-content leading-relaxed">${formatMarkdown(textContent)}</div>
          <div class="flex items-center gap-2 mt-3 pt-2 border-t border-slate-800/80 text-[11px] text-slate-400">
            <button class="btn-copy-msg hover:text-white transition flex items-center gap-1 cursor-pointer">
              <span>📋</span> <span>Copy</span>
            </button>
          </div>
        </div>
      `;
      chatContainer.appendChild(row);

      const copyBtn = row.querySelector('.btn-copy-msg');
      if (copyBtn) {
        copyBtn.addEventListener('click', () => {
          navigator.clipboard.writeText(textContent);
          copyBtn.innerHTML = '<span>✓</span> <span>Copied!</span>';
          setTimeout(() => { copyBtn.innerHTML = '<span>📋</span> <span>Copy</span>'; }, 1800);
        });
      }

      setupCodeBlockCopy(row);
      return row.querySelector('.message-content');
    }
  }

  // =========================================================================
  // 5. Chat Send & Multimodal Streaming
  // =========================================================================
  async function sendMessage(text) {
    if ((!text && !attachedImageBase64) || isGenerating) return;

    if (!currentThreadId) {
      await createNewThread();
    }

    isGenerating = true;
    btnSend.disabled = true;
    chatInput.value = '';
    autoResizeTextarea();

    const imageToSend = attachedImageBase64;
    clearAttachedImage();

    // 1. Optimistic User Bubble
    const userMsg = {
      role: 'user',
      content: text || 'Inspect this image',
      image: imageToSend,
      timestamp: new Date().toISOString()
    };
    currentMessages.push(userMsg);
    appendMessageElement('user', userMsg.content, imageToSend, currentMessages.length - 1);
    scrollToBottom();

    // 2. Prepare Assistant Bubble
    const assistantContentDiv = appendMessageElement('assistant', '');
    const cursor = document.createElement('span');
    cursor.className = 'streaming-cursor';
    assistantContentDiv.appendChild(cursor);
    scrollToBottom();

    let fullText = '';
    const selectedModel = imageToSend ? 'qwen/qwen3.8-27b' : modelSelect.value;

    try {
      const res = await fetch('/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          thread_id: currentThreadId,
          message: text,
          image: imageToSend,
          model: selectedModel,
          stream: true
        })
      });

      if (!res.ok) {
        const errJson = await res.json().catch(() => ({}));
        throw new Error(errJson.error || `HTTP ${res.status}`);
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder('utf-8');
      let done = false;

      while (!done) {
        const { value, done: readerDone } = await reader.read();
        done = readerDone;
        if (value) {
          const chunk = decoder.decode(value, { stream: true });
          const lines = chunk.split('\n');

          for (const line of lines) {
            const trimmed = line.trim();
            if (trimmed.startsWith('data: ') && trimmed !== 'data: [DONE]') {
              try {
                const parsed = JSON.parse(trimmed.slice(6));
                const token = parsed.choices?.[0]?.delta?.content || '';
                if (token) {
                  fullText += token;
                  assistantContentDiv.innerHTML = formatMarkdown(fullText);
                  assistantContentDiv.appendChild(cursor);
                  scrollToBottom();
                }
              } catch (err) {}
            }
          }
        }
      }

      cursor.remove();
      assistantContentDiv.innerHTML = formatMarkdown(fullText);
      setupCodeBlockCopy(assistantContentDiv.closest('.chat-msg-row'));

      const assistantMsg = {
        role: 'assistant',
        content: fullText,
        model: selectedModel,
        timestamp: new Date().toISOString()
      };
      currentMessages.push(assistantMsg);

      loadThreads();

    } catch (err) {
      cursor.remove();
      assistantContentDiv.innerHTML = `<span class="text-rose-400 font-medium">Error: ${escapeHtml(err.message)}</span>`;
    } finally {
      isGenerating = false;
      btnSend.disabled = chatInput.value.trim().length === 0 && !attachedImageBase64;
      chatInput.focus();
    }
  }

  // =========================================================================
  // 6. Directives & Lore
  // =========================================================================
  async function loadLore() {
    try {
      const res = await fetch('/api/lore.php');
      const data = await res.json();
      if (data.success) {
        renderDirectives(data.lore.directives || []);
      }
    } catch (e) {
      console.error('Failed to load lore:', e);
    }
  }

  function renderDirectives(directives) {
    directivesList.innerHTML = '';
    if (directives.length === 0) {
      directivesList.innerHTML = '<div class="text-xs text-slate-500">No custom directives added yet.</div>';
      return;
    }

    directives.forEach((d) => {
      const card = document.createElement('div');
      card.className = 'p-3 rounded-xl bg-dark-850 border border-slate-800 text-xs text-slate-300 relative space-y-1';
      card.innerHTML = `
        <div class="flex items-center justify-between text-[10px] font-mono font-bold text-indigo-400 uppercase">
          <span>${escapeHtml(d.category || 'Rule')}</span>
          <button class="btn-delete-dir text-slate-500 hover:text-rose-400 p-0.5" data-id="${d.id}">✕</button>
        </div>
        <div class="leading-relaxed text-white">${escapeHtml(d.rule)}</div>
      `;

      card.querySelector('.btn-delete-dir').addEventListener('click', async (e) => {
        e.stopPropagation();
        try {
          await fetch(`/api/lore.php?id=${d.id}`, { method: 'DELETE' });
          loadLore();
        } catch (err) {
          console.error(err);
        }
      });

      directivesList.appendChild(card);
    });
  }

  formAddDirective.addEventListener('submit', async (e) => {
    e.preventDefault();
    const val = inputNewDirective.value.trim();
    if (!val) return;

    try {
      const res = await fetch('/api/lore.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ new_directive: val, category: 'Personal Preference' })
      });
      const data = await res.json();
      if (data.success) {
        inputNewDirective.value = '';
        loadLore();
      }
    } catch (err) {
      console.error(err);
    }
  });

  // Drawer Toggles
  btnOpenLore.addEventListener('click', () => {
    loadLore();
    drawerOverlay.classList.remove('hidden');
    loreDrawer.classList.remove('translate-x-full');
  });

  btnCloseDrawer.addEventListener('click', closeDrawer);
  drawerOverlay.addEventListener('click', closeDrawer);

  function closeDrawer() {
    drawerOverlay.classList.add('hidden');
    loreDrawer.classList.add('translate-x-full');
  }

  // =========================================================================
  // 7. Event Handlers
  // =========================================================================
  function setupEventListeners() {
    btnNewChat.addEventListener('click', createNewThread);

    btnMobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });

    modelSelect.addEventListener('change', () => {
      const text = modelSelect.options[modelSelect.selectedIndex].text.split('(')[0].trim();
      activeModelBadge.textContent = text;
    });

    btnClearThread.addEventListener('click', () => {
      if (!currentMessages || currentMessages.length === 0) return;
      if (confirm('Clear messages in this conversation?')) {
        currentMessages = [];
        renderChatMessages();
      }
    });

    chatInput.addEventListener('input', () => {
      autoResizeTextarea();
      btnSend.disabled = (chatInput.value.trim().length === 0 && !attachedImageBase64) || isGenerating;
    });

    chatInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if ((text || attachedImageBase64) && !isGenerating) {
          sendMessage(text);
        }
      }
    });

    btnSend.addEventListener('click', () => {
      const text = chatInput.value.trim();
      if ((text || attachedImageBase64) && !isGenerating) {
        sendMessage(text);
      }
    });

    document.querySelectorAll('.suggestion-card').forEach((card) => {
      card.addEventListener('click', () => {
        const prompt = card.dataset.prompt;
        if (prompt && !isGenerating) {
          chatInput.value = prompt;
          autoResizeTextarea();
          sendMessage(prompt);
        }
      });
    });
  }

  function autoResizeTextarea() {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 180) + 'px';
  }

  function scrollToBottom() {
    chatScrollArea.scrollTop = chatScrollArea.scrollHeight;
  }

  function setupCodeBlockCopy(container) {
    container.querySelectorAll('.btn-copy-code').forEach((btn) => {
      btn.addEventListener('click', () => {
        const pre = btn.closest('pre');
        const code = pre ? pre.querySelector('code')?.innerText : '';
        if (code) {
          navigator.clipboard.writeText(code);
          btn.textContent = '✓ Copied!';
          setTimeout(() => { btn.textContent = 'Copy'; }, 1800);
        }
      });
    });
  }

  function formatMarkdown(text) {
    if (!text) return '';

    let html = escapeHtml(text);

    // Code blocks
    html = html.replace(/```([a-zA-Z0-9_\-#+]*)\n([\s\S]*?)```/g, (match, lang, code) => {
      const language = lang || 'code';
      return `<pre class="my-3 rounded-xl overflow-hidden bg-dark-950 border border-slate-800"><div class="flex items-center justify-between px-3.5 py-1.5 bg-dark-900 border-b border-slate-800 text-[11px] font-mono text-slate-400"><span>${language}</span><button class="btn-copy-code px-2 py-0.5 rounded hover:bg-slate-800 text-slate-300">Copy</button></div><code class="p-3.5 block overflow-x-auto text-xs font-mono text-slate-200 leading-relaxed">${code.trim()}</code></pre>`;
    });

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded bg-slate-800/80 text-indigo-300 font-mono text-xs border border-slate-700/60">$1</code>');

    // Headers
    html = html.replace(/^### (.*$)/gim, '<h3 class="text-base font-bold text-white mt-4 mb-1">$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2 class="text-lg font-bold text-white mt-5 mb-1.5">$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1 class="text-xl font-bold text-white mt-6 mb-2">$1</h1>');

    // Bold & Italic
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold text-white">$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em class="italic text-slate-300">$1</em>');

    // Blockquotes
    html = html.replace(/^\&gt;\s?(.*$)/gim, '<blockquote class="border-l-2 border-indigo-500 pl-3 py-1 my-2 bg-indigo-500/5 text-slate-300 rounded-r-lg">$1</blockquote>');

    // Lists
    html = html.replace(/^\s*[-*]\s+(.*)$/gim, '<li class="ml-4 list-disc text-slate-300">$1</li>');
    html = html.replace(/(<li.*<\/li>)/gims, '<ul class="my-2 space-y-1">$1</ul>');

    // Paragraphs
    html = html.split('\n\n').map(p => {
      p = p.trim();
      if (!p) return '';
      if (p.startsWith('<pre') || p.startsWith('<h1') || p.startsWith('<h2') || p.startsWith('<h3') || p.startsWith('<ul') || p.startsWith('<blockquote')) {
        return p;
      }
      return `<p class="my-1.5 leading-relaxed text-slate-200">${p.replace(/\n/g, '<br>')}</p>`;
    }).join('');

    return html;
  }

  function escapeHtml(str) {
    return (str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Start Auth Check
  checkAuth();
});
