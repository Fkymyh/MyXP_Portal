
const appNames = {
    memo: "メモ帳",
    movies: "映画情報",
    bookmarks: "お気に入り",
    news: "ITニュース",
    weather: "天気",
    calendar: "カレンダー"
};

const windows = document.getElementById("windows");
const taskbarApps = document.getElementById("taskbar-apps");
const startButton = document.getElementById("start-button");
const startMenu = document.getElementById("start-menu");

let highestZIndex = 10;

function openApp(appId) {
    if (!Object.hasOwn(appNames, appId)) return;

    const existing = document.getElementById(`window-${appId}`);

    if (existing) {
        existing.hidden = false;
        bringToFront(existing);
        return;
    }

    const win = document.createElement("section");
    win.className = "xp-window";
    win.id = `window-${appId}`;
    win.style.zIndex = ++highestZIndex;

    const titlebar = document.createElement("div");
    titlebar.className = "window-titlebar";

    const title = document.createElement("span");
    title.textContent = appNames[appId];

    const controls = document.createElement("div");
    controls.className = "window-controls";

    const minimize = document.createElement("button");
    minimize.textContent = "−";
    minimize.title = "最小化";

    const maximize = document.createElement("button");
    maximize.textContent = "□";
    maximize.title = "最大化";

    const close = document.createElement("button");
    close.textContent = "×";
    close.title = "閉じる";

    controls.append(minimize, maximize, close);
    titlebar.append(title, controls);

    const content = document.createElement("div");
    content.className = "window-content";

    let notesController;
    if (appId === "memo") {
        notesController = mountNotes(content);
    } else {
        const message = document.createElement("p");
        message.textContent = `${appNames[appId]}は開発予定です。`;
        content.append(message);
    }

    win.append(titlebar, content);
    windows.append(win);

    const taskButton = document.createElement("button");
    taskButton.className = "task-button";
    taskButton.textContent = appNames[appId];
    taskbarApps.append(taskButton);

    taskButton.addEventListener("click", () => {
        if (win.hidden || Number(win.style.zIndex) !== highestZIndex) {
            win.hidden = false;
            bringToFront(win);
        } else {
            win.hidden = true;
        }
    });

    minimize.addEventListener("click", () => {
        win.hidden = true;
    });

    close.addEventListener("click", () => {
        if (notesController && !notesController.close()) return;
        win.remove();
        taskButton.remove();
    });

    let maximized = false;
    let previousStyle = "";

    maximize.addEventListener("click", () => {
        if (!maximized) {
            previousStyle = win.style.cssText;

            Object.assign(win.style, {
                top: "0px",
                left: "0px",
                width: "100%",
                height: "100%"
            });
        } else {
            win.style.cssText = previousStyle;
        }

        maximized = !maximized;
        bringToFront(win);
    });

    win.addEventListener("pointerdown", () => bringToFront(win));

    let dragging = false;
    let offsetX = 0;
    let offsetY = 0;

    titlebar.addEventListener("pointerdown", (e) => {
        if (e.button !== 0 || e.target.closest("button") || maximized) return;

        dragging = true;

        const rect = win.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;

        titlebar.setPointerCapture(e.pointerId);
        bringToFront(win);
    });

    titlebar.addEventListener("pointermove", (e) => {
        if (!dragging) return;

        const desktop = document.getElementById("desktop");
        const bounds = desktop.getBoundingClientRect();

        const x = Math.max(0, Math.min(
            e.clientX - offsetX - bounds.left,
            bounds.width - win.offsetWidth
        ));

        const y = Math.max(0, Math.min(
            e.clientY - offsetY - bounds.top,
            bounds.height - win.offsetHeight
        ));

        win.style.left = `${x}px`;
        win.style.top = `${y}px`;
    });

    titlebar.addEventListener("pointerup", () => {
        dragging = false;
    });

    titlebar.addEventListener("pointercancel", () => {
        dragging = false;
    });
    titlebar.addEventListener("lostpointercapture", () => {
        dragging = false;
    });
}

function bringToFront(win) {
    win.style.zIndex = ++highestZIndex;
}

document.querySelectorAll("[data-app]").forEach(button => {
    button.addEventListener("dblclick", () => {
        openApp(button.dataset.app);
        startMenu.hidden = true;
    });

    if (button.closest("#start-menu")) {
        button.addEventListener("click", () => {
            openApp(button.dataset.app);
            startMenu.hidden = true;
        });
    } else {
        button.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openApp(button.dataset.app);
            }
        });
    }
});

startButton.addEventListener("click", () => {
    startMenu.hidden = !startMenu.hidden;
});

document.addEventListener("pointerdown", (event) => {
    if (!startMenu.contains(event.target) && !startButton.contains(event.target)) {
        startMenu.hidden = true;
    }
});
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") startMenu.hidden = true;
});
window.addEventListener("resize", () => {
    document.querySelectorAll(".xp-window").forEach(win => {
        const desktop = document.getElementById("desktop");
        win.style.left = `${Math.max(0, Math.min(win.offsetLeft, desktop.clientWidth - win.offsetWidth))}px`;
        win.style.top = `${Math.max(0, Math.min(win.offsetTop, desktop.clientHeight - win.offsetHeight))}px`;
    });
});

function updateClock() {
    const now = new Date();

    document.getElementById("clock").textContent =
        now.toLocaleTimeString("ja-JP", {
            hour: "2-digit",
            minute: "2-digit"
        });
}

updateClock();
setInterval(updateClock, 1000);
