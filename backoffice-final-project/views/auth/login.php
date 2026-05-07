<?php
// views/auth/login.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user'])) { header('Location: /dashboard'); exit; }
$serverError = $_SESSION['login_error'] ?? null;
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
<div class="login-wrapper">
    <div class="login-card">

        <div class="login-header">
            <!-- Logo SVG inline -->
            <svg class="logo" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="24" cy="24" r="21" stroke="currentColor" stroke-width="2.5"/>
                <path d="M13 26 L19 16 L24 22 L29 16 L35 26" stroke="currentColor" stroke-width="2.5"
                      stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="24" cy="32" r="2.5" fill="currentColor"/>
            </svg>
            <h1>WEC Management</h1>
            <p class="subtitle">Campeonato Mundial de Resistencia</p>
        </div>

        <!-- Alerta de error (IDs usados por login.js) -->
        <div id="loginError" class="alert alert-error" <?= $serverError ? '' : 'hidden' ?> role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="loginErrorMsg"><?= htmlspecialchars($serverError ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <!-- Formulario principal -->
        <form id="loginForm" novalidate autocomplete="on">

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       autocomplete="email" placeholder="usuario@wec.com"
                       required aria-describedby="emailError">
                <span class="field-error" id="emailError" aria-live="polite"></span>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" placeholder="••••••••"
                           required aria-describedby="passwordError">
                    <button type="button" id="togglePass"
                            class="toggle-pass" aria-label="Mostrar contraseña">
                        <!-- Ojo abierto (iconShow) -->
                        <svg id="iconShow" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Ojo tachado (iconHide) -->
                        <svg id="iconHide" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
                                     a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8
                                     a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <span class="field-error" id="passwordError" aria-live="polite"></span>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit">
                <span class="btn-text">Iniciar sesión</span>
                <span class="btn-spinner" hidden aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" class="spin">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83
                                 M16.24 16.24l2.83 2.83M2 12h4M18 12h4
                                 M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                </span>
            </button>
        </form>

        <div class="divider"><span>o</span></div>

        <button type="button" id="guestBtn" class="btn-guest">
            Acceder como invitado
        </button>

    </div>
</div>
<script src="/public/js/login.js"></script>
</body>
</html>