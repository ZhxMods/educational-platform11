/**
 * xp-system.js — XP Animation System
 * Handles lesson completion, XP rewards, level-ups, and confetti
 */

'use strict';

// ── CONFIG ────────────────────────────────────────────────────
// Safely determine base URL from meta tag or fallback to origin
const _siteBase = (() => {
    const metaTag = document.querySelector('meta[name="site-url"]');
    if (metaTag && metaTag.content) {
        return metaTag.content.replace(/\/$/, '');
    }
    // Fallback to origin
    return window.location.origin;
})();

const XP = {
    endpointUrl : _siteBase + '/ajax/complete_lesson.php',
    csrfToken   : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    colors      : {
        primary : '#1d4ed8',
        gold    : '#f59e0b',
        success : '#22c55e',
        error   : '#ef4444',
    },
    confetti: {
        count  : 90,
        colors : ['#1d4ed8','#3b82f6','#fde68a','#f59e0b','#22c55e','#a78bfa','#fb7185','#34d399'],
        gravity: 0.55,
        drag   : 0.075,
    }
};

// ── TOAST ─────────────────────────────────────────────────────
const Toast = (() => {
    let container;
    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }
    function show(type, message, duration = 3500) {
        const c     = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icons = { success:'✦', error:'✕', warning:'⚠', info:'ℹ' };
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] ?? '•'}</span>
            <span class="toast-msg">${message}</span>
            <button class="toast-close" aria-label="Close">✕</button>
        `;
        const close = () => {
            toast.classList.add('toast-out');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        };
        toast.querySelector('.toast-close').addEventListener('click', close);
        c.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('toast-in'));
        if (duration > 0) setTimeout(close, duration);
        return toast;
    }
    return { show };
})();

// ── COUNT-UP ──────────────────────────────────────────────────
function countUp(el, from, to, duration = 1200) {
    const start = performance.now();
    const diff  = to - from;
    const ease  = t => 1 - Math.pow(1 - t, 3);
    function tick(now) {
        const elapsed  = now - start;
        const progress = Math.min(elapsed / duration, 1);
        el.textContent = Math.round(from + diff * ease(progress)).toLocaleString();
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

// ── FLOATING XP BADGE ─────────────────────────────────────────
function floatXP(amount, anchor) {
    const badge = document.createElement('div');
    badge.className = 'xp-float';
    badge.textContent = `+${amount} XP`;
    const rect = anchor.getBoundingClientRect();
    badge.style.left = `${rect.left + rect.width / 2}px`;
    badge.style.top  = `${rect.top  + window.scrollY}px`;
    document.body.appendChild(badge);
    requestAnimationFrame(() => badge.classList.add('xp-float-active'));
    badge.addEventListener('animationend', () => badge.remove(), { once: true });
}

// ── CONFETTI ──────────────────────────────────────────────────
function launchConfetti(originEl) {
    const canvas = document.createElement('canvas');
    canvas.id = 'confetti-canvas';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    const rect = originEl.getBoundingClientRect();
    const ox = rect.left + rect.width  / 2;
    const oy = rect.top  + rect.height / 2;
    const { count, colors, gravity, drag } = XP.confetti;
    const particles = Array.from({ length: count }, () => ({
        x: ox, y: oy,
        vx: (Math.random() - 0.5) * 18,
        vy: (Math.random() - 1.5) * 14,
        r: Math.random() * 5 + 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        spin: (Math.random() - 0.5) * 0.3,
        angle: Math.random() * Math.PI * 2,
        shape: Math.random() > 0.5 ? 'rect' : 'circle',
        w: Math.random() * 10 + 5,
        h: Math.random() * 6  + 3,
        alpha: 1,
        decay: Math.random() * 0.012 + 0.006,
    }));
    let rafId;
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = false;
        for (const p of particles) {
            if (p.alpha <= 0) continue;
            alive = true;
            p.vy += gravity; p.vx *= (1 - drag);
            p.x  += p.vx;   p.y  += p.vy;
            p.angle += p.spin; p.alpha -= p.decay;
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.alpha);
            ctx.fillStyle   = p.color;
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle);
            if (p.shape === 'circle') {
                ctx.beginPath(); ctx.arc(0,0,p.r,0,Math.PI*2); ctx.fill();
            } else {
                ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
            }
            ctx.restore();
        }
        if (alive) { rafId = requestAnimationFrame(draw); }
        else { canvas.remove(); }
    }
    rafId = requestAnimationFrame(draw);
    setTimeout(() => { cancelAnimationFrame(rafId); canvas.remove(); }, 4000);
}

// ── LEVEL-UP MODAL ────────────────────────────────────────────
function showLevelUp(newLevel) {
    document.getElementById('levelup-modal')?.remove();
    const modal = document.createElement('div');
    modal.id = 'levelup-modal';
    modal.innerHTML = `
        <div class="levelup-backdrop"></div>
        <div class="levelup-card" role="dialog" aria-modal="true">
            <div class="levelup-stars"><span>⭐</span><span>⭐</span><span>⭐</span></div>
            <div class="levelup-badge-wrap">
                <div class="levelup-badge">${newLevel}</div>
                <div class="levelup-pulse"></div>
            </div>
            <h2 class="levelup-title">LEVEL UP!</h2>
            <p class="levelup-subtitle">You reached <strong>Level ${newLevel}</strong> 🎉</p>
            <button class="levelup-btn" id="levelup-close">Continue</button>
        </div>
    `;
    document.body.appendChild(modal);
    requestAnimationFrame(() => modal.classList.add('levelup-visible'));
    setTimeout(() => launchConfetti(modal.querySelector('.levelup-badge')), 200);
    const close = () => {
        modal.classList.remove('levelup-visible');
        modal.addEventListener('transitionend', () => modal.remove(), { once: true });
    };
    modal.querySelector('#levelup-close').addEventListener('click', close);
    modal.querySelector('.levelup-backdrop').addEventListener('click', close);
}

// ── UPDATE ALL XP UI ELEMENTS ─────────────────────────────────
function updateProgressUI(newXP, xpProgress, xpToNext, currentLevel) {
    // Update XP bar fills
    document.querySelectorAll('.xp-bar-fill').forEach(el => {
        el.style.transition = 'width 1.2s cubic-bezier(.34,1.56,.64,1)';
        el.style.width = `${xpProgress}%`;
    });
    
    // Update XP counters
    document.querySelectorAll('[data-xp-counter]').forEach(el => {
        const from = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10) || 0;
        countUp(el, from, newXP, 1200);
    });
    
    document.querySelectorAll('.xp-num').forEach(el => {
        const from = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10) || 0;
        countUp(el, from, newXP, 1200);
    });
    
    // Update XP to next level
    document.querySelectorAll('[data-xp-to-next]').forEach(el => {
        el.textContent = xpToNext.toLocaleString();
    });
    
    // Update level displays
    document.querySelectorAll('[data-level-display]').forEach(el => {
        el.textContent = currentLevel;
    });
    
    // Update XP bar labels
    document.querySelectorAll('[data-xp-bar-label]').forEach(el => {
        el.textContent = `${newXP.toLocaleString()} XP • ${Math.round(xpProgress)}%`;
    });
}

// ── BUTTON STATE HELPERS ──────────────────────────────────────
function setButtonLoading(btn, isLoading) {
    if (isLoading) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = `<span class="btn-spinner"></span><span>...</span>`;
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
    }
}

function setButtonCompleted(btn) {
    btn.disabled = true;
    btn.classList.remove('start', 'continue');
    btn.classList.add('done');
    btn.innerHTML = `<span>✓</span> Completed`;
    btn.closest('.lesson-card')?.classList.add('lesson-completed');
}

// ── MAIN: COMPLETE LESSON ─────────────────────────────────────
async function completeLesson(btn) {
    const lessonId = parseInt(btn.dataset.lessonId, 10);
    if (!lessonId || btn.disabled) return;
    
    const xpPreview = parseInt(btn.dataset.xpReward, 10) || 10;
    setButtonLoading(btn, true);
    
    try {
        const body = new URLSearchParams({
            lesson_id  : lessonId,
            csrf_token : XP.csrfToken,
        });
        
        const response = await fetch(XP.endpointUrl, {
            method  : 'POST',
            headers : { 'X-Requested-With': 'XMLHttpRequest' },
            body,
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unknown error');

        if (data.message === 'already_completed') {
            Toast.show('info', 'You already completed this lesson!');
            setButtonCompleted(btn);
            return;
        }

        const earned = data.xp_earned ?? xpPreview;
        setButtonLoading(btn, false);
        floatXP(earned, btn);
        setButtonCompleted(btn);

        if (data.leveled_up) { 
            showLevelUp(data.current_level); 
        } else { 
            launchConfetti(btn); 
        }

        updateProgressUI(data.xp_points, data.xp_progress, data.xp_to_next, data.current_level);

        const msg = data.leveled_up
            ? `🎉 Level Up! You're now Level ${data.current_level}! +${earned} XP`
            : `✦ +${earned} XP earned! Keep going!`;
        Toast.show('success', msg, data.leveled_up ? 6000 : 3500);

        document.querySelectorAll('.xp-pill').forEach(el => {
            el.classList.remove('xp-pulse');
            requestAnimationFrame(() => el.classList.add('xp-pulse'));
        });

    } catch (err) {
        setButtonLoading(btn, false);
        const isAuth = err.message.includes('401') || err.message.toLowerCase().includes('unauthenticated');
        const isCsrf = err.message.includes('403') || err.message.toLowerCase().includes('csrf');
        
        if (isAuth) {
            Toast.show('error', 'Session expired — please log in again.', 5000);
        } else if (isCsrf) {
            Toast.show('error', 'Security error. Please refresh the page.', 5000);
        } else {
            Toast.show('error', 'Could not save progress. Please try again.', 4000);
        }
        
        console.error('[XP] completeLesson error:', err);
    }
}

