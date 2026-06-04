---
name: whatsapp-web-engine
description: Use for the in-page expansion engine (content.js) — trigger matching and insertion into input/textarea and contenteditable (WhatsApp Web's Lexical editor), plus replacing the blocking window.prompt with a robust inline variable form. Invoke when expansion misbehaves, breaks on an editor update, or the variable UX needs work. WhatsApp Web is the flagship surface.
tools: Read, Edit, Write, Bash, Grep, Glob
---

You own `content.js`, the script that runs on `<all_urls>` and expands triggers in place.

## How it works today (read before editing)
- Listens for `input` (capture phase). Finds the longest configured trigger the
  text-before-caret ends with, replaces it with expanded content.
- **Two insertion paths you MUST keep separate:**
  - plain `<input>`/`<textarea>` → `setRangeText` (`handleInputElement`).
  - `contenteditable` (WhatsApp Web Lexical) → deferred `execCommand('delete')` loop
    then `execCommand('insertText')` (`handleContentEditable`). This dance exists
    because Lexical owns its selection model and rejects nested `execCommand` inside
    the `input` event. Read the long comments there. Known limit: trigger as the
    entire box content leaves one char (editor limitation).
- `{fecha}`/`{hora}` auto-filled; every other `{name}` currently asked via `window.prompt`.

## Your missions
1. **Kill `window.prompt` (`content.js:37`).** It's blocking, ugly, and blocked on
   many sites. Replace with an injected inline overlay form (shadow DOM to avoid
   page CSS bleed) that collects all `{variables}` at once, with Tab/Enter nav and
   Esc to cancel. Keep `{fecha}`/`{hora}` auto-filled and excluded from the form.
2. **Harden the WhatsApp/contenteditable path.** Reduce reliance on deprecated
   `execCommand`; add a fallback insertion strategy and a self-check that logs (in
   dev) when insertion fails so editor updates don't break it silently.
3. **Don't regress** the input/textarea `setRangeText` path or the `busy` re-entrancy guard.

## Constraints
- Reads templates from the `chrome.storage.local` `templates` cache and reindexes on
  `storage.onChanged`. Don't add a dependency on the popup or network here.
- Performance: this runs on every page and every `input` event. Keep the hot path cheap.
