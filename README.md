###### 21-10-2016 21:30, Wersja 1
# SmartCrossing-Server
---
Serwer aplikacji SmartCrossing.

# Połączenia z serwerem
---
Serwer został napisany w oparciu o architekturę REST. Połączenie z serwerem nawiązuje się poprzez wywołanie 
adresu url odpowiadającego danej akcji używając przy tym odpowiedniej metody HTTP.

# Kody odpowiedzi
---
### Sukces
| Kod   | Opis                                           |
| :---: | -----------------------------------------------|
| 010   | Zarejestrowano                                 |
| 011   | Zalogowano                                     |
| 012   | Zalogowano używając tokena uwierzytelniającego |
| 013   | Wylogowano                                     |
| 014   | Zaktualizowano punkty użytkownika              |
| 020   | Dodano nową książkę                            |
| 021   | Dodano nową półkę                              |
| 022   | Książka zostałą dodana do półki                |
| 023   | Książka została zabrana z półki                |
| 024   | Wypożyczono ksiązkę                            |
| 025   | Oddano książkę                                 |
| 030   | Dodano propozycję nowej półki                  |
| 031   | Zagłosowano na propozycję nowej półki          |
| 032   | Zaktualizowano listę propozycji nowych półek   |
| 033   | Zaakceptowano propozycję nowej półki           |
| 034   | Odrzucono propozycję nowej półki               |

### Błąd
| Kod   | Opis                                                   |
| :---: | ------------------------------------------------------ |
| 001   | Serwer nie otrzymał danych                             |
| 002   | Serwer otrzymał błędne dane                            |
| 003   | Błąd podczas łączenia z bazą danych                    |
| 004   | Brak uprawnień                                         |
| 005   | Błąd podczas odbierania lub przetwarzania pliku        |
| 010   | Użytkownik nie istnieje                                |
| 011   | Użytkownik jest już zarejestrowany                     |
| 012   | Zalogowanie nie powiodło się                           |
| 013   | Błędny token uwierzytelniajacy                         |
| 014   | Użytkownik nie jest zalogowany                         |
| 020   | Książka nie istnieje                                   |
| 021   | Półka nie istnieje                                     |
| 022   | Książka już znajduje się na półce                      |
| 023   | Ksiązka nie znajduje się na półce                      |
| 024   | Nie można wypożyczyć książki                           |
| 025   | Nie można oddać książki                                |
| 030   | Prośba o dodanie nowej półki nie istnieje              |
| 031   | Głosowanie nad dodaniem nowej półki zostało zakończone |
| 032   | Użytkownik już zagłosował nad dodaniem nowej półki     |

### Dodatkowe kody błędów
| Kod   | Opis                                                                 |
| :---: | -------------------------------------------------------------------- |
| 001   | Błąd dotyczy adresu email użytkownika                                |
| 002   | Błąd dotyczy nazwy użytkownika                                       |
| 003   | Błąd dotyczy hasła użytkownika                                       |
| 004   | Błąd dotyczy tokena uwierzytelniającego                              |
| 005   | Błąd dotyczy numeru identyfikacyjnego książki                        |
| 006   | Błąd dotyczy numeru identyfikacyjnego półki                          |
| 007   | Błąd dotyczy współrzędnych półki                                     |
| 008   | Błąd dotyczy numeru identyfikacyjnego propozycji dodania nowej półki |

# Stałe
---
### Kategorie książek
| Kategoria | Opis                |
| --------- | ------------------- |
| fic       | Beletrystyka        |
| bio       | Biografie           |
| bai       | Biznes i inwestycje |
| ckg       | Gotowanie           |
| his       | Historia            |
| com       | Komputery           |
| cst       | Kryminały           |
| kds       | Dla dzieci          |
| pls       | Polityka            |
| law       | Prawo               |
| rel       | Religia             |
| rom       | Romanse             |
| sfi       | Sci-Fi              |
| hlt       | Zdrowie             |
| NULL      | Inna                |

