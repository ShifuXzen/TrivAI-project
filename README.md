# TrivAI-project
Infinite Trivia AI is een browserbased trivia‑game waarin je met 3 levens zo lang mogelijk meerkeuzevragen probeert te beantwoorden. Dit project combineert live AI‑vragen (SerpAPI + Groq), lokale fallback‑vragen, een report‑flow en een beheerpagina voor moderatie.

**Belangrijkste features**
1. Live AI‑vragen met bronnen, afkomstig uit SerpAPI en samengesteld door Groq.
2. Lokale fallback‑vragen wanneer API‑limieten bereikt zijn.
3. Rapportageknop voor verdachte AI‑vragen.
4. Beheerpagina om vragen goed te keuren of te herstructureren.
5. Anti‑repeat logica en categorie‑afwisseling.

**Projectstructuur**
1. `TrivAI-project/vibe-coding-deep-dive/TrivAI/index.php` – HTML‑shell van de game.
2. `TrivAI-project/vibe-coding-deep-dive/TrivAI/style.css` – UI‑stijl (Google‑achtige look).
3. `TrivAI-project/vibe-coding-deep-dive/TrivAI/scripts.js` – game‑logica en UI‑interactie.
4. `TrivAI-project/vibe-coding-deep-dive/TrivAI/generate-question.php` – haalt bronnen op via SerpAPI en laat Groq een vraag genereren.
5. `TrivAI-project/vibe-coding-deep-dive/TrivAI/report.php` – slaat rapportages op in de database.
6. `TrivAI-project/vibe-coding-deep-dive/TrivAI/beheer.php` – beheerpagina voor moderatie (goedkeuren of herstructureren).
7. `TrivAI-project/vibe-coding-deep-dive/TrivAI/db.php` – PDO‑verbinding en DB‑config.
8. `TrivAI-project/vibe-coding-deep-dive/TrivAI/config.php` – leest `.env` en levert configuratie aan de backend.
9. `TrivAI-project/vibe-coding-deep-dive/TrivAI/reports.sql` – SQL‑schema voor rapportages.
10. `TrivAI-project/vibe-coding-deep-dive/TrivAI/question-cache.json` – cache van live vragen.

**Hoe werkt de game (code‑uitleg)**
1. `scripts.js` laadt bij start een vraag via `generate-question.php`.
2. Een vraag bevat `question`, `answers`, `correctIndex`, `category` en `sources`.
3. Na een antwoord wordt feedback getoond en gaat de game na 1,2s door.
4. Bij fout antwoord gaan de levens omlaag; bij 0 levens verschijnt de game‑over kaart.
5. Antwoorden worden in een “recent queue” gezet zodat dezelfde vraag of categorie niet meteen terugkomt.
6. De volgende vraag wordt vooraf opgehaald (prefetch) om wachttijd te verminderen.

**AI‑vraag generatie (backend‑uitleg)**
1. `generate-question.php` bouwt een SerpAPI‑query op basis van een categorie.
2. De topresultaten worden omgezet naar een compacte bronnenlijst.
3. Groq krijgt een prompt met regels (exact 4 antwoorden, 1 correct) en de bronnen.
4. De JSON‑output wordt gevalideerd en teruggegeven aan de frontend.
5. De vraag wordt in `question-cache.json` opgeslagen om hergebruik en snelheid te verbeteren.

**Rapportage‑flow (code‑uitleg)**
1. In de UI zit de knop “AI vraag rapporteren”.
2. `scripts.js` stuurt de huidige vraag naar `report.php`.
3. `report.php` schrijft het report weg in MySQL via `db.php`.
4. `beheer.php` laat de reports zien en biedt acties:
5. “Goedkeuren” verwijdert het report direct uit de DB.
6. “Herstructureren” haalt nieuwe bronnen op en laat Groq een nieuwe vraag genereren.

**Beheer‑flow (code‑uitleg)**
1. `beheer.php` toont een tabel met de laatste 200 reports.
2. Filters tonen `open`, `reworked`, `approved` of `all`.
3. Herstructureren slaat de nieuwe vraag op in de extra kolommen.
4. De originele vraag blijft zichtbaar naast de herstructureerde variant.

**Eigen TrivAI‑omgeving opzetten (uitgebreid)**
1. Zorg dat je PHP 8+ hebt met `curl`, `json`, `mbstring` en `pdo_mysql` extensies.
2. Maak een MySQL database aan (bijv. `trivai`).
3. Maak de tabel aan door `reports.sql` uit te voeren in phpMyAdmin.
4. Voeg database‑gegevens en API‑keys toe in `TrivAI-project/vibe-coding-deep-dive/TrivAI/.env`.
5. Start een lokale PHP‑server in de TrivAI‑map.
6. Open `index.php` in de browser.
7. Open `beheer.php` om reports te beheren.

**Voorbeeld `.env`**
```env
# SerpAPI
SERP_API_KEY=your_serp_api_key
SERP_ENDPOINT=https://serpapi.com/search.json
SERP_ENGINE=google
SERP_GL=nl
SERP_HL=nl
SERP_NUM=6
SERP_GOOGLE_DOMAIN=google.nl
SERP_START_MAX=20

# Groq API
GROQ_API_KEY=your_groq_api_key
GROQ_MODEL=llama-3.3-70b-versatile
GROQ_ENDPOINT=https://api.groq.com/openai/v1
GROQ_TIMEOUT=20

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=trivai
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4

# Cache
CACHE_TTL=900
CACHE_MAX=50
```

**Lokale server starten (voorbeeld)**
```bash
php -S localhost:8000 -t TrivAI-project/vibe-coding-deep-dive/TrivAI
```

**Handige URL’s**
1. Game: `http://localhost:8000/index.php`
2. Beheer: `http://localhost:8000/beheer.php`

**Veelvoorkomende problemen**
1. `Groq request failed` of `SerpAPI rate limit` betekent dat je API‑quota op is.
2. `Unknown column 'status'` betekent dat je de DB‑kolommen nog niet hebt toegevoegd.
3. `Database configuratie ontbreekt` betekent dat `.env` nog geen `DB_*` velden bevat.
