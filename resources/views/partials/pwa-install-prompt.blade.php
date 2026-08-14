<div id="pwa-install-banner" class="hidden fixed inset-x-0 bottom-0 z-50 px-4 pb-4">
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-gray-100 p-4 flex items-start gap-3">
        <img src="{{ asset('logos/android-chrome-192x192.png') }}" alt="Plen@" class="w-11 h-11 rounded-xl shrink-0">

        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-800">Instalá Plen@ en tu celular</p>

            <p id="pwa-install-ios-hint" class="hidden text-xs text-gray-500 mt-0.5">
                Tocá <span class="font-medium">Compartir</span>
                <svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4m0 0l4 4m-4-4v13M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
                y después <span class="font-medium">Agregar a pantalla de inicio</span>.
            </p>

            <p id="pwa-install-android-hint" class="hidden text-xs text-gray-500 mt-0.5">
                Accedé más rápido, como una app.
            </p>
        </div>

        <div class="flex items-center gap-1 shrink-0">
            <button id="pwa-install-btn" type="button"
                class="hidden text-xs font-medium text-white px-3 py-2 rounded-lg transition"
                style="background-color: #09cda6;"
                onmouseover="this.style.backgroundColor='#07b394'" onmouseout="this.style.backgroundColor='#09cda6'">
                Instalar
            </button>
            <button id="pwa-install-close" type="button" aria-label="Cerrar" class="text-gray-400 hover:text-gray-600 p-2 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const STORAGE_KEY = 'pwa_install_dismissed_at';
    const DAYS_TO_HIDE = 14;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function wasDismissedRecently() {
        const ts = localStorage.getItem(STORAGE_KEY);
        if (!ts) return false;
        const days = (Date.now() - parseInt(ts, 10)) / (1000 * 60 * 60 * 24);
        return days < DAYS_TO_HIDE;
    }

    if (isStandalone() || wasDismissedRecently()) return;

    const banner = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-install-btn');
    const closeBtn = document.getElementById('pwa-install-close');
    const iosHint = document.getElementById('pwa-install-ios-hint');
    const androidHint = document.getElementById('pwa-install-android-hint');

    function dismiss() {
        banner.classList.add('hidden');
        localStorage.setItem(STORAGE_KEY, Date.now().toString());
    }

    closeBtn.addEventListener('click', dismiss);

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (isIOS) {
        iosHint.classList.remove('hidden');
        banner.classList.remove('hidden');
    } else {
        let deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            androidHint.classList.remove('hidden');
            installBtn.classList.remove('hidden');
            banner.classList.remove('hidden');
        });

        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            dismiss();
        });

        window.addEventListener('appinstalled', dismiss);
    }
})();
</script>