### JSON
| Wartość                                   |
| ----------------------------------------- |
| error                                     |
| success                                   |
| sub_error                                 |
| error_msg                                 |
| user_id                                   |
| user_score                                |
| user_books_added_count                    |
| user_borrow_count                         |
| user_unique_borrow_count                  |
| user_return_count                         |
| user_unique_return_count                  |
| user_badge_added_books_tier               |
| user_badge_added_bookshelves_tier         |
| user_badge_books_borrowed_by_user_tier    |
| user_badge_books_borrowed_by_other_tier   |
| user_badge_score_tier                     |
| user_borrowed_books                       |
| user_global_count                         |
| book_id                                   |
| book_borrow_count                         |
| book_unique_borrow_count                  |
| book_return_count                         |
| book_unique_return_count                  |
| book_in_bookshelf                         |
| book_bookshelf_id                         |
| book_global_count                         |
| book_global_in_bookshelves_count          |
| book_global_borrow_count                  |
| book_global_unique_borrow_count           |
| book_global_return_count                  |
| book_global_unique_return_count           |
| bookshelf_books                           |
| bookshelf_books_count                     |
| bookshelf_books_borrow_general_count      |
| bookshelf_books_borrow_unique_count       |
| bookshelf_books_return_general_count      |
| bookshelf_books_return_unique_count       |
| bookshelf_global_count                    |
| bookshelves                               |
| bookshelf_requests                        |
| books                                     |
| user_email                                |
| user_password                             |
| user_auth_token                           |
| user_signed_in                            |
| user_name                                 |
| user_creation_date                        |
| user_account_type                         |
| users_badge_added_books_tier              |
| users_badge_added_bookshelves_tier        |
| users_badge_books_borrowed_by_user_tier   |
| users_badge_books_borrowed_by_others_tier |
| users_badge_score_tier                    |
| bookshelf_id                              |
| bookshelf_latitude                        |
| bookshelf_longitude                       |
| bookshelf_name                            |
| bookshelf_author                          |
| bookshelf_request_id                      |
| bookshelf_request_closing_time            |
| bookshelf_request_approved                |
| book_title                                |
| book_author                               |
| book_isbn                                 |
| book_publication_date                     |
| book_category                             |
| book_cover                                |
| book_user_author                          |
| book_cover_http                           |
| book_cover_https                          |
| book_adder                                |
| borrowed_books                            |
| borrow_id                                 |
| borrow_time                               |
| returned_books                            |
| return_id                                 |
| return_time                               |

## Funkcje
---
### .../user/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia                                               | Akcja                       |
| ----------- | ---------------- | ------------ | -------------------------------------------------------------- | --------------------------- |
| GET         |                  |              |                                                                | Brak                        |
| POST        | application/json | JSON         | {"user_email":"...", "user_name":"...", "user_password":"..."} | Zarejestrowanie użytkownika |
| PUT         |                  |              |                                                                | Brak                        |
| DELETE      |                  |              |                                                                | Brak                        |

### .../user/{id}/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                       |
| ----------- | ------------ | ------------ | ---------------- | --------------------------- |
| GET         | -            | -            | -                | Pobiera dane użytkownika    |
| POST        |              |              |                  | Brak                        |
| PUT         |              |              |                  | Brak                        |
| DELETE      |              |              |                  | Brak                        |

### .../user/sign/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia                            | Akcja                   |
| ----------- | ---------------- | ------------ | ------------------------------------------- | ----------------------- |
| GET         |                  |              |                                             | Brak                    |
| POST        | application/json | JSON         | {"user_email":"...", "user_password":"..."} | Zalogowanie użytkownika |
| PUT         |                  |              |                                             | Brak                    |
| DELETE      |                  |              |                                             | Brak                    |

### .../user/auth/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                       |
| ----------- | ------------ | ------------ | ---------------- | --------------------------- |
| GET         |              |              |                  | Brak                        |
| POST        |              |              |                  | Brak                        |
| PUT         |              |              |                  | Brak                        |
| DELETE      |              |              |                  | Brak                        |

### .../user/auth/{token}/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia                                               | Akcja                                              |
| ----------- | ------------ | ------------ | -------------------------------------------------------------- | -------------------------------------------------- |
| GET         | -            | -            | -                                                              | Sprawdzanie poprawności tokena uwierzytelniającego |
| POST        |              |              |                                                                | Brak                                               |
| PUT         |              |              |                                                                | Brak                                               |
| DELETE      | -            | -            | -                                                              | Wylogowanie użytkownika                            |

### .../user/{id}/book/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                                               |
| ----------- | ------------ | ------------ | ---------------- | --------------------------------------------------- |
| GET         | -            | -            | -                | Zwraca listę książek wypożyczonych przz użytkownika |
| POST        |              |              |                  | Brak                                                |
| PUT         |              |              |                  | Brak                                                |
| DELETE      |              |              |                  | Brak                                                |

### .../user/stats/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                                   |
| ----------- | ------------ | ------------ | ---------------- | --------------------------------------- |
| GET         | -            | -            | -                | Zwraca globalne statystyki użytkowników |
| POST        |              |              |                  | Brak                                    |
| PUT         |              |              |                  | Brak                                    |
| DELETE      |              |              |                  | Brak                                    |

