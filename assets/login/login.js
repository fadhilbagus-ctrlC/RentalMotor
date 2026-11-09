const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');

registerBtn.addEventListener('click', () => {
    container.classList.add('active');
});

loginBtn.addEventListener('click', () => {
    container.classList.remove('active');
});

// ---------------------------------------------------------------------------
// Deteksi keyboard virtual di HP.
// Saat keyboard terbuka, tinggi layar yang terlihat menyusut. Container
// mengikuti tinggi tersebut agar area form bisa di-scroll dan tombol Register
// tidak menabrak panel toggle-right di bawahnya.
// ---------------------------------------------------------------------------
const isMobile = window.matchMedia('(max-width: 850px)');
const viewport = window.visualViewport;

const inputFocused = () => {
    const el = document.activeElement;
    return !!el && el.tagName === 'INPUT';
};

const visibleHeight = () => viewport ? viewport.height : window.innerHeight;

// Tinggi layar "normal" saat keyboard tidak terbuka.
let baselineHeight = Math.max(window.innerHeight, visibleHeight());

const refreshBaseline = () => {
    baselineHeight = Math.max(window.innerHeight, visibleHeight());
};

const syncViewport = () => {
    if (!isMobile.matches) {
        document.body.classList.remove('keyboard-open');
        document.documentElement.style.removeProperty('--vv-height');
        return;
    }

    const height = visibleHeight();
    document.documentElement.style.setProperty('--vv-height', height + 'px');

    // Selisih > 120px biasanya berarti keyboard terbuka (bukan address bar).
    const keyboardOpen = (baselineHeight - height) > 120;
    document.body.classList.toggle('keyboard-open', keyboardOpen);
};

const handleResize = () => {
    // Jangan perbarui baseline saat input sedang fokus (keyboard terbuka).
    if (!inputFocused()) {
        refreshBaseline();
    }
    syncViewport();
};

if (viewport) {
    viewport.addEventListener('resize', handleResize);
    viewport.addEventListener('scroll', syncViewport);
}
window.addEventListener('resize', handleResize);
window.addEventListener('orientationchange', () => {
    setTimeout(() => {
        refreshBaseline();
        syncViewport();
    }, 300);
});
syncViewport();
