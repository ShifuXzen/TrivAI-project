## User Stories – Infinite Trivia AI

### 1. Als speler wil ik een trivia-vraag met vier antwoordopties zien zodat ik mijn kennis kan testen.

**Prioriteit:** must
**Acceptatiecriteria:**

* De vraag bevat exact vier keuzemogelijkheden
* De vraagtekst en antwoorden worden duidelijk weergegeven op het scherm

### 2. Als speler wil ik direct feedback krijgen op mijn antwoord zodat ik weet of ik goed of fout heb geantwoord.

**Prioriteit:** must
**Acceptatiecriteria:**

* Na het klikken op een antwoord verschijnt er direct feedback
* Feedback geeft aan of het gekozen antwoord correct of fout is

### 3. Als speler wil ik mijn resterende levens kunnen zien zodat ik weet hoeveel fouten ik nog mag maken.

**Prioriteit:** must
**Acceptatiecriteria:**

* Het aantal resterende levens is altijd zichtbaar tijdens het spel
* Bij een fout antwoord wordt het aantal levens verminderd

### 4. Als speler wil ik dat het spel automatisch doorgaat naar de volgende vraag zodat ik zonder onderbreking kan blijven spelen.

**Prioriteit:** must
**Acceptatiecriteria:**

* Bij een goed antwoord wordt automatisch een nieuwe vraag geladen
* Er is een korte vertraging (max 2 seconden) tussen vragen

### 5. Als speler wil ik dat het spel stopt als mijn levens op zijn zodat ik weet wanneer ik verloren heb.

**Prioriteit:** must
**Acceptatiecriteria:**

* Bij 0 levens wordt het spel beëindigd
* Er verschijnt een "Game Over" scherm of boodschap

### 6. Als speler wil ik vragen blijven ontvangen zonder pagina-verversing zodat het spel soepel en snel aanvoelt.

**Prioriteit:** must
**Acceptatiecriteria:**

* Nieuwe vragen worden geladen via JavaScript zonder de pagina te herladen
* De gebruiker blijft op dezelfde pagina gedurende het spel

### 7. Als speler wil ik dat de vragen afwisselend zijn qua onderwerp zodat het spel gevarieerd blijft.

**Prioriteit:** nice
**Acceptatiecriteria:**

* Vragen worden willekeurig geselecteerd uit verschillende categorieën
* Geen twee opeenvolgende vragen gaan over exact hetzelfde onderwerp

### 8. Als ontwikkelaar wil ik AI gebruiken om vragen te genereren zodat ik snel veel unieke content heb voor het spel.

**Prioriteit:** nice
**Acceptatiecriteria:**

* De AI kan minimaal 20 unieke vragen aanleveren
* Elke vraag bevat een correct antwoord en drie foute opties
