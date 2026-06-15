document.addEventListener('DOMContentLoaded', function () {
    // --- 1. OBSŁUGA DYNAMICZNEJ WYSZUKIWARKI ---
    const searchInput = document.querySelector('.search-input');
    const userRows = document.querySelectorAll('.user-row');

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();

            userRows.forEach(row => {
                const nameElement = row.querySelector('.search-name');
                const emailElement = row.querySelector('.search-email');

                if (nameElement && emailElement) {
                    const name = nameElement.textContent.toLowerCase();
                    const email = emailElement.textContent.toLowerCase();

                    if (name.includes(query) || email.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }

    // --- 2. DELEGACJA ZDARZEŃ DLA PRZYCISKÓW (Zarządzanie Akcjami) ---
    const tableBody = document.querySelector('.admin-table tbody');

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            // Sprawdzamy czy kliknięto przycisk USUŃ lub ikonę wewnątrz niego
            const deleteBtn = e.target.closest('.btn-delete');
            // Sprawdzamy czy kliknięto przycisk PRZYWRÓĆ lub ikonę wewnątrz niego
            const restoreBtn = e.target.closest('.btn-restore');

            if (deleteBtn) {
                handleBan(deleteBtn);
            } else if (restoreBtn) {
                handleRestore(restoreBtn);
            }
        });
    }

    // Funkcja realizująca BANOWANIE (USUWANIE)
    function handleBan(button) {
        const userId = button.getAttribute('data-id');
        const row = button.closest('.user-row');
        const actionsCell = button.closest('.actions-cell');

        if (confirm('Czy na pewno chcesz zbanować tego kadeta i odebrać mu dostęp do Centrum Dowodzenia?')) {
            fetch('/delete-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 1. Aktualizacja pigułki statusu na zbanowany
                    const statusBadge = row.querySelector('.role-badge:nth-child(2)');
                    if (statusBadge) {
                        statusBadge.className = 'role-badge badge--admin';
                        statusBadge.textContent = '⛔ Zbanowany';
                        statusBadge.style.cssText = 'margin-left: 5px; text-transform: uppercase;';
                    }
                    // 2. Zamiana przycisku "Usuń" na "Przywróć"
                    actionsCell.innerHTML = `
                        <button class="btn-action btn-restore" data-id="${userId}" title="Przywróć użytkownika">
                            <i class="fa-solid fa-user-check"></i> Przywróć
                        </button>
                    `;
                } else {
                    alert('Błąd: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // Funkcja realizująca PRZYWRACANIE (ODBANOWANIE)
    function handleRestore(button) {
        const userId = button.getAttribute('data-id');
        const row = button.closest('.user-row');
        const actionsCell = button.closest('.actions-cell');

        if (confirm('Czy chcesz zdjąć blokadę z tego użytkownika i przywrócić go do gry?')) {
            fetch('/restore-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // POPRAWKA: Zmieniamy klasę na badge--offline, aby pigułka stała się szara!
                    const statusBadge = row.querySelector('.role-badge:nth-child(2)');
                    if (statusBadge) {
                        statusBadge.className = 'role-badge badge--offline';
                        statusBadge.textContent = '⚪ Offline';
                        statusBadge.style.cssText = ''; // Czyścimy stare style inline, CSS zrobi resztę
                    }
                    
                    // Zamiana przycisku "Przywróć" z powrotem na "Usuń"
                    actionsCell.innerHTML = `
                        <button class="btn-action btn-delete" data-id="${userId}" title="Usuń użytkownika">
                            <i class="fa-solid fa-trash-can"></i> Usuń
                        </button>
                    `;
                } else {
                    alert('Błąd: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
});