### .../user/{id}/stats/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                           |
| ----------- | ------------ | ------------ | ---------------- | ------------------------------- |
| GET         | -            | -            | -                | Zwraca statystyki użytkownika   |
| POST        |              |              |                  | Brak                            |
| PUT         | -            | -            | -                | Odświeża statystyki użytkownika |
| DELETE      |              |              |                  | Brak                            |

### .../user/ranking/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                        |
| ----------- | ------------ | ------------ | ---------------- | ---------------------------- |
| GET         | -            | -            | -                | Zwraca ranking użytkowników  |
| POST        |              |              |                  | Brak                         |
| PUT         |              |              |                  | Brak                         |
| DELETE      |              |              |                  | Brak                         |

### .../user/ranking/{limit}/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                                                               |
| ----------- | ------------ | ------------ | ---------------- | ------------------------------------------------------------------- |
| GET         | -            | -            | -                | Zwraca ranking użytkowników. Lista {limit} najlepszych użytkowników |
| POST        |              |              |                  | Brak                                                                |
| PUT         |              |              |                  | Brak                                                                |
| DELETE      |              |              |                  | Brak                                                                |

### .../bookshelf/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia                                                                                       | Akcja                                                              |
| ----------- | ---------------- | ------------ | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| GET         | -                | -            | -                                                                                                      | Zwraca listę półek                                                 |
| POST        | application/json | JSON         | {"user_auth_token":"...", "bookshelf_latitude":0.0, "bookshelf_longitude":0.0, "bookshelf_name":"..."} | Dodaje nową półkę (admin) lub propozycję półki (zwykły użytkownik) |
| PUT         |                  |              |                                                                                                        | Brak                                                               |
| DELETE      |                  |              |                                                                                                        | Brak                                                               |

### .../bookshelf/{id}/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                     |
| ----------- | ------------ | ------------ | ---------------- | ------------------------- |
| GET         | -            | -            | -                | Zwraca informacje o półce |
| POST        |              |              |                  | Brak                      |
| PUT         |              |              |                  | Brak                      |
| DELETE      |              |              |                  | Brak                      |

### .../bookshelf/stats/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                            |
| ----------- | ------------ | ------------ | ---------------- | -------------------------------- |
| GET         | -            | -            | -                | Zwraca globalne statystyki półek |
| POST        |              |              |                  | Brak                             |
| PUT         |              |              |                  | Brak                             |
| DELETE      |              |              |                  | Brak                             |

### .../bookshelf/{id}/stats/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                   |
| ----------- | ------------ | ------------ | ---------------- | ----------------------- |
| GET         | -            | -            | -                | Zwraca statystyki półki |
| POST        |              |              |                  | Brak                    |
| PUT         |              |              |                  | Brak                    |
| DELETE      |              |              |                  | Brak                    |

### .../bookshelf/{id}/book/

| Metoda HTTP | Content-Type           | Opis wejścia                        | Przykład wejścia                                                                                                                                     | Akcja                                               |
| ----------- | ---------------------- | ----------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| GET         | -                      | -                                   | -                                                                                                                                                    | Zwraca listę książek na półce                       |
| POST        | application/json       | JSON                                | {"user_auth_token":"...", "book_title":"...", "book_author":"...", "book_isbn":"...", "book_publication_date":0, "book_category":/patrz wyżej/}      | Dodaje książkę do bazy danych, a następnie na półkę |
| POST        | application/multipart  | okładka - "uploaded", JSON - "json" | json={"user_auth_token":"...", "book_title":"...", "book_author":"...", "book_isbn":"...", "book_publication_date":0, "book_category":/patrz wyżej/} | Dodaje książkę do bazy danych, a następnie na półkę |
| PUT         |                        |                                     |                                                                                                                                                      | Brak                                                |
| DELETE      |                        |                                     |                                                                                                                                                      | Brak                                                |

### .../bookshelf/{id}/book/{book_id}

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                     |
| ----------- | ---------------- | ------------ | ------------------------- | ------------------------- |
| GET         |                  |              |                           | Brak                      |
| POST        | application/json | JSON         | {"user_auth_token":"..."} | Odkłąda książkę na półkę  |
| PUT         |                  |              |                           | Brak                      |
| DELETE      | application/json | JSON         | {"user_auth_token":"..."} | Wypożycza ksiązkę z półki |

### .../bookshelf/request/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                                |
| ----------- | ------------ | ------------ | ---------------- | ------------------------------------ |
| GET         | -            | -            | -                | Zwraca listę propozycji nowych półek |
| POST        |              |              |                  | Brak                                 |
| PUT         |              |              |                  | Brak                                 |
| DELETE      |              |              |                  | Brak                                 |

