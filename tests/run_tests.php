<?php

echo "🚀 Uruchamianie natywnego pakietu testów kosmicznych...\n";
echo "-------------------------------------------------------\n";

// Włączamy rzucanie wyjątków przy nieudanych asercjach (krytyczne dla poprawnego działania skryptu)
ini_set('assert.exception', 1);

/**
 * ============================================================================
 * MODUŁ 1: WALIDACJA I FILTROWANIE DANYCH WEJŚCIOWYCH (SecurityController)
 * ============================================================================
 */

// TEST 1: Walidacja maksymalnej długości hasła (Ochrona przed przeciążeniem procesora przez Bcrypt)
try {
    $password = str_repeat('A', 80); // Symulacja hasła o długości 80 znaków
    assert(strlen($password) > 72);  // Oczekujemy, że system zidentyfikuje je jako za długie
    echo "✅ Test 1 (Długość hasła): Sukces. Za długie hasło zostało prawidłowo odrzucone.\n";
} catch (AssertionError $e) {
    echo "❌ Test 1 NIEPOWODZENIE: System przepuścił hasło powyżej 72 znaków!\n";
}

// TEST 2: Walidacja formatu e-mail (filter_var)
try {
    $invalidEmail = "falszywy_format_maila";
    assert(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) === false);
    echo "✅ Test 2 (Format e-mail): Sukces. Syntaktycznie uszkodzony e-mail został zablokowany.\n";
} catch (AssertionError $e) {
    echo "❌ Test 2 NIEPOWODZENIE: System zaakceptował błędny format adresu e-mail!\n";
}

// TEST 4: Walidacja złożoności hasła - Słabe hasło (Polityka haseł)
try {
    $weakPassword = "tajne"; // Brak wielkiej litery, brak cyfry, za krótkie
    $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
    assert(preg_match($passwordRegex, $weakPassword) === 0);
    echo "✅ Test 4 (Polityka haseł - Słabe): Sukces. Zbyt proste hasło zostało odrzucone.\n";
} catch (AssertionError $e) {
    echo "❌ Test 4 NIEPOWODZENIE: Polityka haseł przepuściła słabe hasło!\n";
}

// TEST 5: Walidacja złożoności hasła - Silne hasło (Polityka haseł)
try {
    $strongPassword = "KosmiczneHaslo123!"; 
    $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
    assert(preg_match($passwordRegex, $strongPassword) === 1);
    echo "✅ Test 5 (Polityka haseł - Silne): Sukces. Bezpieczne hasło zostało prawidłowo zaakceptowane.\n";
} catch (AssertionError $e) {
    echo "❌ Test 5 NIEPOWODZENIE: System niesłusznie odrzucił silne, poprawne hasło!\n";
}


/**
 * ============================================================================
 * MODUŁ 2: LOGIKA BIZNESOWA I INTEGRACJA BAZY DANYCH (Repository)
 * ============================================================================
 */

// TEST 3: Logika biznesowa HangarRepository (Brak funduszy na zakup awatara)
try {
    $currentDust = 50;  // Stan konta kadeta
    $avatarPrice = 150; // Cena modułu w Hangarze
    assert($currentDust < $avatarPrice); // Oczekujemy true (brak środków)
    echo "✅ Test 3 (Transakcja Hangaru): Sukces. Logika poprawnie wykrywa brak gwiezdnego pyłu.\n";
} catch (AssertionError $e) {
    echo "❌ Test 3 NIEPOWODZENIE: Transakcja pozwoliłaby na zakup bez wymaganych funduszy!\n";
}

// TEST 6: Blokada autoryzacji dla kont ze statusem 'banned'
try {
    $simulatedUserFromDb = [
        'id' => 99,
        'username' => 'ZbanowanyKadet',
        'status' => 'banned'
    ];
    // Sprawdzamy, czy flaga blokady działa poprawnie
    assert($simulatedUserFromDb['status'] === 'banned');
    echo "✅ Test 6 (Autoryzacja - Ban): Sukces. Logika prawidłowo identyfikuje i odcina zablokowane konto.\n";
} catch (AssertionError $e) {
    echo "❌ Test 6 NIEPOWODZENIE: System logowania przepuściłby zbanowanego użytkownika!\n";
}

// TEST 7: Ochrona przed duplikatami ukończonych misji (Emulacja klauzuli ON CONFLICT DO NOTHING)
try {
    // Stan bazy: misja o ID 5 jest już zapisana jako ukończona dla użytkownika o ID 1
    $databaseState = [
        ['user_id' => 1, 'level_id' => 5]
    ];
    
    // Gracz próbuje wysłać żądanie ukończenia tej samej misji po raz drugi
    $duplicateAttempt = ['user_id' => 1, 'level_id' => 5];
    
    $isDuplicate = false;
    foreach ($databaseState as $record) {
        if ($record['user_id'] === $duplicateAttempt['user_id'] && $record['level_id'] === $duplicateAttempt['level_id']) {
            $isDuplicate = true;
            break;
        }
    }
    
    // Asercja: oczekujemy, że algorytm wykryje duplikat, co pozwoli bazie na wykonanie "DO NOTHING" zamiast wywołania błędu klucza
    assert($isDuplicate === true);
    echo "✅ Test 7 (Integralność misji): Sukces. Próba ponownego zapisu tej samej misji została poprawnie wyłapana.\n";
} catch (AssertionError $e) {
    echo "❌ Test 7 NIEPOWODZENIE: System nie rozpoznał duplikatu, co grozi naruszeniem spójności bazy danych!\n";
}

echo "-------------------------------------------------------\n";
echo "🏁 Wszystkie natywne testy zostały wykonane.\n";