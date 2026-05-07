<?php
// views/auth/login.php
// Protección: si ya hay sesión activa, redirigir al dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user'])) {
    header('Location: /dashboard');
    exit;
}

// Mensaje de error pasado por la sesión (desde AuthController)
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC · Acceso</title>
    <link rel="stylesheet" href="/public/css/login.css">
</head>
<body>

<div class="login-wrapper" role="main">
    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <svg class="logo" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5"/>
                <path d="M12 24 L20 14 L28 24 L36 14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 30 L20 20 L28 30 L36 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.5"/>
            </svg>
            <h1>WEC Management</h1>
            <p class="subtitle">Accede con tus credenciales</p>
        </div>

        <!-- Alerta de error global (desde sesión) -->
        <?php if ($error): ?>
        <div class="alert alert-error" role="alert" id="globalAlert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <!-- Alerta JS (inline, para errores de fetch) -->
        <div class="alert alert-error" id="jsAlert" role="alert" style="display:none;"></div>

        <!-- Formulario login -->
        <form id="loginForm" novalidate>
            <div class="field">
                <label for="email">Correo electrónico</label>
                <div class="input-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        placeholder="usuario@wec.com"
                        required
                        aria-describedby="emailErr"
                    >
                </div>
                <span class="field-error" id="emailErr" aria-live="polite"></span>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        required
                        aria-describedby="passErr"
                    >
                    <button
                        type="button"
                        class="toggle-pass"
                        id="togglePass"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                    >
                        <!-- Eye open -->
                        <svg id="iconEye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Eye off -->
                        <svg id="iconEyeOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <span class="field-error" id="passErr" aria-live="polite"></span>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <span id="btnText">Iniciar sesión</span>
                <svg id="btnSpinner" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="display:none;">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
            </button>
        </form>

        <!-- Separador -->
        <div style="margin: 1rem 0; display: flex; align-items: center; gap: 0.75rem;">
            <div style="flex:1; height:1px; background: var(--border);"></div>
            <span style="font-size:0.75rem; color: var(--text-muted);">o</span>
            <div style="flex:1; height:1px; background: var(--border);"></div>
        </div>

        <!-- Acceso como invitado -->
        <form id="guestForm">
            <button type="submit" class="btn-guest" id="btnGuest">
                Acceder como invitado
            </button>
        </form>

    </div>
</div>

<script>
(function () {
    'use strict';

    const loginForm   = document.getElementById('loginForm');
    const guestForm   = document.getElementById('guestForm');
    const emailInput  = document.getElementById('email');
    const passInput   = document.getElementById('password');
    const emailErr    = document.getElementById('emailErr');
    const passErr     = document.getElementById('passErr');
    const btnSubmit   = document.getElementById('btnSubmit');
    const btnGuest    = document.getElementById('btnGuest');
    const btnText     = document.getElementById('btnText');
    const btnSpinner  = document.getElementById('btnSpinner');
    const jsAlert     = document.getElementById('jsAlert');
    const togglePass  = document.getElementById('togglePass');
    const iconEye     = document.getElementById('iconEye');
    const iconEyeOff  = document.getElementById('iconEyeOff');

    // ── Toggle contraseña ──────────────────────────────────
    togglePass.addEventListener('click', function () {
        const isPassword = passInput.type === 'password';
        passInput.type   = isPassword ? 'text' : 'password';
        iconEye.style.display    = isPassword ? 'none'  : '';
        iconEyeOff.style.display = isPassword ? ''      : 'none';
        this.setAttribute('aria-label',  isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    });

    // ── Helpers UI ─────────────────────────────────────────
    function showAlert(msg) {
        jsAlert.textContent = msg;
        jsAlert.style.display = '';
        jsAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function hideAlert() {
        jsAlert.style.display = 'none';
        jsAlert.textContent = '';
    }
    function setLoading(on) {
        btnSubmit.disabled = on;
        btnGuest.disabled  = on;
        btnText.textContent = on ? 'Accediendo…' : 'Iniciar sesión';
        btnSpinner.style.display = on ? '' : 'none';
    }
    function clearFieldErrors() {
        [emailInput, passInput].forEach(el => el.classList.remove('is-invalid'));
        emailErr.textContent = '';
        passErr.textContent  = '';
    }

    // ── Validación cliente ─────────────────────────────────
    function validate() {
        clearFieldErrors();
        let ok = true;
        const emailVal = emailInput.value.trim();
        const passVal  = passInput.value;

        if (!emailVal) {
            emailInput.classList.add('is-invalid');
            emailErr.textContent = 'El correo es obligatorio.';
            ok = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            emailInput.classList.add('is-invalid');
            emailErr.textContent = 'Introduce un correo válido.';
            ok = false;
        }
        if (!passVal) {
            passInput.classList.add('is-invalid');
            passErr.textContent = 'La contraseña es obligatoria.';
            ok = false;
        } else if (passVal.length < 6) {
            passInput.classList.add('is-invalid');
            passErr.textContent = 'Mínimo 6 caracteres.';
            ok = false;
        }
        return ok;
    }

    // ── Submit login ──────────────────────────────────────
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideAlert();
        if (!validate()) return;

        setLoading(true);
        try {
            const body = new URLSearchParams({
                email:    emailInput.value.trim(),
                password: passInput.value,
            });
            const res  = await fetch('/login', { method: 'POST', body });
            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect ?? '/dashboard';
            } else {
                showAlert(data.message ?? 'Credenciales incorrectas.');
                passInput.value = '';
                passInput.classList.add('is-invalid');
                passErr.textContent = ' ';
            }
        } catch {
            showAlert('Error de red. Inténtalo de nuevo.');
        } finally {
            setLoading(false);
        }
    });

    // ── Submit invitado ───────────────────────────────────
    guestForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideAlert();
        setLoading(true);
        try {
            const res  = await fetch('/guest-login', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect ?? '/dashboard';
            } else {
                showAlert(data.message ?? 'No se pudo acceder como invitado.');
            }
        } catch {
            showAlert('Error de red. Inténtalo de nuevo.');
        } finally {
            setLoading(false);
        }
    });

    // ── Limpiar errores al escribir ───────────────────────
    emailInput.addEventListener('input', function () {
        this.classList.remove('is-invalid');
        emailErr.textContent = '';
        hideAlert();
    });
    passInput.addEventListener('input', function () {
        this.classList.remove('is-invalid');
        passErr.textContent = '';
        hideAlert();
    });

})();
</script>

</body>
</html>