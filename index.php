<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Input Sync 試作</title>
  <style>
    :root {
      color-scheme: light;
      --ink: #192b27;
      --muted: #6d7d77;
      --line: #dce5df;
      --paper: #f5f7f2;
      --card: #fff;
      --green: #176d50;
      --green-pale: #e5f2eb;
      --amber: #9b6612;
      --red: #a23e38;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--paper); color: var(--ink); }
    .shell { width: min(100% - 36px, 960px); margin: 0 auto; padding: 38px 0 56px; }
    header { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 54px; }
    .brand { display: flex; align-items: center; gap: 11px; font-weight: 750; letter-spacing: -.02em; }
    .mark { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 12px; background: var(--green); color: white; font-size: 18px; }
    .eyebrow { color: var(--green); font-size: 12px; font-weight: 750; letter-spacing: .13em; text-transform: uppercase; }
    .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--line); border-radius: 999px; background: var(--card); color: var(--muted); font-size: 13px; }
    .dot { width: 8px; height: 8px; border-radius: 50%; background: #c4902c; }
    .status[data-state="connected"] .dot { background: #2b9a6d; }
    .status[data-state="error"] .dot { background: #c45149; }
    h1 { max-width: 720px; margin: 12px 0 14px; font-size: clamp(34px, 6vw, 58px); line-height: 1.08; letter-spacing: -.055em; }
    .intro { max-width: 650px; margin: 0; color: var(--muted); font-size: 16px; line-height: 1.8; }
    .grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(260px, .85fr); gap: 18px; margin-top: 32px; }
    .card { border: 1px solid var(--line); border-radius: 18px; background: var(--card); padding: 24px; box-shadow: 0 10px 32px rgb(25 43 39 / 4%); }
    .card h2 { margin: 0; font-size: 18px; letter-spacing: -.02em; }
    .card-note { margin: 7px 0 23px; color: var(--muted); font-size: 13px; line-height: 1.6; }
    .field { margin: 18px 0 0; }
    label, legend { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
    input[type="text"], input[type="email"], textarea { width: 100%; min-height: 46px; padding: 11px 13px; border: 1px solid var(--line); border-radius: 10px; background: #fcfdfb; color: var(--ink); font: inherit; outline: none; transition: border-color .15s, box-shadow .15s; }
    textarea { min-height: 132px; line-height: 1.65; resize: vertical; }
    input:focus, textarea:focus { border-color: #479578; box-shadow: 0 0 0 3px rgb(71 149 120 / 14%); }
    fieldset { margin: 20px 0 0; padding: 0; border: 0; }
    .choice-row { display: flex; flex-wrap: wrap; gap: 12px 20px; }
    .choice { display: inline-flex; align-items: center; gap: 8px; color: #42534d; font-size: 14px; }
    .choice input { accent-color: var(--green); }
    .check-row { margin-top: 22px; }
    .check-row .choice { line-height: 1.5; }
    .actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 26px; padding-top: 19px; border-top: 1px solid #edf1ed; }
    .hint { color: var(--muted); font-size: 12px; }
    button { padding: 9px 13px; border: 1px solid var(--line); border-radius: 9px; background: white; color: var(--ink); font: inherit; font-size: 13px; cursor: pointer; }
    button:hover { border-color: #9daf9f; background: #f8faf7; }
    .textarea-fit-button { width: 100%; margin-top: 9px; line-height: 1.45; }
    .preview { display: grid; gap: 0; }
    .preview-item { display: flex; justify-content: space-between; gap: 14px; padding: 15px 0; border-bottom: 1px solid #edf1ed; font-size: 14px; }
    .preview-item:first-child { padding-top: 4px; }
    .preview-item:last-child { border-bottom: 0; }
    .preview-key { color: var(--muted); }
    .preview-value { max-width: 62%; overflow-wrap: anywhere; text-align: right; font-weight: 650; }
    .format-picker { display: flex; flex-direction: column; gap: 8px; margin: 2px 0 14px; }
    .format-picker label { margin: 0; }
    .format-picker select { max-width: 100%; min-height: 40px; padding: 8px 11px; border: 1px solid var(--line); border-radius: 9px; background: #fcfdfb; color: var(--ink); font: inherit; font-size: 13px; }
    .preview-note { align-items: stretch; flex-direction: column; gap: 8px; }
    .preview-note .preview-value { max-width: 100%; text-align: left; white-space: pre-wrap; font-weight: 400; }
    .preview-content.raw-content { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
    .preview-content.markdown-content { white-space: normal; }
    .markdown-content p { margin: 0 0 .8em; }
    .markdown-content p:last-child { margin-bottom: 0; }
    .markdown-content h1, .markdown-content h2, .markdown-content h3, .markdown-content h4, .markdown-content h5, .markdown-content h6 { margin: .8em 0 .35em; font-size: 1.1em; }
    .markdown-content blockquote { margin: .5em 0; padding-left: .8em; border-left: 3px solid var(--line); color: var(--muted); }
    .markdown-content ul, .markdown-content ol { margin: .4em 0; padding-left: 1.5em; }
    .markdown-content pre { overflow: auto; padding: 10px; border-radius: 8px; background: #f4f7f3; }
    .markdown-content code { padding: 1px 4px; border-radius: 4px; background: #f0f3ef; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .92em; }
    .markdown-content pre code { padding: 0; background: transparent; }
    .markdown-content a { color: var(--green); }
    .revision { margin: 18px 0 0; padding: 12px 13px; border-radius: 10px; background: #f4f7f3; color: var(--muted); font-size: 12px; line-height: 1.6; }
    .footnote { margin: 19px 2px 0; color: var(--muted); font-size: 12px; line-height: 1.65; }
    @media (max-width: 700px) {
      .shell { width: min(100% - 24px, 560px); padding-top: 24px; }
      header { margin-bottom: 40px; }
      .grid { grid-template-columns: 1fr; }
      .card { padding: 20px; }
      .status { font-size: 12px; }
    }
  </style>
</head>
<body>
  <main class="shell">
    <header>
      <div class="brand"><span class="mark" aria-hidden="true">↔</span><span>Input Sync</span></div>
      <div class="status" id="connection" data-state="connecting" role="status" aria-live="polite">
        <span class="dot" aria-hidden="true"></span><span id="connectionText">接続中</span>
      </div>
    </header>

    <section aria-labelledby="pageTitle">
      <div class="eyebrow">PHP prototype</div>
      <h1 id="pageTitle">入力状態を、<br>別の画面にも。</h1>
      <p class="intro">フォームに入力すると、同じページを開いている別タブにも内容を同期します。タブを2つ開いて、入力とチェック状態の変化を試せます。</p>
    </section>

    <div class="grid">
      <section class="card" aria-labelledby="formTitle">
        <h2 id="formTitle">同期する入力</h2>
        <p class="card-note">文字入力は入力中に保存されます。ラジオボタンとチェックボックスも同期対象です。</p>
        <form id="syncForm" autocomplete="off">
          <div class="field">
            <label for="displayName">表示名</label>
            <input id="displayName" name="display_name" type="text" data-sync maxlength="120" placeholder="例：Aoi">
          </div>
          <div class="field">
            <label for="emailAddress">メールアドレス</label>
            <input id="emailAddress" name="email_address" type="email" data-sync maxlength="254" placeholder="aoi@example.com">
          </div>
          <div class="field">
            <label for="freeText">自由記入欄</label>
            <textarea id="freeText" name="free_text" data-sync maxlength="100000" placeholder="自由に入力してください。入力内容は別タブにも同期されます。"></textarea>
            <button type="button" class="textarea-fit-button" id="textareaFitButton" aria-pressed="false">入力文字の文字数や行数に縦幅を合わせる</button>
          </div>
          <fieldset>
            <legend>作業モード</legend>
            <div class="choice-row">
              <label class="choice"><input type="radio" name="work_mode" value="focus" data-sync> 集中</label>
              <label class="choice"><input type="radio" name="work_mode" value="collab" data-sync> 共同</label>
              <label class="choice"><input type="radio" name="work_mode" value="break" data-sync> 休憩</label>
            </div>
          </fieldset>
          <div class="check-row">
            <label class="choice"><input type="checkbox" name="notifications" data-sync> 通知を受け取る</label>
          </div>
          <div class="actions">
            <span class="hint" id="saveHint">変更は自動保存されます</span>
            <button type="button" id="resetButton">全端末の状態をリセット</button>
          </div>
        </form>
      </section>

      <aside class="card" aria-labelledby="previewTitle">
        <h2 id="previewTitle">共有されている状態</h2>
        <p class="card-note">サーバーに保存された最新値です。</p>
        <div class="format-picker">
          <label for="previewFormat">自由記入欄の表示形式</label>
          <select id="previewFormat">
            <option value="raw">RAWデータ</option>
            <option value="markdown">Markdownパース済み</option>
            <option value="plain">プレーンテキスト</option>
          </select>
        </div>
        <div class="preview" aria-live="polite">
          <div class="preview-item"><span class="preview-key">表示名</span><span class="preview-value" id="previewName">—</span></div>
          <div class="preview-item"><span class="preview-key">メール</span><span class="preview-value" id="previewEmail">—</span></div>
          <div class="preview-item"><span class="preview-key">作業モード</span><span class="preview-value" id="previewMode">未選択</span></div>
          <div class="preview-item"><span class="preview-key">通知</span><span class="preview-value" id="previewNotifications">オフ</span></div>
          <div class="preview-item preview-note"><span class="preview-key">自由記入欄</span><div class="preview-value preview-content" id="previewNote">—</div></div>
        </div>
        <p class="revision" id="revisionInfo">状態を読み込んでいます。</p>
      </aside>
    </div>
    <p class="footnote">この試作は同じサーバー上の全ブラウザーで1つの状態を共有します。ローカル開発・動作確認向けです。</p>
  </main>

  <script>
    (() => {
      const form = document.getElementById('syncForm');
      const connection = document.getElementById('connection');
      const connectionText = document.getElementById('connectionText');
      const saveHint = document.getElementById('saveHint');
      const revisionInfo = document.getElementById('revisionInfo');
      const previewFormat = document.getElementById('previewFormat');
      const previewNote = document.getElementById('previewNote');
      const freeText = document.getElementById('freeText');
      const textareaFitButton = document.getElementById('textareaFitButton');
      const initialTextareaHeight = freeText.getBoundingClientRect().height;
      const modeLabels = { focus: '集中', collab: '共同', break: '休憩' };
      const pending = new Map();
      let debounceTimer;
      let seenRevision = -1;
      let requestInFlight = false;
      let currentFreeText = '';
      let textareaFitMode = false;

      function setConnection(state, message) {
        connection.dataset.state = state;
        connectionText.textContent = message;
      }

      function valueFor(group) {
        const first = group[0];
        if (first.type === 'checkbox') return first.checked;
        if (first.type === 'radio') {
          const checked = group.find((input) => input.checked);
          return checked ? checked.value : null;
        }
        return first.value;
      }

      function groups() {
        const result = new Map();
        for (const input of form.querySelectorAll('[data-sync][name]')) {
          if (!result.has(input.name)) result.set(input.name, []);
          result.get(input.name).push(input);
        }
        return result;
      }

      function resizeTextareaToContent() {
        if (!textareaFitMode) return;
        freeText.style.height = 'auto';
        const borderHeight = freeText.offsetHeight - freeText.clientHeight;
        freeText.style.height = (freeText.scrollHeight + borderHeight) + 'px';
        freeText.style.overflowY = 'hidden';
      }

      function setTextareaFitMode(enabled) {
        textareaFitMode = enabled;
        textareaFitButton.setAttribute('aria-pressed', String(enabled));
        if (enabled) {
          textareaFitButton.textContent = '初期縦幅に戻す';
          resizeTextareaToContent();
        } else {
          freeText.style.height = initialTextareaHeight + 'px';
          freeText.style.overflowY = '';
          textareaFitButton.textContent = '入力文字の文字数や行数に縦幅を合わせる';
        }
      }

      function applyState(state) {
        for (const [name, value] of Object.entries(state.values || {})) {
          const group = groups().get(name);
          if (!group || group.includes(document.activeElement)) continue;
          for (const input of group) {
            if (input.type === 'checkbox') input.checked = Boolean(value);
            else if (input.type === 'radio') input.checked = String(value) === input.value;
            else input.value = value == null ? '' : String(value);
          }
        }
        resizeTextareaToContent();
        renderPreview(state.values || {});
        seenRevision = Number(state.revision || 0);
        const savedAt = state.updated_at ? new Date(state.updated_at * 1000).toLocaleTimeString() : 'まだ保存されていません';
        revisionInfo.textContent = `更新番号 ${seenRevision} · 最終更新 ${savedAt}`;
      }

      function escapeHtml(value) {
        const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(value).replace(/[&<>"']/g, (character) => entities[character]);
      }

      function stripMarkdown(source) {
        const tick = String.fromCharCode(96);
        let text = source.replace(/\r\n?/g, '\n');
        text = text.replace(new RegExp('^' + tick + '{3}[^\\n]*\\n|^' + tick + '{3}[ \\t]*$', 'gm'), '');
        text = text.replace(/^#{1,6}[ \t]+/gm, '');
        text = text.replace(/^>[ \t]?/gm, '');
        text = text.replace(/^[ \t]*[-+*][ \t]+/gm, '• ');
        text = text.replace(/^[ \t]*\d+\.[ \t]+/gm, '');
        text = text.replace(/!\[([^\]]*)\]\([^)\n]*\)/g, '$1');
        text = text.replace(/\[([^\]]+)\]\(https?:\/\/[^)\s]+\)/g, '$1');
        text = text.replace(new RegExp(tick + '([^' + tick + '\\n]+)' + tick, 'g'), '$1');
        text = text.replace(/\*\*([^*\n]+)\*\*|__([^_\n]+)__/g, (match, stars, underscores) => stars || underscores);
        text = text.replace(/\*([^*\n]+)\*|_([^_\n]+)_/g, (match, stars, underscores) => stars || underscores);
        return text;
      }

      function renderInlineMarkdown(source) {
        let safe = escapeHtml(source);
        const tick = String.fromCharCode(96);
        const codePattern = new RegExp(tick + '([^' + tick + '\\n]+)' + tick, 'g');
        const fragments = [];
        let markerPrefix = '\uE000md0';
        let markerNumber = 0;
        while (safe.includes(markerPrefix)) {
          markerNumber += 1;
          markerPrefix = '\uE000md' + markerNumber;
        }
        const reserve = (html) => {
          const token = markerPrefix + fragments.length + '\uE001';
          fragments.push(html);
          return token;
        };

        safe = safe.replace(codePattern, (match, code) => reserve('<code>' + code + '</code>'));
        safe = safe.replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/g, (match, label, url) => {
          return reserve('<a href="' + url + '" rel="noopener noreferrer">' + label + '</a>');
        });
        safe = safe.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/__([^_\n]+)__/g, '<strong>$1</strong>');
        safe = safe.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
        safe = safe.replace(/_([^_\n]+)_/g, '<em>$1</em>');

        const tokenPattern = new RegExp(markerPrefix + '(\\d+)\\uE001', 'g');
        return safe.replace(tokenPattern, (token, index) => fragments[Number(index)] || '');
      }

      function renderMarkdown(source) {
        const tick = String.fromCharCode(96);
        const fence = tick.repeat(3);
        const lines = source.replace(/\r\n?/g, '\n').split('\n');
        const parts = [];
        let paragraph = [];
        let listTag = '';
        let listItems = [];
        let quoteLines = [];
        let codeLines = [];
        let inFence = false;

        const flushParagraph = () => {
          if (!paragraph.length) return;
          parts.push('<p>' + paragraph.map(renderInlineMarkdown).join('<br>') + '</p>');
          paragraph = [];
        };
        const flushList = () => {
          if (!listItems.length) return;
          parts.push('<' + listTag + '>' + listItems.map((item) => '<li>' + item + '</li>').join('') + '</' + listTag + '>');
          listTag = '';
          listItems = [];
        };
        const flushQuote = () => {
          if (!quoteLines.length) return;
          parts.push('<blockquote><p>' + quoteLines.map(renderInlineMarkdown).join('<br>') + '</p></blockquote>');
          quoteLines = [];
        };
        const flushBlocks = () => {
          flushParagraph();
          flushList();
          flushQuote();
        };

        for (const line of lines) {
          if (line.trimStart().startsWith(fence)) {
            if (inFence) {
              parts.push('<pre><code>' + escapeHtml(codeLines.join('\n')) + '</code></pre>');
              codeLines = [];
              inFence = false;
            } else {
              flushBlocks();
              inFence = true;
            }
            continue;
          }
          if (inFence) {
            codeLines.push(line);
            continue;
          }
          if (!line.trim()) {
            flushBlocks();
            continue;
          }

          const heading = /^(#{1,6})[ \t]+(.+)$/.exec(line);
          if (heading) {
            flushBlocks();
            const tag = 'h' + heading[1].length;
            parts.push('<' + tag + '>' + renderInlineMarkdown(heading[2]) + '</' + tag + '>');
            continue;
          }

          const listItem = /^[ \t]*([-+*]|\d+\.)[ \t]+(.+)$/.exec(line);
          if (listItem) {
            flushParagraph();
            flushQuote();
            const nextListTag = /^\d/.test(listItem[1]) ? 'ol' : 'ul';
            if (listTag && listTag !== nextListTag) flushList();
            listTag = nextListTag;
            listItems.push(renderInlineMarkdown(listItem[2]));
            continue;
          }

          const quote = /^[ \t]*>[ \t]?(.*)$/.exec(line);
          if (quote) {
            flushParagraph();
            flushList();
            quoteLines.push(quote[1]);
            continue;
          }

          flushList();
          flushQuote();
          paragraph.push(line);
        }

        if (inFence) parts.push('<pre><code>' + escapeHtml(codeLines.join('\n')) + '</code></pre>');
        flushBlocks();
        return parts.join('');
      }

      function renderFreeText(source) {
        previewNote.className = 'preview-value preview-content';
        if (!source) {
          previewNote.textContent = '—';
          return;
        }

        if (previewFormat.value === 'markdown') {
          previewNote.classList.add('markdown-content');
          previewNote.innerHTML = renderMarkdown(source);
        } else if (previewFormat.value === 'plain') {
          previewNote.classList.add('plain-content');
          previewNote.innerHTML = escapeHtml(stripMarkdown(source)).replace(/\r\n?|\n/g, '<br>');
        } else {
          previewNote.classList.add('raw-content');
          previewNote.textContent = source;
        }
      }

      function renderPreview(values) {
        document.getElementById('previewName').textContent = values.display_name || '—';
        document.getElementById('previewEmail').textContent = values.email_address || '—';
        document.getElementById('previewMode').textContent = modeLabels[values.work_mode] || '未選択';
        document.getElementById('previewNotifications').textContent = values.notifications ? 'オン' : 'オフ';
        currentFreeText = typeof values.free_text === 'string' ? values.free_text : '';
        renderFreeText(currentFreeText);
      }

      async function refresh() {
        if (requestInFlight) return;
        try {
          const response = await fetch('api.php', { cache: 'no-store' });
          if (!response.ok) throw new Error('state fetch failed');
          const state = await response.json();
          if (Number(state.revision || 0) > seenRevision) applyState(state);
          setConnection('connected', '同期中');
        } catch (error) {
          setConnection('error', '接続できません');
        }
      }

      async function flush() {
        if (pending.size === 0 || requestInFlight) return;
        const updates = Object.fromEntries(pending);
        pending.clear();
        requestInFlight = true;
        saveHint.textContent = '保存しています…';
        try {
          const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ updates }),
          });
          if (!response.ok) throw new Error('state save failed');
          applyState(await response.json());
          setConnection('connected', '同期中');
          saveHint.textContent = '変更を保存しました';
        } catch (error) {
          for (const [key, value] of Object.entries(updates)) pending.set(key, value);
          setConnection('error', '保存に失敗しました');
          saveHint.textContent = '接続を確認して再試行します';
        } finally {
          requestInFlight = false;
          if (pending.size) window.setTimeout(flush, 150);
        }
      }

      function queueChange(input) {
        const group = groups().get(input.name);
        if (!group) return;
        if (input.type === 'radio' && !input.checked) return;
        pending.set(input.name, valueFor(group));
        saveHint.textContent = '入力を同期しています…';
        window.clearTimeout(debounceTimer);
        const debounceMs = input.name === 'free_text' ? 500 : 220;
        debounceTimer = window.setTimeout(flush, debounceMs);
      }

      previewFormat.addEventListener('change', () => renderFreeText(currentFreeText));
      textareaFitButton.addEventListener('click', () => setTextareaFitMode(!textareaFitMode));
      window.addEventListener('resize', resizeTextareaToContent);

      form.addEventListener('input', (event) => {
        if (event.target.matches('[data-sync]')) queueChange(event.target);
        if (event.target === freeText) resizeTextareaToContent();
      });
      form.addEventListener('change', (event) => {
        if (event.target.matches('[data-sync]')) queueChange(event.target);
      });

      document.getElementById('resetButton').addEventListener('click', async () => {
        if (!window.confirm('共有中の入力状態をすべてリセットしますか？')) return;
        try {
          const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reset: true }),
          });
          if (!response.ok) throw new Error('state reset failed');
          pending.clear();
          applyState(await response.json());
          saveHint.textContent = '共有状態をリセットしました';
        } catch (error) {
          saveHint.textContent = 'リセットできませんでした';
        }
      });

      refresh();
      window.setInterval(refresh, 700);
    })();
  </script>
</body>
</html>
