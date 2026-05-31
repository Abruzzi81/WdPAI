// Definiujemy funkcję jako globalną na samym początku pliku
function selectAvatar(element) {
    const avatarId = element.getAttribute('data-id');
    const filename = element.getAttribute('data-filename');
    const name = element.getAttribute('data-name');

    // Jeśli moduł jest już aktywny, ignorujemy kliknięcie
    if (element.classList.contains('item--active')) return;

    // Jeśli moduł jest zablokowany, wyświetlamy okno dialogowe z pytaniem o zakup
    if (element.classList.contains('item--locked')) {
        const priceTag = element.querySelector('.skin-price');
        const priceText = priceTag ? priceTag.innerText : '';
        const confirmBuy = confirm(`Czy chcesz odblokować moduł tożsamości "${name}" za ${priceText}?`);
        if (!confirmBuy) return; // Jeśli użytkownik kliknie "Anuluj", przerywamy
    }

    // Wysyłamy asynchroniczne żądanie POST do serwera PHP
    fetch('/equip-avatar', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify({ avatar_id: avatarId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Problem z odpowiedzią sieciową serwera.');
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            
            // 1. Obsługa transakcji zakupu
            if (data.purchased) {
                element.classList.remove('item--locked');
                const lockOverlay = element.querySelector('.lock-overlay');
                if (lockOverlay) lockOverlay.remove();
                
                // Aktualizacja portfela Star Dust w nagłówku na żywo
                const headerStarDust = document.querySelector('.stat-pill span');
                if (headerStarDust && data.new_balance !== null) {
                    headerStarDust.innerText = `✨ ${data.new_balance.toLocaleString()} Gwiezdny pył`;
                }
            }

            // 2. Szukamy starej aktywnej karty i zmieniamy jej status na "POSIADANY"
            const previousActive = document.querySelector('.skin-item.item--active');
            if (previousActive) {
                previousActive.classList.remove('item--active');
                const priceTag = previousActive.querySelector('.skin-price');
                if (priceTag) priceTag.innerHTML = `<span class="text-blue">POSIADANY</span>`;
            }

            // 3. Ustawiamy klikniętą kartę jako aktywną (zielona poświata)
            element.classList.add('item--active');
            const currentPriceTag = element.querySelector('.skin-price');
            if (currentPriceTag) {
                currentPriceTag.innerHTML = `<strong class="text-green">AKTYWNY</strong>`;
            }
            
            // 4. Podmieniamy duży awatar profilowy po lewej stronie
            const bigAvatarImg = document.getElementById('current-avatar-img');
            const bigAvatarName = document.getElementById('current-avatar-name');
            if (bigAvatarImg) bigAvatarImg.src = `/public/img/avatars/${filename}`;
            if (bigAvatarName) bigAvatarName.innerText = name;

            // 5. Podmieniamy miniaturkę w nagłówku strony (jeśli ją dodałeś)
            const headerMini = document.querySelector('.user-avatar-mini img');
            if (headerMini) headerMini.src = `/public/img/avatars/${filename}`;

        } else {
            alert(data.message || 'Wystąpił nieoczekiwany błąd podczas finalizacji transakcji.');
        }
    })
    .catch(error => {
        console.error('Hangar Error:', error);
        alert('Błąd krytyczny: Brak połączenia z siecią handlową Akademii.');
    });
}