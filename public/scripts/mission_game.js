// public/js/mission_game.js

let levelId = 0;
let minNum = 0;
let maxNum = 0;

let correctAnswers = 0;
let mistakes = 0;
let currentTargetAnswer = 0;

// Czekamy na załadowanie struktury dokumentu przez przeglądarkę
document.addEventListener("DOMContentLoaded", () => {
    const gameNode = document.getElementById('mission-game-node');

    if (gameNode) {
        // Pobranie danych konfiguracyjnych przekazanych przez HTML data attributes
        levelId = parseInt(gameNode.getAttribute('data-id'));
        minNum = parseInt(gameNode.getAttribute('data-min'));
        maxNum = parseInt(gameNode.getAttribute('data-max'));

        // Inicjalizacja pierwszego pytania misji
        generateMissionQuestion();
    }

    // Obsługa klawisza Enter w polu tekstowym odpowiedzi
    const inputField = document.getElementById('player-answer');
    if (inputField) {
        inputField.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                checkAnswer();
            }
        });
    }
});

// Generowanie losowego pytania z zakresu misji
function generateMissionQuestion() {
    const factor1 = Math.floor(Math.random() * (maxNum - minNum + 1)) + minNum;
    const factor2 = Math.floor(Math.random() * (maxNum - minNum + 1)) + minNum;
    currentTargetAnswer = factor1 * factor2;

    document.getElementById('num1').innerText = factor1;
    document.getElementById('num2').innerText = factor2;

    const inputField = document.getElementById('player-answer');
    if (inputField) {
        inputField.value = '';
        inputField.focus();
    }
}

// Weryfikacja odpowiedzi gracza (globalna dla atrybutu onclick w HTML)
window.checkAnswer = function () {
    const inputField = document.getElementById('player-answer');
    const playerAnswer = parseInt(inputField.value);

    // Blokada pustego zatwierdzania
    if (isNaN(playerAnswer)) return;

    if (playerAnswer === currentTargetAnswer) {
        correctAnswers++;
        document.getElementById('progress-text').innerText = `${correctAnswers} / 20`;
    } else {
        mistakes++;
        updateHeartsDisplay();
    }

    // WARUNEK PRZEGRANEJ: 3 błędy
    if (mistakes >= 3) {
        alert("KRYTYCZNY BŁĄD! Systemy statku przeciążone. Nie zaliczasz tej misji.");
        window.location.href = "/mission";
        return;
    }

    // WARUNEK WYGRANEJ: 20 poprawnych odpowiedzi
    if (correctAnswers >= 20) {
        fetch('/save-mission', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ level_id: levelId, status: 'victory' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Zaktualizowany komunikat dla gracza z uwzględnieniem EXP
                    alert(`MISJA ZAKOŃCZONA SUKCESEM!\n\nSystemy ustabilizowane.\nZdobywasz:\n✨ ${data.reward} Gwiezdnego Pyłu\n🛡️ +${data.exp_reward} EXP!`);
                } else {
                    alert("Misja ukończona, ale system sieciowy Akademii nie zapisał nagrody.");
                }
                window.location.href = "/mission";
            })
            .catch(err => {
                console.error("Mission Save Error:", err);
                window.location.href = "/mission";
            });
        return;
    }

    // Następne pytanie
    generateMissionQuestion();
}

// Aktualizacja graficzna serduszek (żyć)
function updateHeartsDisplay() {
    const livesContainer = document.getElementById('lives-container');
    if (!livesContainer) return;

    let heartsHtml = '';
    const remainingLives = 3 - mistakes;

    for (let i = 0; i < remainingLives; i++) {
        heartsHtml += '<i class="fa-solid fa-heart"></i> ';
    }

    if (remainingLives === 0) {
        heartsHtml = '<span class="hull-failed">ZNISZCZONE</span>';
    }

    livesContainer.innerHTML = heartsHtml;
}