# Bibliotheek REST API Specificatie

Deze specificatie beschrijft de endpoints van de Bibliotheek REST API, gebaseerd op het resource map diagram.

## Base URL
`/api/v1`

## 1. Catalogus (`/catalogus`)
Beheer en ontsluiting van de boekencollectie.

### `GET /catalogus/boeken`
Zoek naar boeken in de catalogus.
- **Parameters:**
  - `q` (query, optioneel): Zoekterm (titel, auteur).
  - `genre` (query, optioneel): Filter op genre.
- **Response (200 OK):**
  ```json
  [
    {
      "id": "123",
      "titel": "De Aanslag",
      "auteur": "Harry Mulisch",
      "beschikbaar": true
    }
  ]
  ```

### `POST /catalogus/boeken`
Voeg een nieuw boek toe aan de collectie (Alleen personeel).
- **Body:**
  ```json
  {
    "isbn": "978902343",
    "titel": "Nieuw Boek",
    "auteur": "Auteur Naam"
  }
  ```
- **Response (201 Created):**
  - Bevat de ID van het aangemaakte boek.

### `GET /catalogus/boeken/:id`
Haal gedetailleerde informatie op van één specifiek boek.
- **Path parameters:**
  - `id`: De unieke ID van het boek.
- **Response (200 OK):**
  ```json
  {
    "id": "123",
    "titel": "De Aanslag",
    "samenvatting": "...",
    "locatie": "Kast 4, Plank B",
    "exemplaren": 3
  }
  ```

---

## 2. Transacties (`/transacties`)
Endpoints voor het lenen, retourneren en reserveren van boeken.

### `POST /transacties/lenen`
Registreer een nieuwe uitlening.
- **Body:**
  ```json
  {
    "lidId": "LID-001",
    "boekId": "123"
  }
  ```
- **Response (200 OK):**
  - Bevestiging van uitlening met inleverdatum.

### `POST /transacties/retourneren`
Verwerk de inname van een boek.
- **Body:**
  ```json
  {
    "exemplaarId": "EX-999"
  }
  ```
- **Response (200 OK):**
  - Bevestiging van inname en eventuele boete-status.

### `POST /transacties/reserveren`
Reserveer een boek dat momenteel is uitgeleend.
- **Body:**
  ```json
  {
    "lidId": "LID-001",
    "boekId": "123"
  }
  ```
- **Response (201 Created):**
  - Bevestiging van plaatsing op wachtlijst.

---

## 3. Account (`/account`)
Persoonlijke gegevens voor het ingelogde lid.

### `GET /account/profiel`
Haal NAW-gegevens van de gebruiker op.
- **Response (200 OK):**
  ```json
  {
    "naam": "Jan Jansen",
    "email": "jan@example.com",
    "lidSinds": "2023-01-01"
  }
  ```

### `GET /account/historie`
Bekijk de geschiedenis van geleende boeken.
- **Response (200 OK):**
  ```json
  [
    {
      "boek": "Harry Potter",
      "leenDatum": "2023-10-01",
      "retourDatum": "2023-10-21"
    }
  ]
  ```

### `GET /account/boetes`
Bekijk openstaande boetes.
- **Response (200 OK):**
  ```json
  {
    "totaalBedrag": 2.50,
    "items": [
      {
        "boek": "De Aanslag",
        "reden": "Te laat ingeleverd",
        "bedrag": 2.50
      }
    ]
  }
  ```
