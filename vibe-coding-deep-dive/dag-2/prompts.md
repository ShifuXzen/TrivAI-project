# 📚 Prompts voor Mermaid-diagrammen (Bibliotheek App)

Hieronder vind je prompts die je kunt gebruiken om Mermaid-diagrammen te genereren voor een bibliotheeksysteem.

---

## 1. ERD (Entity Relationship Diagram)

**Prompt:**
> Maak een volledig ERD voor een bibliotheek waarin boeken, auteurs, leden, leningen, reserveringen, boetes, medewerkers en accounts gemodelleerd zijn.

---

## 2. Sequence Diagram (REST API)

**Prompt:**
> Genereer een sequence diagram voor een REST API van een bibliotheek waarin een gebruiker boeken kan opzoeken, lenen, reserveren, boetes opvragen en inloggen.

---

## 3. Use-case Diagram (via flowchart workaround)

**Prompt:**
> Maak een use-case diagram (via flowchart) waarin gebruikers en bibliotheekmedewerkers verschillende acties kunnen uitvoeren zoals boeken zoeken, lenen, reserveren en administratie beheren.

---

## 4. Flowchart – Gebruikersproces

**Prompt:**
> Genereer een flowchart voor een gebruiker van een bibliotheek die inlogt, een boek zoekt, controleert of het beschikbaar is, het leent of reserveert.

---

## 5. Gantt Chart (Projectplanning)

**Prompt:**
> Maak een Gantt-diagram voor de ontwikkeling van een bibliotheek-app, met fasen zoals analyse, ontwerp, ontwikkeling, testen en uitrol.

---

## 6. Mindmap – Bibliotheekfunctionaliteiten

**Prompt:**
> Maak een mindmap met de hoofdfunctionaliteiten van een bibliotheek-app zoals boekenbeheer, ledenbeheer, reserveringen, boetes, rapportage en authenticatie.

---

## 7. State Diagram – Boekstatus

**Prompt:**
> Maak een state diagram waarin de verschillende toestanden van een boek getoond worden: beschikbaar, uitgeleend, gereserveerd, verloren, verwijderd.

---

## 8. Kanban Board – Bibliotheekontwikkeling

**Prompt:**
> Maak een kanban board met kolommen voor taken, bezig en afgerond, en voeg taken toe zoals 'Login toevoegen', 'Zoekfunctionaliteit', 'Database opzetten', enz.

---

# Samenvatting API Ontwerp Discussie

Dit document bevat een samenvatting van de vragen en antwoorden rondom het ontwerp van de Bibliotheek REST API diagrammen in `diagrammen.md`.

## 1. Wat doet de toegevoegde Mermaid code?

De toegevoegde code is **Mermaid-syntax** voor een **flowchart/graaf diagram** (`graph LR`). Het visualiseert de structuur van de REST API endpoints als een kaart.

### Detail uitleg:
*   **`graph LR`**: Definieert een grafiek van Links naar Rechts.
*   **Knopen (Nodes)**: Blokjes zoals `API[Bibliotheek API]` (root) en `Cat[Catalogus]` (sub-sectie).
*   **Verbindingen**: Pijlen zoals `API -->|/catalogus| Cat` die de URL-paden representeren.
*   **Styling**: Kleurcodes (bijv. `style API fill:#f96`) om onderscheid te maken tussen API, resources en endpoints.

---

## 2. Hoe werken de belangrijkste functies?

De API is opgedeeld in drie logische bronnen:

### A. De Catalogus (`/catalogus`)
*   **`GET /boeken`**: Haalt lijst met boeken op (lezen).
*   **`GET /boeken/:id`**: Haalt details van één boek op.
*   **`POST /boeken`**: Voegt een nieuw boek toe aan de database (schrijven).

### B. Transacties (`/transacties`)
*   **`POST /lenen`**: Start een uitleenproces (checkt beschikbaarheid -> update status -> maakt lening aan).
*   **`POST /reserveren`**: Zet een gebruiker op de wachtlijst voor een uitgeleend boek.

### C. Account (`/account`)
*   **`GET /profiel` & `/historie`**: Haalt persoonlijke data op van de ingelogde gebruiker (afgeschermd).

---

## 3. Kritische Analyse: Fouten & Verbeterpunten

Het huidige ontwerp volgt een **RPC (Remote Procedure Call)** stijl en wijkt op punten af van strikte **REST** principes.

### Belangrijkste punten:
1.  **Werkwoorden in URL's (Fout)**:
    *   *Huidig:* `POST /lenen`, `POST /retourneren` (actie-gericht).
    *   *Correctie:* Gebruik zelfstandige naamwoorden. Bijvoorbeeld `POST /uitleningen` (maak object aan) en `PATCH /uitleningen/{id}` (wijzig status).
2.  **Nesting**:
    *   *Huidig:* `/catalogus/boeken`.
    *   *Verbetering:* `/boeken` direct aan de root is schoner.
3.  **Ontbrekende CRUD**:
    *   Er ontbreken `PUT` (updaten) en `DELETE` (verwijderen) endpoints voor volledig beheer.
4.  **Context**:
    *   De `/account` endpoints zijn handig voor gebruikers, maar voor beheerders zijn endpoints zoals `/leden/{id}/boetes` noodzakelijk.



