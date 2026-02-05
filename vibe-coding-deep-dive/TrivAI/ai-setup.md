## AI Setup (SerpAPI + Groq)

### 1. Benodigdheden

* Een SerpAPI account + API key
* Een Groq API key

### 2. .env aanmaken

1. Kopieer `.env.example` naar `.env`
2. Vul minimaal deze variabelen in:
   * `SERP_API_KEY`
   * `GROQ_API_KEY`
   * `GROQ_MODEL`

### 3. Optionele SerpAPI instellingen

* `SERP_ENGINE` (default: `google`)
* `SERP_GL` (default: `nl`)
* `SERP_HL` (default: `nl`)
* `SERP_NUM` (default: `6`)
* `SERP_GOOGLE_DOMAIN` (default: `google.nl`)
* `SERP_START_MAX` (default: `20`)

### 4. Optionele Groq instellingen

* `GROQ_ENDPOINT` (default: `https://api.groq.com/openai/v1`)
* `GROQ_TIMEOUT` (default: `20`)

### 5. Optionele cache instellingen

* `CACHE_TTL` (default: `900` seconden)
* `CACHE_MAX` (default: `50` vragen)

### 6. Lokaal draaien

1. Start een PHP server in de `TrivAI` map:
   * `php -S localhost:8000`
2. Open `http://localhost:8000/index.php`

### 7. Test de endpoint

* `http://localhost:8000/generate-question.php`
* Optioneel: `http://localhost:8000/generate-question.php?category=Sport`

### 8. Fallback gedrag

Als de live call faalt, valt de app automatisch terug op de lokale vragenlijst.
