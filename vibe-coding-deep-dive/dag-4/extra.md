## Extra (optioneel)

### Nice-to-have user story
- Score zichtbaar tijdens het spel, telt correcte antwoorden en reset bij retry.

### AI tests
- Tests staan in `TrivAI-project/vibe-coding-deep-dive/TrivAI/tests/ai-tests.md`.

### Performance issues (AI-benodigd)
- Live vraaggeneratie is afhankelijk van twee API-calls, wat latency kan geven.
- Elke vraag leest en schrijft `question-cache.json`, dit schaalt minder goed bij veel verkeer.
- Prefetch kan extra requests veroorzaken als de speler heel snel klikt.

### Security issues (AI-benodigd)
- `.env` mag nooit publiek toegankelijk zijn; webroot moet correct staan.
- `beheer.php` heeft geen authenticatie, waardoor reports publiek zichtbaar zijn.
- `report.php` accepteert elke POST en kan misbruikt worden zonder rate limiting.

### Refactor voorstellen
- Splits `generate-question.php` op in kleinere services (SerpAPI, Groq, cache).
- Haal spel-logica uit `scripts.js` naar losse modules (state, UI, networking).
- Maak een centrale categorie-lijst die frontend en backend delen.
