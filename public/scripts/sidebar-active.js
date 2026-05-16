document.addEventListener("DOMContentLoaded", function () {
    // Pobieramy pełny adres URL (np. 'http://localhost/training' lub 'http://localhost/views/training.html')
    const currentUrl = window.location.href;

    // Łapiemy wszystkie linki w sidebarze
    const menuItems = document.querySelectorAll('.sidebar nav ul li a');

    menuItems.forEach(item => {
        // Pobieramy czystą wartość href (np. 'training', 'hangar')
        const hrefValue = item.getAttribute('href');

        // Sprawdzamy, czy hrefValue nie jest puste lub nie jest resetem '#'
        if (hrefValue && hrefValue !== '#') {
            // Sprawdzamy, czy aktualny URL kończy się daną ścieżką lub ją zawiera
            // (np. czy 'http://localhost/training' zawiera słowo 'training')
            if (currentUrl.includes(hrefValue)) {
                // Usuwamy klasę active ze wszystkich innych elementów (na wszelki wypadek)
                item.closest('ul').querySelectorAll('li').forEach(li => li.classList.remove('active'));
                
                // Dodajemy klasę active do rodzica <li>
                item.parentElement.classList.add('active');
            }
        }
    });
});