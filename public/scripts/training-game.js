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
    if (!answerForm || !userAnswerInput || !num1Element || !num2Element || !gameContainer) {
        console.error("BŁĄD KRYTYCZNY: Nie znaleziono kluczowych elementów gry w pliku HTML!");
        return;
    }

    // Odczytujemy poziom trudności z atrybutu "data-level" (domyślnie ustawiamy 'easy')
    const currentLevel = gameContainer.getAttribute('data-level') || 'easy';

    // Konfiguracja startowa parametrów gry (ustawione na 100 sekund)
    let timeLeft = 100;
    let score = 0;

    // Generujemy pierwsze liczby na start gry
    initNextQuestion();

    // 2. FUNKCJA DYNAMICZNEGO GENEROWANIA LICZB DLA POZIOMÓW
    function generateNumbers() {
        // Pobieramy wartości bezpośrednio z atrybutów HTML wstrzykniętych przez PHP
        const minAttr = gameContainer.getAttribute('data-min');
        const maxAttr = gameContainer.getAttribute('data-max');

        // Konwertujemy na liczby
        const min = parseInt(minAttr, 10);
        const max = parseInt(maxAttr, 10);

        // Losowanie liczb w przedziale obustronnie domkniętym [min, max]
        const n1 = Math.floor(Math.random() * (max - min + 1)) + min;
        const n2 = Math.floor(Math.random() * (max - min + 1)) + min;

        return { n1, n2 };
    }

    function initNextQuestion() {
        const { n1, n2 } = generateNumbers();
        num1Element.innerText = n1;
        num2Element.innerText = n2;
    }

    // 3. LICZNIK CZASU ROZGRYWKI (TIMER) + FINISZ GRY
    const countdownInterval = setInterval(() => {
        timeLeft--;
        if (timerElement) {
            timerElement.innerText = timeLeft;
        }

        // Warunek końca czasu
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);

            // Zerujemy zmienną, aby wyłączyć strażników przedwczesnego opuszczenia strony
            timeLeft = 0;

            // Przygotowanie paczki danych JSON dla PHP (status 'victory' oznacza ukończenie 100s czasu)
            const payload = {
                status: 'victory',
                score: score,
                level: currentLevel
            };

            // Blokujemy formularz, by użytkownik nic już nie wpisał
            userAnswerInput.disabled = true;

            // Wysłanie wyników za pomocą Fetch API do nowego kontrolera treningu
            fetch('/save-training', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Problem z odpowiedzią serwera');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Płynna zamiana reaktora na dynamiczny ekran podsumowania z uwzględnieniem EXP
                        gameContainer.innerHTML = `
                            <div class="summary-container" style="text-align: center; padding: 20px; color: white; background: rgba(15, 23, 42, 0.9); border: 2px solid #ec4899; border-radius: 16px; width: calc(100%); max-width: 420px; box-shadow: 0 0 25px rgba(236, 72, 153, 0.4); box-sizing: border-box;">
                                <h2 style="color: #ec4899; font-size: 1.8rem; margin-bottom: 15px; letter-spacing: 2px;">MISJA ZAKOŃCZONA</h2>
                                
                                <div style="margin: 25px 0; background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                                    <p style="font-size: 1.2rem; margin: 8px 0;">Rozwiązane równania: <strong style="color: #a3e635;">${data.score}</strong></p>
                                    <p style="font-size: 1.2rem; margin: 8px 0;">Zdobyty gwiezdny pył: <strong style="color: #fbbf24;"> +${data.earned_star_dust}</strong></p>
                                    <p style="font-size: 1.2rem; margin: 8px 0;">Zdobyte doświadczenie: <strong style="color: #a3e635;"> +${data.earned_exp}</strong></p>
                                </div>

                                <a href="/training" class="btn-back" style="display: inline-block; background: #ec4899; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);">
                                    POWRÓT DO CENTRUM DOWODZENIA
                                </a>
                            </div>
                        `;

                        // Aktualizacja licznika pyłu w nagłówku w locie (bez przeładowania strony)
                        const headerStarDust = document.querySelector('.stat-pill span');
                        if (headerStarDust) {
                            const currentDust = parseInt(headerStarDust.innerText.replace(/[^0-9]/g, ''), 10) || 0;
                            const newTotal = currentDust + data.earned_star_dust;
                            headerStarDust.innerText = `✨ ${newTotal.toLocaleString()} Gwiezdny pył`;
                        }
                    } else {
                        alert('Błąd akademii: Nie udało się zapisać wyników w bazie danych.');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Krytyczny błąd połączenia! Wyniki nie mogły zostać przesłane do bazy.');
                });
        }
    }, 1000);

    // 4. OBSŁUGA FORMULARZA I SPRAWDZANIE WYNIKÓW
    answerForm.addEventListener('submit', function (event) {
        // Blokujemy domyślne przeładowanie strony przez przeglądarkę
        event.preventDefault();

        // Pobieramy aktualne liczby wyświetlane na ekranie
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
        initNextQuestion();

        // Błyskawiczne czyszczenie pola tekstowego i ponowne ustawienie kursora do pisania
        userAnswerInput.value = '';
        userAnswerInput.focus();

        // Po upływie 300 milisekund wygaszamy neonowy błysk i wracamy do stylu bazowego
        setTimeout(() => {
            userAnswerInput.style.borderColor = '';
        }, 300);
    });

    // 6. STRAŻNIK ODŚWIEŻENIA / ZAMKNIĘCIA KARTY PRZEGLĄDARKI (F5 / Krzyżyk)
    window.addEventListener('beforeunload', function (event) {
        if (timeLeft > 0) {
            event.preventDefault();
            event.returnValue = 'Czy na pewno chcesz przerwać misję?';
            return event.returnValue;
        }
    });

    // 7. PEWNY STRAŻNIK DLA LINKÓW NAWIGACJI BOCZNEJ
    const sidebarLinks = document.querySelectorAll('nav a, .sidebar a, aside a');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            const hrefAttr = this.getAttribute('href') || '';

            // Reagujemy tylko wtedy, gdy gra trwa i gracz nie klika w "Wyloguj"
            if (timeLeft > 0 && !hrefAttr.includes('logout')) {

                // Wyświetlamy okienko potwierdzenia
                const confirmLeave = confirm("CZY CHCESZ PRZERWAĆ MISJĘ?\nTwój obecny postęp w tym sektorze zostanie utracony.");

                if (!confirmLeave) {
                    // Gracz zostaje w grze – blokujemy domyślną akcję linku
                    event.preventDefault();
                } else {
                    // Gracz świadomie ucieka – wyłączamy timer, by w tle nie wyskoczył alert
                    clearInterval(countdownInterval);
                }
            }
        });
    });
});