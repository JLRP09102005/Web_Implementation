<?php
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC — Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/login.css">
</head>
<body>

<div class="login-bg" aria-hidden="true">
    <div class="grid-lines"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
</div>

<main class="login-wrap">
    <div class="login-card" role="main">

        <!-- Logo -->
        <div class="login-logo" aria-label="WEC Championship">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="24" cy="24" r="22" stroke="#e10600" stroke-width="2.5"/>
                <path d="M14 24 L20 16 L24 22 L28 16 L34 24 L28 32 L24 26 L20 32 Z" fill="#e10600"/>
            </svg>
            <span class="logo-title">WEC</span>
        </div>

        <h1 class="login-heading">Acceso al panel</h1>
        <p class="login-sub">Introduce tus credenciales para continuar</p>

        <!-- Alert error -->
        <div id="loginError" class="alert-error" role="alert" hidden>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="loginErrorMsg"></span>
        </div>

        <!-- Formulario -->
        <form id="loginForm" novalidate autocomplete="off">

            <div class="field-group">
                <label for="email" class="field-label">Email</label>
                <div class="field-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="field-input"
                        placeholder="usuario@wec.com"
                        autocomplete="email"
                        required
                        aria-describedby="emailError"
                    >
                </div>
                <span id="emailError" class="field-error" role="alert"></span>
            </div>

            <div class="field-group">
                <label for="password" class="field-label">Contraseña</label>
                <div class="field-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                        aria-describedby="passwordError"
                    >
                    <button type="button" id="togglePass" class="field-toggle" aria-label="Mostrar contraseña">
                        <svg id="iconShow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="iconHide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <span id="passwordError" class="field-error" role="alert"></span>
            </div>

            <button type="submit" id="submitBtn" class="btn-primary">
                <span class="btn-text">Entrar</span>
                <span class="btn-spinner" hidden aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                </span>
            </button>

        </form>

        <div class="login-divider"><span>o</span></div>

        <button type="button" id="guestBtn" class="btn-ghost">
            Continuar como invitado
        </button>

    </div>
</main>

<script src="/public/js/login.js"></script>
</body>
</html>