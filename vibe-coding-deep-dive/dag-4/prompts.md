## Promptlog - Dag 4

### User-story 1 prompts:

### 1. Basis UI voor user-story 1

**Prompt:**
* 1. Als speler wil ik een trivia-vraag met vier antwoordopties zien zodat ik mijn kennis kan testen.
* Prioriteit: must
* Acceptatiecriteria:
* De vraag bevat exact vier keuzemogelijkheden
* De vraagtekst en antwoorden worden duidelijk weergegeven op het scherm
* maak hiervoor gebruik van de index.php, scripts.js en style.css file waar nodig is

**Antwoord (AI):**
* HTML-structuur toegevoegd in `index.php` met een vraag en antwoordcontainer
* JavaScript (`scripts.js`) rendert een vraag met exact vier antwoordopties
* CSS (`style.css`) toegevoegd voor duidelijke weergave en basis layout

### 2. User-story 3 - Levens tonen

**Prompt:**
* 3. Als speler wil ik mijn resterende levens kunnen zien zodat ik weet hoeveel fouten ik nog mag maken.
* Prioriteit: must
* Acceptatiecriteria:
* Het aantal resterende levens is altijd zichtbaar tijdens het spel
* Bij een fout antwoord wordt het aantal levens verminderd

**Antwoord (AI):**
* Levens-indicator toegevoegd in `index.php`
* Logica in `scripts.js` om levens te tonen en bij fout antwoord te verlagen
* Styling toegevoegd in `style.css` voor de levensbadge

### 3. Game Over met retry

**Prompt:**
* ja ik zou graag ook een game over melding willen, met voor nu alleen een retry knop

**Antwoord (AI):**
* Game Over blok toegevoegd in `index.php` met retry knop
* Logica in `scripts.js` om Game Over te tonen bij 0 levens en resetten via retry
* Styling toegevoegd in `style.css` voor de Game Over melding en knop

### 4. Google-achtige styling

**Prompt:**
* Als het kan wil ik de style van de pagina een beetje op een ripoff google search pagina laten lijken, image is de inspiratie

**Antwoord (AI):**
* `index.php` aangepast met logo, zoekbalk en layout
* `style.css` aangepast naar Google-achtige look & feel

### 5. Promptlog aanvullen

**Prompt:**
* Stop de prompt + het antwoord van het genereren van de 1ste user-story in de md

**Antwoord (AI):**
* Promptlog bijgewerkt in `dag-4/prompts.md`

### 6. User-story 2 - Direct feedback

**Prompt:**
* 2. Als speler wil ik direct feedback krijgen op mijn antwoord zodat ik weet of ik goed of fout heb geantwoord.
* Prioriteit: must
* Acceptatiecriteria:
* Na het klikken op een antwoord verschijnt er direct feedback
* Feedback geeft aan of het gekozen antwoord correct of fout is

**Antwoord (AI):**
* Feedback-element toegevoegd in `index.php`
* `scripts.js` toont goed/fout feedback en kleurt de gekozen knop
* `style.css` styling toegevoegd voor feedback

### 7. Game Over alleen bij 0 levens

**Prompt:**
* zorg er wel voor dat de game over melding alleen daadwerkelijk verschijnt als al je levens op zijn

**Antwoord (AI):**
* Logica aangepast in `scripts.js` zodat Game Over alleen bij 0 levens verschijnt

### 8. Game Over bleef zichtbaar

**Prompt:**
* waarom zie ik nogsteeds de game over melding terwijl ik 3 levens heb?

**Antwoord (AI):**
* CSS fix: `.game-over[hidden]{display:none}` toegevoegd in `style.css`

### 9. User-story 4 - Auto volgende vraag

**Prompt:**
* 4. Als speler wil ik dat het spel automatisch doorgaat naar de volgende vraag zodat ik zonder onderbreking kan blijven spelen.
* Prioriteit: must
* Acceptatiecriteria:
* Bij een goed antwoord wordt automatisch een nieuwe vraag geladen
* Er is een korte vertraging (max 2 seconden) tussen vragen

**Antwoord (AI):**
* `scripts.js` uitgebreid met vragenlijst en auto-advance (1.2s delay)

### 10. Encoding issue Italië

**Prompt:**
* waarom staat italie er zo gek bij in deze lijn code?

**Antwoord (AI):**
* Uitleg over encoding mismatch
* Oplossing voorgesteld met Unicode escape

### 11. Encoding fix toegepast

**Prompt:**
* ja ik wil dit gecorrigeerd hebben, ook staat de encoding al op UTF-8

**Antwoord (AI):**
* Unicode escape toegepast in `scripts.js`

### 12. Controle overige encoding

**Prompt:**
* graag

**Antwoord (AI):**
* Bestanden gecontroleerd en encoding gecorrigeerd waar nodig

