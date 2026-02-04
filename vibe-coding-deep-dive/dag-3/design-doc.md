## 1. Tech stack: Overzicht

**Samenvatting:**
De applicatie wordt een browser-based trivia game met een hybride frontendâ€“backend architectuur.

**Frontend:**
De frontend bestaat uit een eenvoudige webpagina met HTML en CSS voor de lay-out en styling. JavaScript in de browser verzorgt de interactie, het tonen van vragen, het verwerken van antwoorden, het bijhouden van levens en het dynamisch laden van nieuwe vragen via fetch-calls naar de backend.

**Backend:**
De backend wordt gebouwd in PHP. Een PHP-endpoint (bijvoorbeeld `get-question.php`) levert trivia-vragen in JSON-formaat aan de frontend. In de basisversie kan dit gebeuren op basis van lokaal gedefinieerde vragen in PHP of een JSON-bestand. In een uitgebreidere versie kan PHP een AI-API aanroepen om nieuwe vragen te genereren.

**AI-ondersteuning tijdens ontwikkeling:**
Tijdens de ontwikkeling maak ik gebruik van AI-tools als Codex, ChatGPT met geÃ¯ntegreerde Claude Sonnet 4.5 en eventueel Antigravity. Deze tools worden gebruikt voor hulp bij code schrijven, debuggen, genereren van voorbeeldvragen en het verbeteren van documentatie en structuur. Ze draaien niet als onderdeel van de runtime van de applicatie, maar zijn ontwikkelhulpmiddelen.

---

## 2. Globale architectuur: Stroom

**Beschrijving:**
De globale architectuur bestaat uit drie lagen: de gebruiker in de browser, de frontendlogica in JavaScript, en de PHP-backend die vragen aanlevert.

Een gebruiker opent de webpagina in de browser en ziet het spelvenster met een vraag, vier antwoordknoppen, een levensindicator en feedbacktekst. JavaScript initialiseert het spel, zet het aantal levens op drie en roept bij de start een fetch-call naar `get-question.php` aan om de eerste vraag op te halen.

De PHP-backend kiest een vraag. In de minimale versie gebeurt dit door een willekeurige vraag uit een verzameling vragen te kiezen. De vraag wordt teruggegeven als JSON-object met een vraagtekst, een array van vier antwoordopties en de index van het juiste antwoord. In een latere versie kan ditzelfde script een AI-API raadplegen om dynamisch vragen te genereren en het resultaat om te zetten naar hetzelfde JSON-formaat.

De frontend ontvangt het JSON-object, plaatst de vraag en de antwoordknoppen in de DOM en koppelt click-handlers aan elke knop. Wanneer een speler op een antwoord klikt, controleert JavaScript lokaal of de gekozen index overeenkomt met de juiste index uit de response. Bij een goed antwoord toont de frontend positieve feedback en vraagt een nieuwe vraag op bij de backend. Bij een fout antwoord verlaagt de frontend het aantal levens en werkt de levensweergave bij. Zodra het aantal levens nul is, toont de frontend een â€œGame Overâ€-scherm en stopt het met nieuwe vragen laden.

Persistentie is in de MVP niet nodig. Alle spelstatus (levens, huidige vraag, score indien toegevoegd) zit in het geheugen van de frontend zolang de pagina open staat. Er is geen login of database. Dit maakt de architectuur eenvoudiger en goed haalbaar binnen de beschikbare tijd.

---

## 3. Belangrijke keuzes: Motivatie

**Keuze 1: Hybride architectuur (PHP + JS):**
Een eerste belangrijke keuze is het gebruik van een hybride architectuur met PHP voor de backend en JavaScript voor de frontend. PHP is vertrouwd voor server-side logica en eenvoudig te hosten, terwijl JavaScript in de browser zorgt voor een vloeiende, interactieve spelervaring zonder paginavernieuwingen. Dit combineert de sterke punten van beide talen en blijft technisch haalbaar.

**Keuze 2: AI als ontwikkelhulp, niet live:**
Een tweede keuze is om AI in eerste instantie als ontwikkelhulp en contentgenerator te gebruiken in plaats van het direct in de live applicatie te integreren. Dat betekent dat ik AI gebruik om vooraf een set vragen te genereren die ik lokaal kan opslaan, of om code en structuur te verbeteren. Eventuele live AI-integratie via een API wordt gezien als een optionele uitbreiding en is niet noodzakelijk voor de MVP.

