/**
 * Campaign effects for the storefront home page (snow, confetti, hearts, leaves, sparkles)
 * and the campaign countdown.
 *
 * Started by <body data-campaign-effect="snow">. Respects "prefers-reduced-motion",
 * pauses when the tab is hidden, and never blocks clicks (pointer-events: none).
 */

const EFFECTS = {
    snow: { count: 80, colors: ['#ffffff'], shape: 'circle', size: [2, 5], speed: [0.4, 1.2], sway: 0.6 },
    confetti: { count: 90, colors: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'], shape: 'rect', size: [5, 9], speed: [1, 2.4], sway: 1.2 },
    hearts: { count: 30, colors: ['#e11d48', '#f43f5e', '#fb7185'], shape: 'heart', size: [10, 18], speed: [0.5, 1.2], sway: 0.8 },
    leaves: { count: 35, colors: ['#ea580c', '#d97706', '#b45309', '#a16207'], shape: 'leaf', size: [8, 14], speed: [0.6, 1.4], sway: 1.5 },
    sparkles: { count: 45, colors: ['#fde68a', '#ffffff', '#fcd34d'], shape: 'star', size: [2, 4], speed: [0.2, 0.6], sway: 0.3, twinkle: true },
};

const rand = (min, max) => Math.random() * (max - min) + min;

function drawShape(ctx, p) {
    ctx.fillStyle = p.color;
    ctx.globalAlpha = p.alpha;

    switch (p.shape) {
        case 'rect':
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation);
            ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
            ctx.restore();
            break;
        case 'heart': {
            const s = p.size / 2;
            ctx.beginPath();
            ctx.moveTo(p.x, p.y + s / 2);
            ctx.bezierCurveTo(p.x, p.y, p.x - s, p.y, p.x - s, p.y + s / 2);
            ctx.bezierCurveTo(p.x - s, p.y + s, p.x, p.y + s * 1.4, p.x, p.y + s * 1.8);
            ctx.bezierCurveTo(p.x, p.y + s * 1.4, p.x + s, p.y + s, p.x + s, p.y + s / 2);
            ctx.bezierCurveTo(p.x + s, p.y, p.x, p.y, p.x, p.y + s / 2);
            ctx.fill();
            break;
        }
        case 'leaf':
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation);
            ctx.beginPath();
            ctx.ellipse(0, 0, p.size / 2, p.size / 4, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
            break;
        case 'star':
        case 'circle':
        default:
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fill();
    }

    ctx.globalAlpha = 1;
}

function startEffect(name) {
    const config = EFFECTS[name];
    if (!config || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    Object.assign(canvas.style, { position: 'fixed', inset: '0', pointerEvents: 'none', zIndex: '50' });
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    let width = 0;
    let height = 0;
    const resize = () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    };
    resize();
    window.addEventListener('resize', resize);

    // Fewer particles on small screens.
    const count = Math.round(config.count * Math.min(1, width / 1200) + 10);
    const particles = Array.from({ length: count }, () => ({
        x: rand(0, width),
        y: rand(-height, height),
        size: rand(...config.size),
        speed: rand(...config.speed),
        phase: rand(0, Math.PI * 2),
        rotation: rand(0, Math.PI * 2),
        color: config.colors[Math.floor(Math.random() * config.colors.length)],
        shape: config.shape,
        alpha: 1,
    }));

    let frame = null;
    const tick = (t) => {
        ctx.clearRect(0, 0, width, height);
        for (const p of particles) {
            p.y += p.speed;
            p.x += Math.sin(t / 1000 + p.phase) * config.sway * 0.5;
            p.rotation += 0.01;
            if (config.twinkle) {
                p.alpha = 0.5 + 0.5 * Math.sin(t / 300 + p.phase);
            }
            if (p.y > height + 20) {
                p.y = -20;
                p.x = rand(0, width);
            }
            drawShape(ctx, p);
        }
        frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(frame);
        } else {
            frame = requestAnimationFrame(tick);
        }
    });
}

function startCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const end = new Date(el.dataset.countdown).getTime();
        const render = () => {
            const left = Math.max(0, end - Date.now());
            const d = Math.floor(left / 86400000);
            const h = Math.floor((left % 86400000) / 3600000);
            const m = Math.floor((left % 3600000) / 60000);
            const s = Math.floor((left % 60000) / 1000);
            // Labels come from the page in the visitor's language (data-countdown-prefix / data-countdown-done).
            const time = `${d > 0 ? `${d} d ` : ''}${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            el.textContent = left === 0
                ? (el.dataset.countdownDone ?? '¡Últimas horas!')
                : `${el.dataset.countdownPrefix ?? 'Termina en'} ${time}`;
        };
        render();
        setInterval(render, 1000);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const effect = document.body.dataset.campaignEffect;
    if (effect && effect !== 'none') {
        startEffect(effect);
    }
    startCountdowns();
});
