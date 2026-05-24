// public/js/game.js

// Czekamy na pełne załadowanie struktury DOM, aby bezpiecznie pobrać elementy HTML
document.addEventListener("DOMContentLoaded", () => {

    // 1. POBIERANIE ELEMENTÓW Z DRZEWA DOM
    const timerElement = document.getElementById('countdown');
    const scoreCounter = document.getElementById('score-counter');
    const num1Element = document.getElementById('num1');
    const num2Element = document.getElementById('num2');
    const answerForm = document.getElementById('answer-form');
    const userAnswerInput = document.getElementById('user-answer');
    const gameContainer = document.getElementById('game-container');

    // Kontrola bezpieczeństwa w konsoli (F12)
    if (!answerForm || !userAnswerInput || !num1Element || !num2Element) {
        console.error("BŁĄD KRYTYCZNY: Nie znaleziono kluczowych elementów gry w pliku HTML!");
        return;
    }

    // Odczytujemy poziom trudności z atrybutu "data-level" (domyślnie ustawiamy 'easy')
    const currentLevel = gameContainer ? gameContainer.getAttribute('data-level') : 'easy';

    // Konfiguracja startowa parametrów gry
    let timeLeft = 120; // Czas gry zmieniony na 120 sekund
    let score = 0;

    // 2. FUNKCJA DYNAMICZNEGO GENEROWANIA LICZB DLA POZIOMÓW
    function generateNumbers(level) {
        let min = 2, max = 9; // Zakres domyślny

        if (level === 'easy') {
            min = 1;
            max = 5;
        } else if (level === 'normal') {
            min = 1;
            max = 7;
        } else if (level === 'hard') {
            min = 1;
            max = 10;
        } else if (level === 'legendary') {
            min = 2;
            max = 15;
        }

        // Profesjonalny wzór na losowanie liczb całkowitych w przedziale obustronnie domkniętym [min, max]
        const n1 = Math.floor(Math.random() * (max - min + 1)) + min;
        const n2 = Math.floor(Math.random() * (max - min + 1)) + min;

        return { n1, n2 };
    }

    // 3. LICZNIK CZASU ROZGRYWKI (TIMER)
    const countdownInterval = setInterval(() => {
        timeLeft--;
        if (timerElement) {
            timerElement.innerText = timeLeft;
        }

        // Warunek końca czasu
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            alert('SYSTEM PRZEGRZANY! Koniec czasu. Twój wynik operacji to: ' + score + ' poprawnych kodów.');
            window.location.href = '/training'; // Powrót do bazy (wyboru misji)
        }
    }, 1000);

    // 4. OBSŁUGA FORMULARZA I SPRAWDZANIE WYNIKÓW
    answerForm.addEventListener('submit', function (event) {
        // Blokujemy domyślne przeładowanie strony przez przeglądarkę
        event.preventDefault();

        // Pobieramy aktualne liczby wyświetlane na ekranie (usuwamy spacje za pomocą trim)
        const number1 = parseInt(num1Element.innerText.trim(), 10);
        const number2 = parseInt(num2Element.innerText.trim(), 10);

        // Obliczamy oczekiwany, prawidłowy wynik operacji
        const correctResult = number1 * number2;

        // Pobieramy odpowiedź wpisaną przez użytkownika w pole input
        const userResult = parseInt(userAnswerInput.value.trim(), 10);

        // Weryfikacja matematyczna wyniku wpisanego przez gracza
        if (userResult === correctResult) {
            score++;
            if (scoreCounter) {
                scoreCounter.innerText = score;
            }
            // Efekt wizualny sukcesu: obramowanie zmienia się na neonową zieleń
            userAnswerInput.style.borderColor = '#a3e635';
        } else {
            // Efekt wizualny błędu: obramowanie zmienia się na neonowy róż
            userAnswerInput.style.borderColor = '#ec4899';
        }

        // 5. INICJACJA NOWEGO ZADANIA NA BAZIE AKTUALNEGO POZIOMU
        const { n1, n2 } = generateNumbers(currentLevel);

        // Podmieniamy wartości w reaktorze na ekranie
        num1Element.innerText = n1;
        num2Element.innerText = n2;

        // Błyskawiczne czyszczenie pola tekstowego i ponowne ustawienie kursora do pisania
        userAnswerInput.value = '';
        userAnswerInput.focus();

        // Po upływie 300 milisekund wygaszamy neonowy błysk (zielony/różowy) i wracamy do stylu bazowego
        setTimeout(() => {
            userAnswerInput.style.borderColor = '';
        }, 300);
    });
});