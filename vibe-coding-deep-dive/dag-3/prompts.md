# prompts.md

## Prompt 1 – Hulp bij projectidee

**Gebruikte prompt:**

> Ik moet een project maken met de hulp van AI/AI-IDE, Wat zijn vrij korte projecten (anderhalve dag) die mogelijk zijn voor een mbo-4 softwarestudent?

**Antwoord:**
Overzicht met korte haalbare projecten zoals chatbot met sentimentanalyse, image classifier, handschriftherkenning (MNIST), tekstgenerator, to-do lijst met AI-prioritering, social media post-ideeën generator, enz. AI werd hierbij gebruikt als ontwikkelhulp of voor de core AI-logica.

---

## Prompt 2 – Verduidelijking projectgebruik AI

**Gebruikte prompt:**

> Ik bedoelde eigenlijk iets waarbij ik AI kan gebruiken als hulpmiddel, niet zozeer het integreren van AI

**Antwoord:**
Uitleg dat AI hier vooral als ontwikkelondersteuning gebruikt wordt (ChatGPT, Copilot), met projectideeën zoals: CRUD-apps, mini-games, quiztools, budget-trackers, Markdown converter, enz. waarin AI helpt bij debugging, styling, structuur en MVP-bouw.

---

## Prompt 3 – Idee gekozen: AI trivia game

**Gebruikte prompt:**

> het idee van project 7 lijkt me leuk, ik zit dan te denken aan een browser based trivia game die AI gebruikt om op basis van compleet random informatie op google, vragen te maken met 4 antwoorden. Je heb dan 3 levens en de ai blijft oneindig lang nieuwe trivia genereren op basis van wat er op het web te vinden is

**Antwoord:**
Je idee werd uitgewerkt tot een oneindige AI trivia-game, browser-based, met uitleg over hoe AI trivia genereert (via zoekopdrachten, Wikipedia), wat het spel doet (3 levens, eindeloos doorspelen), en hoe je dit kunt bouwen.

---

## Prompt 4 – Dag 3 opdracht

**Gebruikte prompt:**

> # Dag 3 – Projectcontext, User Stories & Design Docs [...]

**Antwoord:**
Documentinhoud voor `project.md` en `prompts.md` werd aangemaakt, met beschrijving van je project (Infinite Trivia AI), doelgroep, scope, en de AI-prompts die je tot dan toe hebt gebruikt (vraaggeneratie, gamelogica, CSS-layout, projectstructuur).

---

## Prompt 5 – Diagrammen laten maken met Mermaid

**Gebruikte prompt:**

> ## **Opdracht 4 – Diagrammen herhalen (± 45 minuten)**  
>  
> ### Opdracht  
>  
> Maak of verbeter diagrammen met Mermaid.  
>  
> ### Eisen  
>  
> - minimaal **2 diagrammen**:  
>     - applicatie-overzicht  
>     - datastroom / API-flow  

**Antwoord (samenvatting):**

De AI heeft een nieuw bestand `diagrammen.md` voorgesteld met daarin twee Mermaid-diagrammen:

- Een **applicatie-overzicht** als `flowchart` met een scheiding tussen Client (browser + frontend JS) en Server (PHP-backend met `get-question.php`).  
- Een **datastroom / API-flow** als `sequenceDiagram` met de deelnemers: Speler, Frontend (JS), PHP-backend en (optioneel) een AI-service. Het diagram beschrijft het ophalen van een vraag, de (optionele) AI-call, het terugsturen van JSON en het lokaal verwerken van antwoorden, levens en feedback in de frontend.

---