### 13. UTF-8 instellingen

**Prompt:**
* hoe kan ik aanpassen dat jij encode in UTF-8

**Antwoord (AI):**
* Uitleg VS Code encoding + `.editorconfig` advies

### 14. Fout antwoord stopt flow

**Prompt:**
* ik heb een nieuw issue gevonden, dat is dat wanneer ik een antwoord fout beantwoord, de vragen niet verder gaan

**Antwoord (AI):**
* Uitleg dat er alleen bij goede antwoorden werd doorgeschakeld

### 15. Auto door bij fout antwoord

**Prompt:**
* ook bij een fout antwoord is de bedoeling dat de vragen gewoon doorgaan

**Antwoord (AI):**
* `scripts.js` aangepast: ook bij fout antwoord doorgaan zolang levens > 0

### 16. User-story 6 - Zonder refresh

**Prompt:**
* 6. Als speler wil ik vragen blijven ontvangen zonder pagina-verversing zodat het spel soepel en snel aanvoelt.
* Prioriteit: must
* Acceptatiecriteria:
* Nieuwe vragen worden geladen via JavaScript zonder de pagina te herladen
* De gebruiker blijft op dezelfde pagina gedurende het spel

**Antwoord (AI):**
* Bevestigd dat dit al via JS gebeurt

### 17. User-story 7 - Variatie categorieën

**Prompt:**
* 7. Als speler wil ik dat de vragen afwisselend zijn qua onderwerp zodat het spel gevarieerd blijft.
* Prioriteit: nice
* Acceptatiecriteria:
* Vragen worden willekeurig geselecteerd uit verschillende categorieën
* Geen twee opeenvolgende vragen gaan over exact hetzelfde onderwerp

**Antwoord (AI):**
* Categorieën toegevoegd en random selectie geïmplementeerd in `scripts.js`

### 18. User-story 8 - AI vragen + rapporteren

**Prompt:**
* 8. Als ontwikkelaar wil ik AI gebruiken om vragen te genereren zodat ik snel veel unieke content heb voor het spel.
* Prioriteit: nice
* Acceptatiecriteria:
* De AI kan minimaal 20 unieke vragen aanleveren
* Elke vraag bevat een correct antwoord en drie foute opties
* Er is ook een knop "AI vraag rapporteren" die de speler in kan drukken

**Antwoord (AI):**
* 20 vragen toegevoegd in `scripts.js`
* Rapport knop + `report.php` endpoint toegevoegd
* Styling in `style.css` toegevoegd

### 19. Live web scraping AI?

**Prompt:**
* is er een bepaalde ai-api die live kan scrubben op het web om nieuwe vragen te genereren

**Antwoord (AI):**
* Uitleg: zoek-API + LLM combineren (geen single API)

### 20. Brave Search API flow

**Prompt:**
* hoe zou ik de brave search api, kunnen gebruiken om vragen te genereren

**Antwoord (AI):**
* Flow + voorbeeld request en LLM prompt structuur uitgelegd

### 21. Setup zonder secrets

**Prompt:**
* zou je voor mij alles op kunnen zetten behalve alle gevoelige informatie zodat ik dat zelf op kan zetten?

**Antwoord (AI):**
* Config + `.env.example` + `generate-question.php` + `.gitignore` toegevoegd

### 22. SerpAPI gebruiken

**Prompt:**
* zou je dit aan kunnen passen zodat ik kan gebruiken met de API serp?

**Antwoord (AI):**
* SerpAPI integratie toegevoegd + config/.env aangepast

### 23. AI setup doc

**Prompt:**
* als het kan voeg een ai-setup toe

**Antwoord (AI):**
* `ai-setup.md` toegevoegd

### 24. ChatGPT als LLM?

**Prompt:**
* Een LLM endpoint dat JSON accepteert en choices[0].message.content teruggeeft
* Kan chatgpt dit?

**Antwoord (AI):**
* Ja via OpenAI Chat Completions; Responses API uitgelegd

### 25. LLM_API_URL

**Prompt:**
* hoe kom ik aan de LLM_API_URL?

**Antwoord (AI):**
* Chat Completions endpoint gegeven

### 26. LLM model kiezen

**Prompt:**
* hoe weet ik welk model de llm heeft?

**Antwoord (AI):**
* Modellenlijst via API en docs uitgelegd

### 27. Absolute path voorbeeld

**Prompt:**
* laat een voorbeeld van een absolute path zien

**Antwoord (AI):**
* Voorbeeld Windows pad gegeven

### 28. OpenAI API kosten

**Prompt:**
* kan ik via de openAI api gratis requests pullen of kost het geld?

**Antwoord (AI):**
* Uitleg pay-as-you-go en credits/abonnementen

### 29. Gratis alternatieven

**Prompt:**
* zijn er alternatieven voor die wel gratis requests kunnen pullen?

