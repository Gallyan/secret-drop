const CIRCUMFERENCE = 2 * Math.PI * 12; // r=12

let ringTimer = null;

export function startRing(durationMs) {
    const ring = document.getElementById('pollRingProgress');
    if (!ring) {
        return;
    }

    if (ringTimer) {
        clearInterval(ringTimer);
    }

    const svg = document.getElementById('pollRing');
    const titleEl = document.getElementById('pollRingTitle');
    const titleTemplate = svg ? svg.dataset.titleTemplate : '';

    const start = Date.now();
    const tick = () => {
        const elapsed = Date.now() - start;
        const progress = Math.min(elapsed / durationMs, 1);
        ring.style.strokeDashoffset = CIRCUMFERENCE * (1 - progress);

        if (titleEl && titleTemplate) {
            const remaining = Math.max(0, Math.ceil((durationMs - elapsed) / 1000));
            const text = titleTemplate.replace(':seconds', String(remaining));
            if (titleEl.textContent !== text) {
                titleEl.textContent = text;
            }
        }
    };

    tick();
    ringTimer = setInterval(tick, 200);
}

export function resetRing() {
    const ring = document.getElementById('pollRingProgress');
    if (ring) {
        ring.style.strokeDashoffset = CIRCUMFERENCE;
    }

    const titleEl = document.getElementById('pollRingTitle');
    if (titleEl) {
        titleEl.textContent = '';
    }

    if (ringTimer) {
        clearInterval(ringTimer);
    }
}
