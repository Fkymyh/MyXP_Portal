"use strict";

function mountNotes(container) {
    // 固定のHTMLだけを使用し、保存データはvalue/textContentで表示します。
    container.innerHTML = `
        <div class="notes-toolbar">
            <button type="button" data-action="new">新規</button>
            <button type="button" data-action="save">保存</button>
            <button type="button" data-action="delete">削除</button>
            <button type="button" data-action="reload">一覧を再読込</button>
        </div>
        <div class="notes-layout">
            <select class="notes-list" size="5" aria-label="メモ一覧"></select>
            <div class="notes-editor">
                <label>タイトル<input class="notes-title" type="text" maxlength="600"></label>
                <label class="notes-body">本文<textarea aria-label="本文"></textarea></label>
            </div>
        </div>
        <p class="notes-status" role="status" aria-live="polite"></p>`;
    container.classList.add("notes-content");
    const list = container.querySelector("select");
    const title = container.querySelector("input");
    const content = container.querySelector("textarea");
    const status = container.querySelector(".notes-status");
    const buttons = container.querySelectorAll("button");
    let notes = [];
    let id = null;
    let dirty = false;
    let busy = false;
    let timer;
    let disposed = false;

    function message(text, error = false) {
        status.textContent = text;
        status.classList.toggle("error", error);
    }
    function lock(value) {
        busy = value;
        buttons.forEach(button => { button.disabled = value; });
        list.disabled = title.disabled = content.disabled = value;
    }
    async function request(method, data) {
        const response = await fetch("api/notes.php", {
            method,
            headers: data ? { "Content-Type": "application/json" } : {},
            body: data ? JSON.stringify(data) : undefined,
            signal: AbortSignal.timeout(15000)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || "通信に失敗しました。");
        return result;
    }
    function renderList() {
        list.replaceChildren();
        notes.forEach(note => {
            const option = document.createElement("option");
            option.value = note.id;
            option.textContent = note.title;
            list.append(option);
        });
        list.value = id === null ? "" : String(id);
    }
    function select(note) {
        clearTimeout(timer);
        id = note?.id ?? null;
        title.value = note?.title ?? "";
        content.value = note?.content ?? "";
        dirty = false;
        list.value = id === null ? "" : String(id);
        message(note ? "保存済みのメモを表示しています。" : "新規メモ：タイトルを入力してください。");
    }
    function canDiscard() {
        return !busy && (!dirty || confirm("未保存の変更を破棄しますか？"));
    }
    async function save() {
        clearTimeout(timer);
        if (busy || disposed || !dirty) return;
        if (!title.value.trim()) {
            message("タイトルを入力してください。変更は未保存です。", true);
            return;
        }
        lock(true);
        message("保存中…");
        try {
            const result = await request(id === null ? "POST" : "PUT", {
                id, title: title.value, content: content.value
            });
            id = result.note.id;
            notes = [result.note, ...notes.filter(note => note.id !== id)];
            title.value = result.note.title;
            dirty = false;
            renderList();
            message("保存しました。");
        } catch (error) {
            message(`保存を確認できません：${error.message} 入力は保持されています。保存ボタンで再試行できます。`, true);
        } finally { lock(false); }
    }
    async function reload() {
        if (!canDiscard()) return;
        clearTimeout(timer);
        lock(true);
        try {
            const result = await request("GET");
            notes = result.notes;
            const selected = notes.find(note => note.id === id) ?? notes[0];
            renderList();
            select(selected);
        } catch (error) {
            message(`一覧を取得できません：${error.message}`, true);
        } finally { lock(false); }
    }
    buttons.forEach(button => button.addEventListener("click", async () => {
        switch (button.dataset.action) {
            case "save": await save(); break;
            case "reload": await reload(); break;
            case "new":
                if (canDiscard()) { select(null); title.focus(); }
                break;
            case "delete":
                if (busy || id === null || !confirm("このメモを削除しますか？ 未保存の変更も破棄されます。")) return;
                clearTimeout(timer);
                lock(true);
                try {
                    await request("DELETE", { id });
                    notes = notes.filter(note => note.id !== id);
                    renderList();
                    select(notes[0]);
                    message("削除しました。");
                } catch (error) { message(`削除できません：${error.message}`, true); }
                finally { lock(false); }
                break;
        }
    }));
    list.addEventListener("change", () => {
        const next = notes.find(note => note.id === Number(list.value));
        if (canDiscard()) select(next);
        else list.value = id === null ? "" : String(id);
    });
    [title, content].forEach(field => field.addEventListener("input", () => {
        dirty = true;
        message("未保存（入力停止から1秒後に自動保存）");
        clearTimeout(timer);
        timer = setTimeout(save, 1000);
    }));
    function beforeUnload(event) {
        if (dirty || busy) {
            event.preventDefault();
            event.returnValue = "";
        }
    }
    window.addEventListener("beforeunload", beforeUnload);
    reload();
    return {
        close() {
            if (!canDiscard()) return false;
            disposed = true;
            clearTimeout(timer);
            window.removeEventListener("beforeunload", beforeUnload);
            return true;
        }
    };
}