// ── SHIMMER SKELETON ──────────────────────────────────────────
function showShimmer(container, count = 6) {
    container.innerHTML = '';
    for (let i = 0; i < count; i++) {
        const card = document.createElement('div');
        card.className = 'lesson-card shimmer-card';
        card.innerHTML = `
            <div class="shimmer-thumb"></div>
            <div class="shimmer-body">
                <div class="shimmer-line short"></div>
                <div class="shimmer-line"></div>
                <div class="shimmer-line medium"></div>
                <div class="shimmer-line short"></div>
            </div>
        `;
        container.appendChild(card);
    }
}

function hideShimmer(container) {
    container.querySelectorAll('.shimmer-card').forEach(el => el.remove());
}

// ── STAGGER CARD ENTRY ────────────────────────────────────────
function animateCardsIn(selector = '.lesson-card') {
    const cards = document.querySelectorAll(selector);
    cards.forEach((card, i) => {
        card.style.opacity   = '0';
        card.style.transform = 'translateY(24px)';
        card.style.transition = `opacity .4s ease ${i*80}ms, transform .4s ease ${i*80}ms`;
        requestAnimationFrame(() => requestAnimationFrame(() => {
            card.style.opacity   = '1';
            card.style.transform = 'translateY(0)';
        }));
    });
}

// ── EVENT DELEGATION ──────────────────────────────────────────
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-complete-lesson]');
    if (btn) completeLesson(btn);
});

// ── PAGE LOAD ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Animate cards
    animateCardsIn('.lesson-card');
    animateCardsIn('.subject-card');
    animateCardsIn('.achievement-card');
    
    // Animate XP bars
    document.querySelectorAll('.xp-bar-fill').forEach(fill => {
        const target = fill.style.width || fill.getAttribute('data-width') || '0%';
        fill.style.width = '0%';
        fill.style.transition = 'width 1.4s cubic-bezier(.34,1.2,.64,1)';
        setTimeout(() => { fill.style.width = target; }, 400);
    });
});

// ── MODULE EXPORTS ────────────────────────────────────────────
if (typeof module !== 'undefined') {
    module.exports = { 
        completeLesson, 
        Toast, 
        countUp, 
        floatXP, 
        launchConfetti, 
        showShimmer, 
        hideShimmer, 
        animateCardsIn 
    };
}
