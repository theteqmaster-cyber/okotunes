/**
 * Okotune (s) AI — Sovereign Intelligence Studio Frontend
 * Dedicated Fullscreen Login Gate, Multimodal Image Vision, & Real-Time Groq Streaming
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

  // Application State
  let currentThreadId = null;
  let isGenerating = false;
  let currentMessages = [];
  let attachedImageBase64 = null;

  // =========================================================================
  // 1. Hard-Gated Authentication
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
    loginScreen.style.display = 'flex';
    appLayout.style.display = 'none';
    authPasswordInput.value = '';
    authEmailInput.focus();
  }

  function showApp() {
    loginScreen.style.display = 'none';
    appLayout.style.display = 'flex';
    initApp();
  }

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    loginErrorMsg.style.display = 'none';
    btnLoginSubmit.disabled = true;
    btnLoginSubmit.textContent = 'Verifying...';

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
        loginErrorMsg.textContent = data.error || 'Access denied. Invalid credentials.';
        loginErrorMsg.style.display = 'block';
      }
    } catch (err) {
      loginErrorMsg.textContent = 'Connection error. Check your server status.';
      loginErrorMsg.style.display = 'block';
    } finally {
      btnLoginSubmit.disabled = false;
      btnLoginSubmit.textContent = 'Access Private Studio';
    }
  });

  btnSignOut.addEventListener('click', async () => {
    try {
      await fetch('/api/auth.php', { method: 'DELETE' });
      currentThreadId = null;
      currentMessages = [];
      showLogin();
    } catch (err) {
      showLogin();
    }
  });

  function initApp() {
    loadThreads();
    loadLore();
    setupEventListeners();
  }

  // =========================================================================
  // 2. Multimodal Image Attachment & Processing
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
      imagePreviewDock.style.display = 'flex';

      // Automatically switch to Vision-capable Qwen model
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
    imagePreviewDock.style.display = 'none';
    btnSend.disabled = chatInput.value.trim().length === 0;
  }

  // Clipboard Paste (Ctrl+V) for screenshots
  window.addEventListener('paste', (e) => {
    if (loginScreen.style.display !== 'none') return;
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let i = 0; i < items.length; i++) {
      if (items[i].type.indexOf('image') !== -1) {
        const file = items[i].getAsFile();
        handleImageFile(file);
        break;
      }
    }
  });

  // Drag and drop images into chat
  window.addEventListener('dragover', (e) => e.preventDefault());
  window.addEventListener('drop', (e) => {
    if (loginScreen.style.display !== 'none') return;
    e.preventDefault();
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const file = e.dataTransfer.files[0];
      if (file.type.startsWith('image/')) {
        handleImageFile(file);
      }
    }
  });

  // =========================================================================
  // 3. Thread Management
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
    threadsList.innerHTML = '<div class="threads-section-title">Conversations</div>';
    if (!threads || threads.length === 0) {
      const emptyDiv = document.createElement('div');
      emptyDiv.style.cssText = 'padding: 10px 12px; font-size: 0.8rem; color: var(--text-subtle);';
      emptyDiv.textContent = 'No past conversations';
      threadsList.appendChild(emptyDiv);
      return;
    }

    threads.forEach((t) => {
      const item = document.createElement('div');
      item.className = `thread-item ${t.id === currentThreadId ? 'active' : ''}`;
      item.dataset.id = t.id;

      item.innerHTML = `
        <span class="thread-title-text" title="${escapeHtml(t.title)}">${escapeHtml(t.title)}</span>
        <div class="thread-actions">
          <button class="btn-thread-action btn-del-thread" title="Delete conversation">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </button>
        </div>
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

    document.querySelectorAll('.thread-item').forEach((el) => {
      el.classList.toggle('active', el.dataset.id === id);
    });

    sidebar.classList.remove('open');

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
    const existingRows = chatContainer.querySelectorAll('.message-row');
    existingRows.forEach((r) => r.remove());

    if (!currentMessages || currentMessages.length === 0) {
      welcomeHero.style.display = 'flex';
    } else {
      welcomeHero.style.display = 'none';
      currentMessages.forEach((msg, idx) => {
        appendMessageElement(msg.role, msg.content, msg.image || null, idx);
      });
      scrollToBottom();
    }
  }

  function appendMessageElement(role, textContent, imageBase64 = null, index = null) {
    welcomeHero.style.display = 'none';

    const row = document.createElement('div');
    row.className = `message-row ${role}`;
    if (index !== null) row.dataset.index = index;

    const avatar = document.createElement('div');
    avatar.className = 'avatar-badge';
    avatar.textContent = role === 'user' ? 'MPAS' : 'OKO';

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';

    // If an image was attached by the user, embed the thumbnail
    if (role === 'user' && imageBase64) {
      const imgContainer = document.createElement('div');
      imgContainer.className = 'user-attached-image-container';
      const img = document.createElement('img');
      img.className = 'user-attached-image';
      img.src = imageBase64;
      img.alt = 'Attached Image';
      imgContainer.appendChild(img);
      contentDiv.appendChild(imgContainer);
    }

    const textWrap = document.createElement('div');
    textWrap.innerHTML = formatMarkdown(textContent);
    contentDiv.appendChild(textWrap);

    bubble.appendChild(contentDiv);

    // If assistant, attach action bar
    if (role === 'assistant') {
      const actionsBar = document.createElement('div');
      actionsBar.className = 'message-actions-bar';

      const btnCopy = document.createElement('button');
      btnCopy.className = 'btn-msg-action';
      btnCopy.innerHTML = `📋 <span>Copy</span>`;
      btnCopy.addEventListener('click', () => {
        navigator.clipboard.writeText(textContent);
        btnCopy.innerHTML = `✓ <span>Copied</span>`;
        setTimeout(() => { btnCopy.innerHTML = `📋 <span>Copy</span>`; }, 1800);
      });
      actionsBar.appendChild(btnCopy);

      bubble.appendChild(actionsBar);
    }

    row.appendChild(avatar);
    row.appendChild(bubble);
    chatContainer.appendChild(row);

    setupCodeBlockCopy(contentDiv);

    return textWrap;
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

    // 1. Render User Message
    const userMsg = {
      role: 'user',
      content: text || 'Inspect this image',
      image: imageToSend,
      timestamp: new Date().toISOString()
    };
    currentMessages.push(userMsg);
    appendMessageElement('user', userMsg.content, imageToSend, currentMessages.length - 1);
    scrollToBottom();

    // 2. Prepare Assistant Streaming Message
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
              } catch (err) {
                // Ignore partial chunks
              }
            }
          }
        }
      }

      // Stream Finished
      cursor.remove();
      assistantContentDiv.innerHTML = formatMarkdown(fullText);
      setupCodeBlockCopy(assistantContentDiv);

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
      assistantContentDiv.innerHTML = `<span style="color: #ef4444;">Error: ${escapeHtml(err.message)}</span>`;
    } finally {
      isGenerating = false;
      btnSend.disabled = chatInput.value.trim().length === 0 && !attachedImageBase64;
      chatInput.focus();
    }
  }

  // =========================================================================
  // 6. Directives & Lore Drawer
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
      directivesList.innerHTML = '<div style="color: var(--text-subtle); font-size: 0.8rem;">No custom directives added yet.</div>';
      return;
    }

    directives.forEach((d) => {
      const card = document.createElement('div');
      card.className = 'directive-card';
      card.innerHTML = `
        <div class="directive-header">
          <span class="directive-cat">${escapeHtml(d.category || 'Rule')}</span>
          <button class="btn-delete-dir" data-id="${d.id}" title="Remove directive">✕</button>
        </div>
        <div class="directive-rule">${escapeHtml(d.rule)}</div>
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

  // Drawer Controls
  btnOpenLore.addEventListener('click', () => {
    loadLore();
    drawerOverlay.classList.add('open');
    loreDrawer.classList.add('open');
  });

  btnCloseDrawer.addEventListener('click', () => {
    drawerOverlay.classList.remove('open');
    loreDrawer.classList.remove('open');
  });

  drawerOverlay.addEventListener('click', () => {
    drawerOverlay.classList.remove('open');
    loreDrawer.classList.remove('open');
  });

  // =========================================================================
  // 7. General Event Listeners
  // =========================================================================
  function setupEventListeners() {
    btnNewChat.addEventListener('click', createNewThread);

    btnMobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
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

    // Multi-line code blocks
    html = html.replace(/```([a-zA-Z0-9_\-#+]*)\n([\s\S]*?)```/g, (match, lang, code) => {
      const language = lang || 'code';
      return `<pre><div class="code-header"><span>${language}</span><button class="btn-copy-code">Copy</button></div><code>${code.trim()}</code></pre>`;
    });

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

    // Headers
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');

    // Bold & Italic
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');

    // Blockquotes
    html = html.replace(/^\&gt;\s?(.*$)/gim, '<blockquote>$1</blockquote>');

    // Lists
    html = html.replace(/^\s*[-*]\s+(.*)$/gim, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/gims, '<ul>$1</ul>');

    // Paragraphs
    html = html.split('\n\n').map(p => {
      p = p.trim();
      if (!p) return '';
      if (p.startsWith('<pre>') || p.startsWith('<h1>') || p.startsWith('<h2>') || p.startsWith('<h3>') || p.startsWith('<ul>') || p.startsWith('<blockquote>')) {
        return p;
      }
      return `<p>${p.replace(/\n/g, '<br>')}</p>`;
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
