<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC — Acceso</title>
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>

<div class="login-wrapper">

    <div class="login-card">

        <div class="login-header">
            <svg class="logo" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="WEC">
                <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5"/>
                <path d="M12 18 L18 30 L24 20 L30 30 L36 18" stroke="currentColor" stroke-width="2.5"
                      stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <h1>WEC Panel</h1>
            <p class="subtitle">Accede con tu cuenta o entra como invitado</p>
        </div>

        <div class="alert alert-error" id="loginError" role="alert" hidden>
            <span id="loginErrorMsg">Credenciales incorrectas.</span>
        </div>

        <form id="loginForm" novalidate>

            <div class="field">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="usuario@dominio.com"
                    autocomplete="email"
                    required
                >
                <span class="field-error" id="emailError"></span>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña">
                        <svg id="iconShow" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="iconHide" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <span class="field-error" id="passwordError"></span>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="btn-text">Entrar</span>
                <span class="btn-spinner" hidden>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" class="spin">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                                 M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="btn-guest" id="guestBtn">
                Entrar como invitado
            </button>

        </form>

    </div>

</div>

<script src="/js/login.js"></script>
</body>
</html>