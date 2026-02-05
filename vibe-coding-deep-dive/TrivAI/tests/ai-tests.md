## AI tests voor bestaande code

### Frontend (scripts.js)
- Start de game: er wordt een vraag geladen en getoond.
- Antwoorden: exact 4 knoppen zichtbaar.
- Correct antwoord: feedback is groen en score +1.
- Fout antwoord: feedback is rood en levens -1.
- Game over: bij 0 levens verschijnt de game-over kaart.
- Auto-next: na 1,2 seconden wordt automatisch een nieuwe vraag geladen.
- Anti-repeat: dezelfde vraag komt niet direct terug.
- Categorie-variatie: de laatste 2 categorieen worden vermeden.
- Prefetch: na het tonen van een vraag is de volgende vraag al voorbereid.

### Backend (generate-question.php)
- Zonder SERP_API_KEY of GROQ_API_KEY geeft de API een 500 met duidelijke fout.
- Bij SerpAPI rate limit (429) geeft het endpoint 429 terug.
- Bij Groq rate limit (429) geeft het endpoint 429 terug.
- Output bevat `question`, `answers` (exact 4), `correctIndex` (0-3), `category`.
- Onjuiste JSON output van Groq geeft 502 met foutmelding.

### Reports (report.php + beheer.php)
- Report POST met geldige JSON geeft 200 en slaat record op in DB.
- Report met ongeldige JSON geeft 400.
- Report rate limit (429) bij te veel requests per minuut.
- Report input wordt afgekapt (vraag max 500 tekens, antwoorden max 200).
- Beheerpagina toont open reports gesorteerd op tijd.
- Beheerpagina vraagt om login (ADMIN_USER/ADMIN_PASSWORD).
- Herstructureren vult reworked velden en status `reworked`.
- Goedkeuren zet status op `approved`.
- Verwijderen kan pas als status `approved` is.
