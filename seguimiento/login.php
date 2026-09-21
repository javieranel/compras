<?php
declare(strict_types=1);
session_start();

// Evita que una sesión autenticada vuelva a mostrar el login.
// Ajusta la clave y la ruta a las que utiliza actualmente tu aplicación.
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ./dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07111f">
    <title>Melones Oil Terminal | Gestión de Compras</title>

    <script>
        // Aplica el tema antes de pintar la página para evitar un destello de color.
        const savedTheme = localStorage.getItem('procura-theme');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        brand: { 50: '#effdf9', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488' },
                        ink: '#07111f'
                    },
                    boxShadow: {
                        glass: '0 24px 80px -24px rgba(2, 8, 23, .42)',
                        glow: '0 0 0 4px rgba(20,184,166,.14), 0 14px 35px -12px rgba(20,184,166,.65)'
                    },
                    keyframes: {
                        drift: { '0%,100%': { transform: 'translate3d(0,0,0) rotate(0deg)' }, '50%': { transform: 'translate3d(28px,-22px,0) rotate(8deg)' } },
                        gradient: { '0%,100%': { backgroundPosition: '0% 50%' }, '50%': { backgroundPosition: '100% 50%' } },
                        float: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } }
                    },
                    animation: {
                        drift: 'drift 16s ease-in-out infinite',
                        'drift-slow': 'drift 23s ease-in-out infinite reverse',
                        gradient: 'gradient 14s ease infinite',
                        float: 'float 5s ease-in-out infinite'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css" rel="stylesheet">

    <style>
        [data-cloak] { display: none; }
        body { min-height: 100svh; }
        .animated-bg {
            background: linear-gradient(125deg, #06111f, #0b2940, #083a3b, #101c36);
            background-size: 300% 300%;
        }
        .light .animated-bg { background: linear-gradient(125deg, #ecfeff, #f8fafc, #dbeafe, #ccfbf1); }
        .noise {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.13'/%3E%3C/svg%3E");
        }
        .field-input:not(:placeholder-shown) + .field-label,
        .field-input:focus + .field-label {
            transform: translateY(-1.05rem) scale(.78);
            color: #0d9488;
        }
        .field-label { transform-origin: left top; }
        .ripple { position: absolute; border-radius: 9999px; transform: scale(0); background: rgba(255,255,255,.38); animation: ripple .65s linear; pointer-events: none; }
        @keyframes ripple { to { transform: scale(4); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>

<body class="bg-slate-100 font-sans text-slate-900 antialiased selection:bg-teal-300/40 dark:bg-ink dark:text-white">
<main class="animated-bg relative isolate min-h-[100svh] overflow-hidden animate-gradient px-4 py-5 sm:px-7 lg:p-8">
    <div class="noise pointer-events-none absolute inset-0 z-0 opacity-[.12] mix-blend-soft-light" aria-hidden="true"></div>

    <!-- Formas ambientales: movimiento lento y puramente decorativo. -->
    <div class="pointer-events-none absolute -left-24 -top-28 h-80 w-80 rounded-full bg-cyan-400/20 blur-3xl animate-drift" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-32 right-[8%] h-96 w-96 rounded-full bg-teal-400/20 blur-3xl animate-drift-slow" aria-hidden="true"></div>
    <div class="pointer-events-none absolute right-[10%] top-[7%] hidden h-36 w-36 rotate-12 rounded-[2.2rem] border border-white/10 bg-white/5 backdrop-blur-sm animate-float lg:block" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto grid min-h-[calc(100svh-2.5rem)] max-w-7xl overflow-hidden rounded-[2rem] border border-white/15 bg-white/70 shadow-glass backdrop-blur-2xl dark:bg-slate-950/55 lg:min-h-[calc(100svh-4rem)] lg:grid-cols-[1.08fr_.92fr]">

        <!-- Panel de valor y métricas -->
        <section class="relative hidden flex-col justify-between overflow-hidden border-r border-white/10 p-10 text-white lg:flex xl:p-14" aria-labelledby="brand-title">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-slate-900/62 to-teal-950/65"></div>
            <div class="relative">
                <a href="#" class="inline-flex items-center gap-3" aria-label="Inicio Melones Oil Terminal">
                    <!-- Sustituye este isotipo por <img src='./img/logo.png'> si ya cuentas con logo. -->
                    <span class="grid h-12 w-12 place-items-center rounded-3xl bg-gradient-to-br from-teal-400 to-cyan-500 text-slate-950 shadow-lg">
                        <img src='./img/image.png' alt="Logo Melones Oil Terminal" class="h-10 w-10 rounded-3xl object-cover shadow-lg">
                    </span>
                    <span><strong id="brand-title" class="block text-xl font-extrabold tracking-tight">Melones Oil Terminal </strong><span class="text-xs font-medium tracking-[.2em] text-teal-200">Departamento de Compras</span></span>
                </a>

                <div class="mt-20 max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-teal-300/20 bg-teal-300/10 px-3 py-1.5 text-xs font-semibold text-teal-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-teal-300 shadow-[0_0_10px_#5eead4]"></span>
                        Operación conectada en tiempo real
                    </span>
                    <h1 class="mt-6 text-5xl font-extrabold leading-[1.05] tracking-[-.04em] xl:text-6xl">Controla tus compras <span class="bg-gradient-to-r from-teal-200 to-cyan-300 bg-clip-text text-transparent">en tiempo real.</span></h1>
                    <p class="mt-6 max-w-lg text-base leading-7 text-slate-300">Centraliza órdenes de compra, proveedores, aprobaciones y entregas en una experiencia segura y transparente.</p>
                </div>
            </div>

            <div class="relative mt-14">
                <p class="mb-4 text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Resumen de hoy</p>
                <div class="grid grid-cols-3 gap-3">
                    <article class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur-xl transition hover:-translate-y-1 hover:bg-white/[.11]">
                        <div class="flex items-center justify-between"><span class="text-xs text-slate-300">OC pendientes</span><span class="rounded-lg bg-amber-300/15 p-1.5 text-amber-200">↗</span></div>
                        <strong class="mt-3 block text-2xl" data-counter="24">0</strong><span class="text-[11px] text-amber-200">6 requieren acción</span>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur-xl transition hover:-translate-y-1 hover:bg-white/[.11]">
                        <div class="flex items-center justify-between"><span class="text-xs text-slate-300">Proveedores</span><span class="rounded-lg bg-cyan-300/15 p-1.5 text-cyan-200">●</span></div>
                        <strong class="mt-3 block text-2xl" data-counter="186">0</strong><span class="text-[11px] text-cyan-200">97% habilitados</span>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur-xl transition hover:-translate-y-1 hover:bg-white/[.11]">
                        <div class="flex items-center justify-between"><span class="text-xs text-slate-300">Ahorro del mes</span><span class="rounded-lg bg-teal-300/15 p-1.5 text-teal-200">✓</span></div>
                        <strong class="mt-3 block text-2xl">8.4%</strong><span class="text-[11px] text-teal-200">+1.2% vs. anterior</span>
                    </article>
                </div>
            </div>
        </section>

        <!-- Panel de acceso -->
        <section class="relative flex items-center justify-center p-5 sm:p-10 xl:p-14" aria-labelledby="login-title">
            <div class="absolute right-5 top-5 flex items-center gap-2">
                <span id="themeLabel" class="hidden text-xs font-medium text-slate-500 dark:text-slate-400 sm:block">Tema</span>
                <button id="themeToggle" type="button" class="relative h-10 w-16 rounded-full border border-slate-200 bg-slate-200 p-1 shadow-inner transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-800" aria-label="Cambiar modo claro u oscuro" aria-pressed="false">
                    <span class="theme-knob grid h-8 w-8 place-items-center rounded-full bg-white text-amber-500 shadow-md transition-transform duration-300 dark:translate-x-6 dark:bg-slate-950 dark:text-cyan-300">
                        <svg id="themeIcon" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42M3 12h2m14 0h2M5.64 18.36l1.42-1.42m9.88-9.88 1.42-1.42"/><circle cx="12" cy="12" r="4"/></svg>
                    </span>
                </button>
            </div>

            <div class="w-full max-w-md pt-14 lg:pt-5">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-teal-400 to-cyan-500 text-slate-950 shadow-lg">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M6 6l1 14h10l1-14M9 10v6m6-6v6M9 6l1-3h4l1 3"/></svg>
                        </span>
                        <div><strong class="block text-lg dark:text-white">Melones Oil Terminal</strong><span class="text-xs font-medium text-teal-700 dark:text-teal-300">Controla tus compras en tiempo real</span></div>
                    </div>
                </div>

                <header class="mb-7">
                    <p class="text-sm font-semibold text-teal-600 dark:text-teal-400">Departamento de Compras </p>
                    <h2 id="login-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Bienvenido de nuevo</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Ingresa tus usuario y contraseña para acceder a tu espacio de trabajo.</p>
                </header>

                <form id="loginForm" class="space-y-5" autocomplete="on" novalidate>
                    <div>
                        <div class="relative">
                            <input id="usuario" name="usuario" type="text" class="field-input peer h-14 w-full rounded-xl border border-slate-300 bg-white/70 px-4 pb-2 pt-6 text-sm text-slate-950 outline-none transition placeholder:text-transparent hover:border-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 dark:border-slate-700 dark:bg-slate-900/65 dark:text-white dark:hover:border-slate-600" placeholder="Usuario o correo" autocomplete="username" minlength="3" required aria-describedby="usuarioError">
                            <label for="usuario" class="field-label pointer-events-none absolute left-4 top-[1.08rem] text-sm text-slate-500 transition-all duration-200 dark:text-slate-400">Usuario</label>
                            <span id="usuarioStatus" class="absolute right-4 top-1/2 -translate-y-1/2 text-teal-500" aria-hidden="true"></span>
                        </div>
                        <p id="usuarioError" class="mt-1.5 min-h-4 text-xs text-rose-600 dark:text-rose-400" aria-live="polite"></p>
                    </div>

                    <div>
                        <div class="relative">
                            <input id="clave" name="clave" type="password" class="field-input peer h-14 w-full rounded-xl border border-slate-300 bg-white/70 px-4 pb-2 pt-6 pr-12 text-sm text-slate-950 outline-none transition placeholder:text-transparent hover:border-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 dark:border-slate-700 dark:bg-slate-900/65 dark:text-white dark:hover:border-slate-600" placeholder="Contraseña" autocomplete="current-password" minlength="6" required aria-describedby="claveError">
                            <label for="clave" class="field-label pointer-events-none absolute left-4 top-[1.08rem] text-sm text-slate-500 transition-all duration-200 dark:text-slate-400">Contraseña</label>
                            <button id="togglePassword" type="button" class="absolute right-3 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Mostrar contraseña" aria-pressed="false">
                                <svg id="eyeIcon" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                        <p id="claveError" class="mt-1.5 min-h-4 text-xs text-rose-600 dark:text-rose-400" aria-live="polite"></p>
                    </div>

                    <div class="flex items-center justify-between gap-3 text-sm">
                        <label class="flex cursor-pointer items-center gap-2 text-slate-600 dark:text-slate-300">
                            <input id="recordarme" name="recordarme" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-900">
                            Recordarme
                        </label>
                        <a href="#" class="font-semibold text-teal-700 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 dark:text-teal-300">¿Olvidé mi contraseña?</a>
                    </div>

                    <button id="submitButton" type="submit" class="group relative flex h-14 w-full items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-teal-600/20 transition duration-300 hover:-translate-y-0.5 hover:shadow-glow focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0">
                        <span id="buttonText" class="transition-transform group-hover:translate-x-[-2px]">Ingresar al sistema</span>
                        <svg id="arrowIcon" viewBox="0 0 24 24" class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        <svg id="loadingIcon" class="hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-90" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </button>



                    <p class="text-center text-xs leading-5 text-slate-400">BY MELONES OIL TERMINAL</p>
                </form>
            </div>
        </section>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
<script>
(() => {
    const root = document.documentElement;
    const form = document.getElementById('loginForm');
    const themeToggle = document.getElementById('themeToggle');
    const password = document.getElementById('clave');
    const passwordToggle = document.getElementById('togglePassword');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const arrowIcon = document.getElementById('arrowIcon');
    const loadingIcon = document.getElementById('loadingIcon');

    // Toggle animado con preferencia persistente.
    const syncThemeState = () => themeToggle.setAttribute('aria-pressed', String(root.classList.contains('dark')));
    syncThemeState();
    themeToggle.addEventListener('click', () => {
        root.classList.toggle('dark');
        localStorage.setItem('procura-theme', root.classList.contains('dark') ? 'dark' : 'light');
        syncThemeState();
    });

    passwordToggle.addEventListener('click', () => {
        const show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(show));
        passwordToggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });

    const validators = {
        usuario: input => input.value.trim().length >= 3 ? '' : 'Ingresa al menos 3 caracteres.',
        clave: input => input.value.length >= 6 ? '' : 'La contraseña debe tener al menos 6 caracteres.'
    };

    function validateField(input) {
        const message = validators[input.id](input);
        const error = document.getElementById(input.id + 'Error');
        input.setAttribute('aria-invalid', String(Boolean(message)));
        input.classList.toggle('border-rose-500', Boolean(message));
        error.textContent = message;
        if (input.id === 'usuario') {
            document.getElementById('usuarioStatus').textContent = !message && input.value ? '✓' : '';
        }
        return !message;
    }

    ['usuario', 'clave'].forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => { if (input.getAttribute('aria-invalid') === 'true') validateField(input); });
    });

    // Ripple calculado desde el punto exacto del clic.
    submitButton.addEventListener('pointerdown', event => {
        const rect = submitButton.getBoundingClientRect();
        const circle = document.createElement('span');
        const size = Math.max(rect.width, rect.height);
        circle.className = 'ripple';
        circle.style.width = circle.style.height = size + 'px';
        circle.style.left = event.clientX - rect.left - size / 2 + 'px';
        circle.style.top = event.clientY - rect.top - size / 2 + 'px';
        submitButton.append(circle);
        circle.addEventListener('animationend', () => circle.remove());
    });

    function setLoading(loading) {
        submitButton.disabled = loading;
        submitButton.setAttribute('aria-busy', String(loading));
        buttonText.textContent = loading ? 'Verificando acceso…' : 'Ingresar al sistema';
        arrowIcon.classList.toggle('hidden', loading);
        loadingIcon.classList.toggle('hidden', !loading);
    }

    form.addEventListener('submit', event => {
        const valid = ['usuario', 'clave'].map(id => validateField(document.getElementById(id))).every(Boolean);
        if (!valid) {
            event.preventDefault();
            form.querySelector('[aria-invalid="true"]')?.focus();
            return;
        }
        setLoading(true);
        // funciones.js debe llamar window.loginReady() cuando termine su petición AJAX.
        window.setTimeout(() => { if (submitButton.disabled) setLoading(false); }, 12000);
    });
    window.loginReady = () => setLoading(false);

    document.querySelectorAll('[data-sso]').forEach(button => button.addEventListener('click', () => {
        const provider = button.dataset.sso === 'azure' ? 'Azure AD' : 'Google Workspace';
        alertify.message(`Configura el endpoint OAuth de ${provider} para habilitar este acceso.`);
    }));

    // Contadores ambientales; se omiten si el usuario prefiere movimiento reducido.
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('[data-counter]').forEach(element => {
            const target = Number(element.dataset.counter);
            const start = performance.now();
            const tick = now => {
                const progress = Math.min((now - start) / 900, 1);
                element.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))).toLocaleString('es-PA');
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    } else {
        document.querySelectorAll('[data-counter]').forEach(el => el.textContent = Number(el.dataset.counter).toLocaleString('es-PA'));
    }
})();
</script>

<!-- Conserva tu controlador AJAX actual. Revisa la nota de integración incluida abajo. -->
<script src="./js/funciones.js"></script>
</body>
</html>
