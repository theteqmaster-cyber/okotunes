/**
 * Okotune (s) AI — Sovereign Intelligence Studio Frontend
 * Handles SSE Streaming, Multi-Turn Threads, Markdown DSP, and Overfitting Lore
 */

document.addEventListener('DOMContentLoaded', () => {
  // DOM Elements
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

  // Lore Drawer Elements
  const btnOpenLore = document.getElementById('btnOpenLore');
  const btnCloseDrawer = document.getElementById('btnCloseDrawer');
  const drawerOverlay = document.getElementById('drawerOverlay');
  const loreDrawer = document.getElementById('loreDrawer');
  const statInteractions = document.getElementById('statInteractions');
  const statGold = document.getElementById('statGold');
  const statDatasetSize = document.getElementById('statDatasetSize');
  const directivesList = document.getElementById('directivesList');
  const formAddDirective = document.getElementById('formAddDirective');
  const inputNewDirective = document.getElementById('inputNewDirective');

  // Auth Modal Elements
  const authModalOverlay = document.getElementById('authModalOverlay');
  const authForm = document.getElementById('authForm');
  const authPasswordInput = document.getElementById('authPasswordInput');
  const authErrorMsg = document.getElementById('authErrorMsg');

  // Application State
  let currentThreadId = null;
  let isGenerating = false;
  let currentMessages = [];

  // =========================================================================
  // 1. Authentication & Initialization
  // =========================================================================
  async function checkAuth() {
    try {
      const res = await fetch('/api/auth.php');
      const data = await res.json();
      if (!data.authenticated) {
        authModalOverlay.style.display = 'flex';
      } else {
        authModalOverlay.style.display = 'none';
        initApp();
      }
    } catch (e) {
      console.warn('Auth check fallback:', e);
      initApp();
    }
  }

  authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    authErrorMsg.style.display = 'none';
    const pwd = authPasswordInput.value.trim();

    try {
      const res = await fetch('/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: pwd })
      });
      const data = await res.json();
      if (data.success) {
        authModalOverlay.style.display = 'none';
        initApp();
      } else {
        authErrorMsg.style.display = 'block';
      }
    } catch (err) {
      authErrorMsg.style.display = 'block';
    }
  });

  function initApp() {
    loadThreads();
    loadLoreStats();
    setupEventListeners();
  }

  // =========================================================================
  // 2. Thread Management
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
          <button class="btn-thread-action btn-del-thread" title="Delete thread">
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

    // Update active highlight in sidebar
    document.querySelectorAll('.thread-item').forEach((el) => {
      el.classList.toggle('active', el.dataset.id === id);
    });

    // Close mobile sidebar if open
    sidebar.classList.remove('open');

    // Fetch thread messages
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
    if (!confirm('Delete this conversation thread?')) return;
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
  // 3. Message Rendering & Markdown Parser
  // =========================================================================
  function renderChatMessages() {
    // Clear dynamic message rows
    const existingRows = chatContainer.querySelectorAll('.message-row');
    existingRows.forEach((r) => r.remove());

    if (!currentMessages || currentMessages.length === 0) {
      welcomeHero.style.display = 'flex';
    } else {
      welcomeHero.style.display = 'none';
      currentMessages.forEach((msg, idx) => {
        appendMessageElement(msg.role, msg.content, idx, msg.gold_standard);
      });
      scrollToBottom();
    }
  }

  function appendMessageElement(role, content, index = null, isGold = false) {
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
    contentDiv.innerHTML = formatMarkdown(content);

    bubble.appendChild(contentDiv);

    // If assistant, attach action bar
    if (role === 'assistant') {
      const actionsBar = document.createElement('div');
      actionsBar.className = 'message-actions-bar';

      // Gold Standard Button
      const btnGold = document.createElement('button');
      btnGold.className = `btn-msg-action ${isGold ? 'gold-active' : ''}`;
      btnGold.innerHTML = `⭐ <span>${isGold ? 'Gold Standard' : 'Mark Gold'}</span>`;
      btnGold.title = 'Add this high-value interaction to the fine-tuning ground truth dataset';
      btnGold.addEventListener('click', () => toggleGoldStandard(index, btnGold));
      actionsBar.appendChild(btnGold);

      // Copy Text Button
      const btnCopy = document.createElement('button');
      btnCopy.className = 'btn-msg-action';
      btnCopy.innerHTML = `📋 <span>Copy</span>`;
      btnCopy.addEventListener('click', () => {
        navigator.clipboard.writeText(content);
        btnCopy.innerHTML = `✓ <span>Copied</span>`;
        setTimeout(() => { btnCopy.innerHTML = `📋 <span>Copy</span>`; }, 1800);
      });
      actionsBar.appendChild(btnCopy);

      bubble.appendChild(actionsBar);
    }

    row.appendChild(avatar);
    row.appendChild(bubble);
    chatContainer.appendChild(row);

    // Setup code block copy buttons
    setupCodeBlockCopy(contentDiv);

    return contentDiv;
  }

  async function toggleGoldStandard(messageIndex, btn) {
    if (messageIndex === null || !currentThreadId) return;
    try {
      const res = await fetch('/api/threads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'mark_gold',
          thread_id: currentThreadId,
          message_index: messageIndex
        })
      });
      const data = await res.json();
      if (data.success) {
        btn.classList.toggle('gold-active', data.is_gold);
        btn.innerHTML = `⭐ <span>${data.is_gold ? 'Gold Standard' : 'Mark Gold'}</span>`;
        loadLoreStats();
      }
    } catch (e) {
      console.error('Failed to toggle gold standard:', e);
    }
  }

  // =========================================================================
  // 4. Chat Send & SSE Streaming
  // =========================================================================
  async function sendMessage(text) {
    if (!text || isGenerating) return;

    if (!currentThreadId) {
      await createNewThread();
    }

    isGenerating = true;
    btnSend.disabled = true;
    chatInput.value = '';
    autoResizeTextarea();

    // 1. Optimistic User Message
    const userMsg = { role: 'user', content: text, timestamp: new Date().toISOString() };
    currentMessages.push(userMsg);
    appendMessageElement('user', text, currentMessages.length - 1);
    scrollToBottom();

    // 2. Prepare Assistant Streaming Message
    const assistantContentDiv = appendMessageElement('assistant', '');
    const cursor = document.createElement('span');
    cursor.className = 'streaming-cursor';
    assistantContentDiv.appendChild(cursor);
    scrollToBottom();

    let fullText = '';
    const selectedModel = modelSelect.value;

    try {
      const res = await fetch('/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          thread_id: currentThreadId,
          message: text,
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
                // Ignore partial JSON chunks
              }
            }
          }
        }
      }

      // Stream Finished
      cursor.remove();
      assistantContentDiv.innerHTML = formatMarkdown(fullText);
      setupCodeBlockCopy(assistantContentDiv);

      // Record in local state
      const assistantMsg = {
        role: 'assistant',
        content: fullText,
        model: selectedModel,
        timestamp: new Date().toISOString(),
        gold_standard: false
      };
      currentMessages.push(assistantMsg);

      // Reload thread titles and stats in background
      loadThreads();
      loadLoreStats();

    } catch (err) {
      cursor.remove();
      assistantContentDiv.innerHTML = `<span style="color: #ef4444;">Error generating response: ${escapeHtml(err.message)}</span>`;
    } finally {
      isGenerating = false;
      btnSend.disabled = chatInput.value.trim().length === 0;
      chatInput.focus();
    }
  }

  // =========================================================================
  // 5. Lore & Overfitting Drawer
  // =========================================================================
  async function loadLoreStats() {
    try {
      const res = await fetch('/api/lore.php');
      const data = await res.json();
      if (data.success) {
        statInteractions.textContent = data.stats.total_interactions;
        statGold.textContent = data.stats.gold_standard_pairs;
        statDatasetSize.textContent = data.stats.dataset_formatted;

        renderDirectives(data.lore.directives || []);
      }
    } catch (e) {
      console.error('Failed to load lore stats:', e);
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
          <button class="btn-delete-dir" data-id="${d.id}" title="Remove rule">✕</button>
        </div>
        <div class="directive-rule">${escapeHtml(d.rule)}</div>
      `;

      card.querySelector('.btn-delete-dir').addEventListener('click', async (e) => {
        e.stopPropagation();
        try {
          await fetch(`/api/lore.php?id=${d.id}`, { method: 'DELETE' });
          loadLoreStats();
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
        body: JSON.stringify({ new_directive: val, category: 'Personal Lore' })
      });
      const data = await res.json();
      if (data.success) {
        inputNewDirective.value = '';
        loadLoreStats();
      }
    } catch (err) {
      console.error(err);
    }
  });

  // Drawer Toggles
  function openDrawer() {
    loadLoreStats();
    drawerOverlay.classList.add('open');
    loreDrawer.classList.add('open');
  }

  function closeDrawer() {
    drawerOverlay.classList.remove('open');
    loreDrawer.classList.remove('open');
  }

  btnOpenLore.addEventListener('click', openDrawer);
  btnCloseDrawer.addEventListener('click', closeDrawer);
  drawerOverlay.addEventListener('click', closeDrawer);

  // =========================================================================
  // 6. UI Interactions & Event Handlers
  // =========================================================================
  function setupEventListeners() {
    // New Chat
    btnNewChat.addEventListener('click', createNewThread);

    // Mobile Toggle
    btnMobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });

    // Model Selector Change
    modelSelect.addEventListener('change', () => {
      const text = modelSelect.options[modelSelect.selectedIndex].text.split('(')[0].trim();
      activeModelBadge.textContent = text;
    });

    // Clear Thread
    btnClearThread.addEventListener('click', () => {
      if (!currentMessages || currentMessages.length === 0) return;
      if (confirm('Clear messages in this conversation?')) {
        currentMessages = [];
        renderChatMessages();
      }
    });

    // Chat Input Auto-Resize & Key Handling
    chatInput.addEventListener('input', () => {
      autoResizeTextarea();
      btnSend.disabled = chatInput.value.trim().length === 0 || isGenerating;
    });

    chatInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (text && !isGenerating) {
          sendMessage(text);
        }
      }
    });

    btnSend.addEventListener('click', () => {
      const text = chatInput.value.trim();
      if (text && !isGenerating) {
        sendMessage(text);
      }
    });

    // Suggestion Cards Click
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

  // =========================================================================
  // 7. Lightweight Pure Markdown Parser
  // =========================================================================
  function formatMarkdown(text) {
    if (!text) return '';

    let html = escapeHtml(text);

    // Multi-line code blocks with language and copy header
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

    // Unordered lists
    html = html.replace(/^\s*[-*]\s+(.*)$/gim, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/gims, '<ul>$1</ul>');

    // Paragraph line breaks
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

  // Kickoff
  checkAuth();
});
