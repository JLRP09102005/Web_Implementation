'use strict';

(function () {

    const form       = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passInput  = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passError  = document.getElementById('passwordError');
    const submitBtn  = document.getElementById('submitBtn');
    const guestBtn   = document.getElementById('guestBtn');
    const btnText    = submitBtn.querySelector('.btn-text');
    const btnSpinner = submitBtn.querySelector('.btn-spinner');
    const alertBox   = document.getElementById('loginError');
    const alertMsg   = document.getElementById('loginErrorMsg');
    const togglePass = document.getElementById('togglePass');
    const iconShow   = document.getElementById('iconShow');
    const iconHide   = document.getElementById('iconHide');

    togglePass.addEventListener('click', () => {
        const isPassword = passInput.type === 'password';
        passInput.type = isPassword ? 'text' : 'password';
        iconShow.style.display = isPassword ? 'none' : '';
        iconHide.style.display = isPassword ? '' : 'none';
        togglePass.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });

    emailInput.addEventListener('input', () => clearError(emailInput, emailError));
    passInput.addEventListener('input', () => clearError(passInput, passError));

    function clearError(input, errorEl) {
        input.classList.remove('is-invalid');
        errorEl.textContent = '';
    }

    function setError(input, errorEl, msg) {
        input.classList.add('is-invalid');
        errorEl.textContent = msg;
        input.focus();
    }

    function validate() {
        let ok = true;

        if (!emailInput.value.trim()) {
            setError(emailInput, emailError, 'El email es obligatorio.');
            ok = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            setError(emailInput, emailError, 'Introduce un email válido.');
            ok = false;
        }

        if (!passInput.value) {
            setError(passInput, passError, 'La contraseña es obligatoria.');
            ok = false;
        }

        return ok;
    }

    function setLoading(on) {
        submitBtn.disabled = on;
        guestBtn.disabled = on;
        btnText.hidden = on;
        btnSpinner.hidden = !on;
    }

    function showAlert(msg) {
        alertMsg.textContent = msg;
        alertBox.hidden = false;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.hidden = true;

        if (!validate()) return;

        setLoading(true);

        try {
            const res = await fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    password: passInput.value,
                }),
            });

            const data = await res.json();

            if (res.ok && data.success) {
                window.location.href = data.redirect ?? '/dashboard';
            } else {
                showAlert(data.message ?? 'Credenciales incorrectas.');
            }

        } catch {
            showAlert('Error de conexión. Inténtalo de nuevo.');
        } finally {
            setLoading(false);
        }
    });

    guestBtn.addEventListener('click', async () => {
        alertBox.hidden = true;

        clearError(emailInput, emailError);
        clearError(passInput, passError);

        setLoading(true);

        try {
            const res = await fetch('/guest-login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });

            const data = await res.json();

            if (res.ok && data.success) {
                window.location.href = data.redirect ?? '/dashboard';
            } else {
                showAlert(data.message ?? 'No se pudo entrar como invitado.');
            }

        } catch {
            showAlert('Error de conexión. Inténtalo de nuevo.');
        } finally {
            setLoading(false);
        }
    });

})();