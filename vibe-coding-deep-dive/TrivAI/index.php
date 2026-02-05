<!doctype html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TrivAI - Trivia</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <main class="app">
      <header class="logo" aria-label="TrivAI">
        <span class="logo-letter blue">T</span>
        <span class="logo-letter red">r</span>
        <span class="logo-letter yellow">i</span>
        <span class="logo-letter blue">v</span>
        <span class="logo-letter green">A</span>
        <span class="logo-letter red">I</span>
      </header>
      <p class="subtitle">Kies het juiste antwoord</p>

      <div class="status-bar" aria-live="polite">
        <div class="status">
          <span class="status-label">Levens</span>
          <span id="lives" class="lives">3</span>
        </div>
        <div class="status">
          <span class="status-label">Score</span>
          <span id="score" class="score">0</span>
        </div>
      </div>

      <section class="card search-card" aria-labelledby="vraag-titel">
        <div class="meta">
          <p id="vraag-titel" class="eyebrow">Trivia-vraag</p>
          <div class="meta-tags">
            <span id="category" class="category" aria-live="polite"></span>
            <span id="origin" class="origin" aria-live="polite"></span>
          </div>
        </div>

        <div class="search-bar">
          <span class="search-icon" aria-hidden="true"></span>
          <h1 id="question" class="question">Vraag wordt geladen...</h1>
        </div>

        <div id="answers" class="answers" role="group" aria-label="Antwoordopties">
          <!-- Antwoordknoppen komen hier via JS -->
        </div>

        <p id="feedback" class="feedback" aria-live="polite"></p>

        <div class="actions">
          <button id="report" class="report" type="button">AI vraag rapporteren</button>
          <a class="manage" href="beheer.php">Beheer</a>
          <p id="report-status" class="report-status" aria-live="polite"></p>
        </div>

        <div id="sources" class="sources" hidden>
          <p class="sources__title">Bronnen</p>
          <ul id="sources-list" class="sources__list"></ul>
        </div>

        <div id="game-over" class="game-over" aria-live="polite" hidden>
          <p class="game-over__title">Game Over</p>
          <p class="game-over__text">Je hebt geen levens meer.</p>
          <button id="retry" class="retry" type="button">Opnieuw proberen</button>
        </div>
      </section>
    </main>

    <script src="scripts.js"></script>
  </body>
</html>