### .../bookshelf/request/admin/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja                                                                      |
| ----------- | ------------ | ------------ | ---------------- | -------------------------------------------------------------------------- |
| GET         | -            | -            | -                | Zwraca listę propozycji nowych półek oczekujących na decyzję administracji |
| POST        |              |              |                  | Brak                                                                       |
| PUT         |              |              |                  | Brak                                                                       |
| DELETE      |              |              |                  | Brak                                                                       |

### .../bookshelf/request/{id}/admin/accept/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                                          |
| ----------- | ---------------- | ------------ | ------------------------- | ---------------------------------------------- |
| GET         |                  |              |                           | Brak                                           |
| POST        | application/json | JSON         | {"user_auth_token":"..."} | Akceptuje propozycję nowej półki (tylko admin) |
| PUT         |                  |              |                           | Brak                                           |
| DELETE      |                  |              |                           | Brak                                           |

### .../bookshelf/request/{id}/admin/reject/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                                        |
| ----------- | ---------------- | ------------ | ------------------------- | -------------------------------------------- |
| GET         |                  |              |                           | Brak                                         |
| POST        | application/json | JSON         | {"user_auth_token":"..."} | Odrzuca propozycję nowej półki (tylko admin) |
| PUT         |                  |              |                           | Brak                                         |
| DELETE      |                  |              |                           | Brak                                         |

### .../bookshelf/request/{id}/vote/

| Metoda HTTP | Content-Type | Opis wejścia | Przykład wejścia | Akcja |
| ----------- | ------------ | ------------ | ---------------- | ----- |
| GET         |              |              |                  | Brak  |
| POST        |              |              |                  | Brak  |
| PUT         |              |              |                  | Brak  |
| DELETE      |              |              |                  | Brak  |

### .../bookshelf/request/{id}/vote/approve/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                           |
| ----------- | ---------------- | ------------ | ------------------------- | ------------------------------- |
| GET         |                  |              |                           | Brak                            |
| POST        | application/json | JSON         | {"user_auth_token":"..."} | Głosuje za dodaniem nowej półki |
| PUT         |                  |              |                           | Brak                            |
| DELETE      |                  |              |                           | Brak                            |

### .../bookshelf/request/{id}/vote/disapprove/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                               |
| ----------- | ---------------- | ------------ | ------------------------- | ----------------------------------- |
| GET         |                  |              |                           | Brak                                |
| POST        | application/json | JSON         | {"user_auth_token":"..."} | Głosuje przeciw dodaniu nowej półki |
| PUT         |                  |              |                           | Brak                                |
| DELETE      |                  |              |                           | Brak                                |

### .../book/

| Metoda HTTP | Content-Type           | Opis wejścia                        | Przykład wejścia                                                                                                                                     | Akcja                         |
| ----------- | ---------------------- | ----------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------- |
| GET         | -                      | -                                   | -                                                                                                                                                    | Brak                          |
| POST        | application/json       | JSON                                | {"user_auth_token":"...", "book_title":"...", "book_author":"...", "book_isbn":"...", "book_publication_date":0, "book_category":/patrz wyżej/}      | Dodaje książkę do bazy danych |
| POST        | application/multipart  | okładka - "uploaded", JSON - "json" | json={"user_auth_token":"...", "book_title":"...", "book_author":"...", "book_isbn":"...", "book_publication_date":0, "book_category":/patrz wyżej/} | Dodaje książkę do bazy danych |
| PUT         |                        |                                     |                                                                                                                                                      | Brak                          |
| DELETE      |                        |                                     |                                                                                                                                                      | Brak                          |

### .../book/{id}/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                        |
| ----------- | ---------------- | ------------ | ------------------------- | ---------------------------- |
| GET         | -                | -            | -                         | Pobiera informacje o książce |
| POST        |                  |              |                           | Brak                         |
| PUT         |                  |              |                           | Brak                         |
| DELETE      |                  |              |                           | Brak                         |

### .../book/stats/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                               |
| ----------- | ---------------- | ------------ | ------------------------- | ----------------------------------- |
| GET         | -                | -            | -                         | Pobiera globalne statystyki książek |
| POST        |                  |              |                           | Brak                                |
| PUT         |                  |              |                           | Brak                                |
| DELETE      |                  |              |                           | Brak                                |

### .../book/{id}/stats/

| Metoda HTTP | Content-Type     | Opis wejścia | Przykład wejścia          | Akcja                      |
| ----------- | ---------------- | ------------ | ------------------------- | -------------------------- |
| GET         | -                | -            | -                         | Pobiera statystyki książki |
| POST        |                  |              |                           | Brak                       |
| PUT         |                  |              |                           | Brak                       |
| DELETE      |                  |              |                           | Brak                       |