**Keuze 3: Scopebeperking van de game:**
Een derde keuze is de scopebeperking van de game. De MVP richt zich op Ã©Ã©n spelmodus: een singleplayer trivia game met drie levens en oneindig veel vragen. Er zijn geen gebruikersaccounts, geen categorieÃ«n en geen moeilijkheidsniveaus in de eerste versie. Dit houdt de complexiteit laag en maakt het haalbaar om binnen ongeveer anderhalve dag een werkende versie te bouwen.

**Keuze 4: Scheiding frontend en backend:**
Tot slot is er gekozen voor een duidelijke scheiding tussen spelstatus in de frontend en vraaggeneratie in de backend. De frontend is verantwoordelijk voor levens, feedback en spelverloop, terwijl de backend alleen vragen levert. Dit zorgt voor een heldere verantwoordelijkheid per laag en maakt het makkelijker om later de vraagbron (bijvoorbeeld van statische vragen naar AI-gegenereerde vragen) te vervangen zonder de frontendlogica te hoeven herschrijven.

---

## 4. Bekende risicoâ€™s: Overzicht

**Risico 1: Kwaliteit en betrouwbaarheid van AI-vragen:**
Een belangrijk risico is de kwaliteit en betrouwbaarheid van AI-gegenereerde vragen. Als ik AI gebruik om vragen te genereren, kan het gebeuren dat er feitelijke fouten of onduidelijke formuleringen in de vragen zitten. Dit kan de spelervaring minder betrouwbaar maken. Om dit te beperken kan ik starten met een handmatig gecontroleerde set vragen of een beperkte hoeveelheid AI-content gebruiken die ik eerst zelf check.

**Risico 2: Geschiktheid en veiligheid van de inhoud:**
Een tweede risico is de geschiktheid en veiligheid van de inhoud. Wanneer vragen op basis van willekeurige webinformatie worden gemaakt, bestaat de kans dat er ongepaste of gevoelige onderwerpen in de vragen terechtkomen. Dit is vooral een risico als ik ooit live scraping of web-input gebruik. Een mogelijke mitigatie is om alleen vertrouwde bronnen te gebruiken of de AI met duidelijke instructies te sturen en resultaten te filteren.

**Risico 3: Tijd en complexiteit van live AI-integratie:**
Een derde risico betreft de tijd en complexiteit van een eventuele live AI-integratie. Het koppelen van de PHP-backend aan een AI-API kost tijd voor configuratie, authenticatie, foutafhandeling en het omzetten van AI-output naar exact het juiste JSON-formaat. Binnen een beperkte bouwtijd kan dit ertoe leiden dat de AI-integratie niet stabiel genoeg wordt. Daarom is er het risico dat ik moet terugvallen op een eenvoudiger oplossing met statische of vooraf gegenereerde vragen. Dit is ook meteen de geplande fallback-strategie.

**Risico 4: Asynchrone communicatie tussen frontend en backend:**
Daarnaast is er een technisch risico rond asynchrone communicatie tussen frontend en backend. Fouten in de fetch-calls, CORS-configuratie, JSON-parsing of error-afhandeling kunnen ervoor zorgen dat het spel geen nieuwe vragen meer kan ophalen. Dit kan worden verminderd door goede foutafhandeling toe te voegen, bijvoorbeeld door duidelijke foutmeldingen in de console en een fallbackboodschap in de UI wanneer het ophalen van een vraag mislukt.

**Risico 5: Performance en laadtijd bij live AI:**
Een ander risico is performance en laadtijd als er ooit live AI-vraaggeneratie wordt gebruikt. Het genereren van een vraag via een AI-API kan enkele seconden duren. Dit zou het spel traag en minder vloeiend kunnen maken. Een mogelijke oplossing is om op de achtergrond alvast een volgende vraag te genereren terwijl de speler nog met de huidige vraag bezig is, of om een kleine buffer van vragen aan te houden.

**Risico 6: Lekken van API-sleutels:**
Tot slot is er het risico dat API-sleutels of gevoelige configuratie per ongeluk uitlekken als die in frontend-code worden geplaatst. Daarom is het belangrijk dat eventuele AI-API-calls uitsluitend in de PHP-backend plaatsvinden en dat sleutels in server-side configuratiebestanden blijven die niet naar de browser worden gestuurd.
