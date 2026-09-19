// scripts.js - lógica de frontend para el sistema de reservas de vuelos
// Se comunica con los servicios PHP (auth.php, search_flights.php,
// reserve_flight.php, manage_reservations.php) usando fetch + JSON.

async function apiPost(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

function getSession() {
    const raw = localStorage.getItem('flight_session');
    return raw ? JSON.parse(raw) : null;
}

function setSession(session) {
    localStorage.setItem('flight_session', JSON.stringify(session));
}

function clearSession() {
    localStorage.removeItem('flight_session');
}

function showMessage(el, text, isError) {
    if (!el) return;
    el.textContent = text;
    el.className = 'msg ' + (isError ? 'error' : 'ok');
    el.classList.remove('hidden');
}

// ---- Barra de navegación dinámica (según si hay sesión iniciada) ----
function renderNav() {
    const navRight = document.getElementById('nav-auth-area');
    if (!navRight) return;
    const session = getSession();

    if (session) {
        navRight.innerHTML = `
            <a href="search.html">Buscar vuelos</a>
            <a href="reservations.html">Mis reservas</a>
            <span style="padding:8px 6px;">Hola, ${session.username}</span>
            <button id="logout-btn">Cerrar sesión</button>
        `;
        document.getElementById('logout-btn').addEventListener('click', () => {
            clearSession();
            window.location.href = 'login.html';
        });
    } else {
        navRight.innerHTML = `
            <a href="login.html">Iniciar sesión</a>
            <a class="highlight" href="register.html">Registrarse</a>
        `;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    renderNav();

    // ---- index.html: búsqueda rápida en el hero (redirige a search.html) ----
    const quickSearch = document.getElementById('homeQuickSearch');
    if (quickSearch) {
        quickSearch.addEventListener('submit', (e) => {
            e.preventDefault();
            const origin = quickSearch.origin.value.trim();
            const destination = quickSearch.destination.value.trim();
            const params = new URLSearchParams();
            if (origin) params.set('origin', origin);
            if (destination) params.set('destination', destination);
            window.location.href = 'search.html' + (params.toString() ? '?' + params.toString() : '');
        });
    }

    // ---- register.html ----
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            const payload = Object.fromEntries(formData.entries());
            payload.action = 'register';
            const msgEl = document.getElementById('registerMsg');
            try {
                const result = await apiPost('php/auth.php', payload);
                showMessage(msgEl, result.message, !result.success);
                if (result.success) {
                    registerForm.reset();
                    setTimeout(() => window.location.href = 'login.html', 1200);
                }
            } catch (err) {
                showMessage(msgEl, 'No se pudo conectar con el servicio de registro.', true);
            }
        });
    }

    // ---- login.html ----
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const payload = Object.fromEntries(formData.entries());
            payload.action = 'login';
            const msgEl = document.getElementById('loginMsg');
            try {
                const result = await apiPost('php/auth.php', payload);
                showMessage(msgEl, result.message, !result.success);
                if (result.success) {
                    setSession({ user_id: result.user_id, username: result.username });
                    setTimeout(() => window.location.href = 'search.html', 800);
                }
            } catch (err) {
                showMessage(msgEl, 'No se pudo conectar con el servicio de inicio de sesión.', true);
            }
        });
    }

    // ---- search.html ----
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        const resultsEl = document.getElementById('results');

        // Si venimos de la búsqueda rápida del home (index.html), precargar los campos
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('origin')) searchForm.origin.value = urlParams.get('origin');
        if (urlParams.get('destination')) searchForm.destination.value = urlParams.get('destination');

        searchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(searchForm);
            const payload = Object.fromEntries(formData.entries());
            try {
                const result = await apiPost('php/search_flights.php', payload);
                renderFlights(result.flights || []);
            } catch (err) {
                resultsEl.innerHTML = '<p class="msg error">No se pudo conectar con el servicio de búsqueda.</p>';
            }
        });

        function renderFlights(flights) {
            if (flights.length === 0) {
                resultsEl.innerHTML = '<p class="empty-state">No se encontraron vuelos con esos criterios.</p>';
                return;
            }
            resultsEl.innerHTML = '<div class="flight-list">' + flights.map(f => `
                <div class="flight-item">
                    <div>
                        <span class="airline-tag">${f.airline}</span>
                        <div class="route">${f.origin} → ${f.destination}</div>
                        <div class="details">Sale: ${f.departure_date}${f.return_date ? ' · Regresa: ' + f.return_date : ''} · ${f.seats_available} asientos disponibles</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div class="price">$${Number(f.price).toFixed(2)}<small>MXN</small></div>
                        <button class="btn gold" data-flight-id="${f.flight_id}">Reservar</button>
                    </div>
                </div>
            `).join('') + '</div>';

            resultsEl.querySelectorAll('button[data-flight-id]').forEach(btn => {
                btn.addEventListener('click', () => reserveFlight(btn.dataset.flightId));
            });
        }

        async function reserveFlight(flightId) {
            const session = getSession();
            if (!session) {
                window.location.href = 'login.html';
                return;
            }
            const result = await apiPost('php/reserve_flight.php', {
                user_id: session.user_id,
                flight_id: flightId
            });
            alert(result.message);
            if (result.success) {
                searchForm.dispatchEvent(new Event('submit'));
            }
        }

        // Cargar todos los vuelos al entrar a la página
        searchForm.dispatchEvent(new Event('submit'));
    }

    // ---- reservations.html ----
    const reservationsList = document.getElementById('reservations');
    if (reservationsList) {
        const session = getSession();
        if (!session) {
            window.location.href = 'login.html';
        } else {
            loadReservations();
        }

        async function loadReservations() {
            const result = await apiPost('php/manage_reservations.php', {
                action: 'list',
                user_id: session.user_id
            });
            const reservations = result.reservations || [];
            if (reservations.length === 0) {
                reservationsList.innerHTML = '<p class="empty-state">Aún no tienes reservas.</p>';
                return;
            }
            reservationsList.innerHTML = '<div class="reservation-list">' + reservations.map(r => `
                <div class="flight-item">
                    <div>
                        <span class="airline-tag">${r.airline}</span>
                        <div class="route">${r.origin} → ${r.destination}</div>
                        <div class="details">Sale: ${r.departure_date} · Reservado el ${r.reservation_date}</div>
                        <span class="status-badge">${r.status}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div class="price">$${Number(r.price).toFixed(2)}<small>MXN</small></div>
                        <button class="btn danger" data-res-id="${r.reservation_id}">Cancelar</button>
                    </div>
                </div>
            `).join('') + '</div>';

            reservationsList.querySelectorAll('button[data-res-id]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const result = await apiPost('php/manage_reservations.php', {
                        action: 'cancel',
                        user_id: session.user_id,
                        reservation_id: btn.dataset.resId
                    });
                    alert(result.message);
                    if (result.success) loadReservations();
                });
            });
        }
    }
});