**Antwoord (AI):**
* Gemini, Mistral, HF, Groq free tiers + SerpAPI free plan genoemd

### 30. Gemini vs Serp

**Prompt:**
* ik twijfel tussen gemini en serp

**Antwoord (AI):**
* Uitleg verschil: LLM vs search

### 31. Offline AI vragen

**Prompt:**
* als ik het offline doe, kan ik dan wel nog door ai vragen laten genereren?

**Antwoord (AI):**
* Uitleg: LLM-only met internet of lokale LLM zonder internet

### 32. SerpAPI voorkeur

**Prompt:**
* ah ok, nee dan heb ik liever dat serpAPI gebruikt word

**Antwoord (AI):**
* Bevestigd, gevraagd naar LLM provider

### 33. Over naar Gemini

**Prompt:**
* Als het kan zou ik over willen naar gemini

**Antwoord (AI):**
* Gemini integratie toegevoegd + `.env.example` en `ai-setup.md` aangepast

### 34. .env ingevuld, communiceren

**Prompt:**
* ok ik heb nu de .env ingevult, kan jij er voor zorgen dat alles nu met elkaar kan gaan communiceren zodat de code vragen kan gaan genereren

**Antwoord (AI):**
* Stappen gegeven om server te starten en endpoint te testen

### 35. Gemini request failed

**Prompt:**
* {"error":"Gemini request failed"}

**Antwoord (AI):**
* Debug mode toegevoegd aan `generate-question.php`

### 36. Gemini quota exceeded

**Prompt:**
* 429 quota error (Gemini)

**Antwoord (AI):**
* Uitleg quota probleem + opties (upgrade, andere provider, fallback)

### 37. Over naar Groq

**Prompt:**
* ik zou dan groq willen proberen

**Antwoord (AI):**
* Groq integratie toegevoegd + config/.env/ai-setup aangepast

### 38. Live JSON voorbeeld

**Prompt:**
* {"id":"ai-live-...","question":"Waar woont het monster van Loch Ness?", ...}

**Antwoord (AI):**
* Bevestigd dat live flow werkt en extra opties voorgesteld

### 39. Categorie + bronnen + cache

**Prompt:**
* dat lijkt me alle 3 wel nice ja

**Antwoord (AI):**
* Categorie badge + bronnenlijst + cache toegevoegd

### 40. Herhaling van vragen

**Prompt:**
* ik merk dat dezelfde vragen de hele tijd opnieuw gesteld worden, is daar een reden voor?

**Antwoord (AI):**
* Uitleg oorzaken + opties om te verbeteren

### 41. Serp-query variëren

**Prompt:**
* de serp-query variëren is denk ik het handigst

**Antwoord (AI):**
* Query templates toegevoegd in `generate-question.php`

### 42. Random start/paging

**Prompt:**
* als dat kan, zeker

**Antwoord (AI):**
* Random `start` toegevoegd + env optie `SERP_START_MAX`

### 43. Herhaling na fout antwoord

**Prompt:**
* elke keer dat ik een vraag fout beantwoord krijg ik herhaalde vragen als volgende vraag

**Antwoord (AI):**
* Anti-repeat checks toegevoegd (client + server)

### 44. Rate-limit afhandeling

**Prompt:**
* zou je trouwens ook een errorafhandeling kunnen maken voor als 1 van de api keys hun rate limit bereikt?

**Antwoord (AI):**
* 429 handling toegevoegd in backend + UI melding

### 45. Fallback bij rate-limit

**Prompt:**
* ja hoor is goed

**Antwoord (AI):**
* Automatische fallback naar lokale vragen bij 429

### 46. Origin label

**Prompt:**
* is ook goed

**Antwoord (AI):**
* Origin label (Live/Cache/Lokaal/Fallback) toegevoegd in UI

### 47. Laatste 20 vragen blokkeren

**Prompt:**
* kan je het zo maken dat de laaste 20 vragen niet meer voorbij komen na dat ze beantwoord zijn

**Antwoord (AI):**
* `recentMax` naar 20, betere duplicate filtering

### 48. Te weinig variatie categorieën

**Prompt:**
* naar mijn gevoel worden sommige vragen nogsteeds te vaak herhaald. Ook is er te weinig variatie tussen categoriëen.

**Antwoord (AI):**
* Strengere anti-repeat + categorie-rotatie toegevoegd (client + server)

### 49. Alleen lokaal gegenereerde vragen

**Prompt:**
* ik krijg nu alleen lokaal gegenerdeerde vragen

**Antwoord (AI):**
* Debug instructie gegeven (`?debug=1`)

### 50. PHP error undefined variable

**Prompt:**
* PHP warning + fatal error over `$excludedCategories`

**Antwoord (AI):**
* Fout opgelost door variabelen/functies bovenaan te definiëren
