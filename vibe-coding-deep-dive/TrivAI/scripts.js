const questionEl = document.getElementById("question");
const answersEl = document.getElementById("answers");
const feedbackEl = document.getElementById("feedback");
const livesEl = document.getElementById("lives");
const scoreEl = document.getElementById("score");
const gameOverEl = document.getElementById("game-over");
const retryBtn = document.getElementById("retry");
const reportBtn = document.getElementById("report");
const reportStatusEl = document.getElementById("report-status");
const categoryEl = document.getElementById("category");
const originEl = document.getElementById("origin");
const sourcesEl = document.getElementById("sources");
const sourcesListEl = document.getElementById("sources-list");

const useLiveQuestions = true;
const liveQuestionEndpoint = "generate-question.php";

const startingLives = 3;
let lives = startingLives;
let score = 0;
let lastCategory = null;
let lastQuestionIndex = null;
let currentQuestion = null;
let isLoading = false;
const answeredQuestions = new Set();
const answeredQueue = [];
const answeredQueueMax = 20;
const recentCategories = [];
const recentCategoryMax = 2;
let prefetchedQuestion = null;
let prefetchPromise = null;

const questions = [
  {
    id: "ai-001",
    category: "Aardrijkskunde",
    question: "Wat is de hoofdstad van Itali\u00eb?",
    answers: ["Rome", "Milaan", "Florence", "Napels"],
    correctIndex: 0,
  },
  {
    id: "ai-002",
    category: "Wetenschap",
    question: "Welke planeet staat het dichtst bij de zon?",
    answers: ["Mercurius", "Venus", "Mars", "Jupiter"],
    correctIndex: 0,
  },
  {
    id: "ai-003",
    category: "Aardrijkskunde",
    question: "Wat is de grootste oceaan op aarde?",
    answers: [
      "Atlantische Oceaan",
      "Indische Oceaan",
      "Stille Oceaan",
      "Noordelijke IJszee",
    ],
    correctIndex: 2,
  },
  {
    id: "ai-004",
    category: "Kunst",
    question: "Wie schilderde de Mona Lisa?",
    answers: ["Vincent van Gogh", "Leonardo da Vinci", "Pablo Picasso", "Rembrandt"],
    correctIndex: 1,
  },
  {
    id: "ai-005",
    category: "Geschiedenis",
    question: "In welk jaar begon de Tweede Wereldoorlog?",
    answers: ["1939", "1941", "1929", "1918"],
    correctIndex: 0,
  },
  {
    id: "ai-006",
    category: "Sport",
    question: "Welke sport gebruikt een shuttle?",
    answers: ["Tennis", "Badminton", "Squash", "Honkbal"],
    correctIndex: 1,
  },
  {
    id: "ai-007",
    category: "Muziek",
    question: "Welk instrument heeft zwarte en witte toetsen?",
    answers: ["Piano", "Viool", "Trompet", "Fluit"],
    correctIndex: 0,
  },
  {
    id: "ai-008",
    category: "Technologie",
    question: "Waar staat HTML voor?",
    answers: [
      "HyperText Markup Language",
      "High Transfer Media Link",
      "Hyperlink and Text Module",
      "Home Tool Markup Layout",
    ],
    correctIndex: 0,
  },
  {
    id: "ai-009",
    category: "Natuur",
    question: "Welk gas komt het meest voor in de atmosfeer?",
    answers: ["Stikstof", "Zuurstof", "Koolstofdioxide", "Argon"],
    correctIndex: 0,
  },
  {
    id: "ai-010",
    category: "Literatuur",
    question: "Wie schreef de Harry Potter boeken?",
    answers: ["J.K. Rowling", "Stephen King", "Roald Dahl", "J.R.R. Tolkien"],
    correctIndex: 0,
  },
  {
    id: "ai-011",
    category: "Taal",
    question: "Wat is een synoniem van snel?",
    answers: ["Vlug", "Traag", "Stil", "Luid"],
    correctIndex: 0,
  },
  {
    id: "ai-012",
    category: "Eten",
    question: "Waarvan wordt kaas voornamelijk gemaakt?",
    answers: ["Melk", "Graan", "Fruit", "Vlees"],
    correctIndex: 0,
  },
  {
    id: "ai-013",
    category: "Gezondheid",
    question: "Hoeveel tanden heeft een volwassene normaal?",
    answers: ["32", "24", "28", "36"],
    correctIndex: 0,
  },
  {
    id: "ai-014",
    category: "Sport",
    question: "Hoeveel spelers staan er tegelijk in een voetbalteam op het veld?",
    answers: ["11", "9", "10", "12"],
    correctIndex: 0,
  },
  {
    id: "ai-015",
    category: "Film",
    question: "Wie regisseerde de film Titanic?",
    answers: ["James Cameron", "Steven Spielberg", "Christopher Nolan", "Peter Jackson"],
    correctIndex: 0,
  },
  {
    id: "ai-016",
    category: "Natuur",
    question: "Welk dier is een zoogdier?",
    answers: ["Dolfijn", "Haai", "Kikker", "Schildpad"],
    correctIndex: 0,
  },
  {
    id: "ai-017",
    category: "Ruimte",
    question: "Hoe heet ons sterrenstelsel?",
    answers: ["Melkweg", "Andromeda", "Sombrero", "Orion"],
    correctIndex: 0,
  },
  {
    id: "ai-018",
    category: "Geschiedenis",
    question: "In welk jaar viel de Berlijnse Muur?",
    answers: ["1989", "1975", "1999", "1961"],
    correctIndex: 0,
  },
  {
    id: "ai-019",
    category: "Technologie",
    question: "Wat is de functie van RAM in een computer?",
    answers: ["Tijdelijke opslag", "Langzame opslag", "Grafische output", "Netwerkverbinding"],
    correctIndex: 0,
  },
  {
    id: "ai-020",
    category: "Cultuur",
    question: "Welke kleur ontstaat door blauw en geel te mengen?",
    answers: ["Groen", "Paars", "Oranje", "Bruin"],
    correctIndex: 0,
  },
];

function setLoadingState() {
  questionEl.textContent = "Vraag wordt geladen...";
  answersEl.innerHTML = "";
  feedbackEl.textContent = "";
  feedbackEl.classList.remove("feedback--good", "feedback--bad");
  reportStatusEl.textContent = "";
  reportBtn.disabled = true;
  categoryEl.textContent = "";
  categoryEl.hidden = true;
  originEl.textContent = "";
  originEl.hidden = true;
  sourcesListEl.innerHTML = "";
  sourcesEl.hidden = true;
}

async function loadNextQuestion() {
  if (isLoading || lives === 0) {
    return;
  }

  isLoading = true;

  try {
    if (prefetchPromise) {
      await prefetchPromise;
    }

    let nextQuestion = null;
    if (prefetchedQuestion && isQuestionUsable(prefetchedQuestion)) {
      nextQuestion = prefetchedQuestion;
      prefetchedQuestion = null;
    } else {
      setLoadingState();
      nextQuestion = await fetchUniqueQuestion(8);
    }

    if (!nextQuestion) {
      questionEl.textContent = "Geen nieuwe vragen beschikbaar.";
      return;
    }

    renderQuestion(nextQuestion);
  } catch (error) {
    questionEl.textContent =
      error && error.message ? error.message : "Vraag laden mislukt.";
  } finally {
    isLoading = false;
  }
}

function renderQuestion(data) {
  currentQuestion = data;
  questionEl.textContent = data.question;
  answersEl.innerHTML = "";
  feedbackEl.textContent = "";
  feedbackEl.classList.remove("feedback--good", "feedback--bad");
  reportStatusEl.textContent = "";
  reportBtn.disabled = false;
  if (data.category) {
    categoryEl.textContent = data.category;
    categoryEl.hidden = false;
  } else {
    categoryEl.textContent = "";
    categoryEl.hidden = true;
  }
  const originLabel = data.origin === "local-fallback"
    ? "Fallback"
    : data.origin === "local"
      ? "Lokaal"
      : data.origin === "cache"
        ? "Cache"
        : data.origin === "live"
          ? "Live"
          : "";
  if (originLabel) {
    originEl.textContent = originLabel;
    originEl.hidden = false;
  } else {
    originEl.textContent = "";
    originEl.hidden = true;
  }
  hideGameOver();

  sourcesListEl.innerHTML = "";
  if (Array.isArray(data.sources) && data.sources.length > 0) {
    data.sources.forEach((source) => {
      const li = document.createElement("li");
      const label = source.title || source.url || "Bron";
      if (source.url) {
        const link = document.createElement("a");
        link.className = "sources__link";
        link.href = source.url;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = label;
        li.appendChild(link);
      } else {
        const span = document.createElement("span");
        span.className = "sources__link";
        span.textContent = label;
        li.appendChild(span);
      }
      sourcesListEl.appendChild(li);
    });
    sourcesEl.hidden = false;
  } else {
    sourcesEl.hidden = true;
  }

  data.answers.forEach((answerText, index) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "answer";
    btn.textContent = `${index + 1}. ${answerText}`;
    btn.addEventListener("click", () => {
      const isCorrect = index === data.correctIndex;
      feedbackEl.textContent = isCorrect
        ? "Goed antwoord!"
        : "Fout antwoord.";
      feedbackEl.classList.toggle("feedback--good", isCorrect);
      feedbackEl.classList.toggle("feedback--bad", !isCorrect);

      if (!isCorrect) {
        lives = Math.max(0, lives - 1);
        renderLives();

        if (lives === 0) {
          showGameOver();
        }
      } else {
        score += 1;
        renderScore();
      }

      if (lives > 0) {
        setTimeout(() => {
          if (lives === 0) {
            return;
          }
          loadNextQuestion();
        }, 1200);
      }

      markAnsweredQuestion(data.question, data.category);
      btn.classList.add(isCorrect ? "answer--good" : "answer--bad");
      answersEl.querySelectorAll("button").forEach((button) => {
        button.disabled = true;
      });
    });
    answersEl.appendChild(btn);
  });

  prefetchNextQuestion();
}

async function fetchUniqueQuestion(maxAttempts) {
  let attempts = 0;
  while (attempts < maxAttempts) {
    const candidate = await getNextQuestion();
    if (!candidate || !candidate.question) {
      attempts += 1;
      continue;
    }
    if (isQuestionUsable(candidate)) {
      return candidate;
    }
    attempts += 1;
  }
  return null;
}

function isQuestionUsable(candidate) {
  return (
    candidate &&
    candidate.question &&
    !isDuplicateQuestion(candidate.question) &&
    !isDuplicateCategory(candidate.category)
  );
}

function prefetchNextQuestion() {
  if (prefetchPromise || lives === 0) {
    return;
  }

  prefetchPromise = (async () => {
    try {
      const candidate = await fetchUniqueQuestion(6);
      if (candidate && isQuestionUsable(candidate)) {
        prefetchedQuestion = candidate;
      }
    } catch (error) {
      // ignore prefetch errors
    } finally {
      prefetchPromise = null;
    }
  })();
}

async function getNextQuestion() {
  if (useLiveQuestions) {
    try {
      const excludeId = currentQuestion ? currentQuestion.id : "";
      const excludeQuestion = currentQuestion ? currentQuestion.question : "";
      const excludeCategories = getExcludeCategories();
      const excludeQuestions = getExcludeQuestions();
      const liveQuestion = await fetchLiveQuestion(
        lastCategory,
        excludeId,
        excludeQuestion,
        excludeCategories,
        excludeQuestions
      );
      lastCategory = liveQuestion.category || lastCategory;
      lastQuestionIndex = null;
      return liveQuestion;
    } catch (error) {
      if (error && error.message && error.message.includes("API limiet")) {
        const fallback = getLocalQuestion();
        fallback.origin = "local-fallback";
        return fallback;
      }
      return getLocalQuestion();
    }
  }

  return getLocalQuestion();
}

function getLocalQuestion() {
  const available = questions.filter(
    (q, index) =>
      q.category !== lastCategory && (lastQuestionIndex === null || index !== lastQuestionIndex)
  );

  let pool = available.filter(
    (q) => !isDuplicateQuestion(q.question) && !isDuplicateCategory(q.category)
  );
  if (pool.length === 0) {
    pool = questions.filter(
      (q) => !isDuplicateQuestion(q.question) && !isDuplicateCategory(q.category)
    );
  }
  if (pool.length === 0) {
    return null;
  }

  const picked = pool[Math.floor(Math.random() * pool.length)];

  lastCategory = picked.category;
  lastQuestionIndex = questions.indexOf(picked);
  return { ...picked, origin: "local" };
}

async function fetchLiveQuestion(
  excludeCategory,
  excludeId,
  excludeQuestion,
  excludeCategories,
  excludeQuestions
) {
  const url = new URL(liveQuestionEndpoint, window.location.href);
  if (excludeCategory) {
    url.searchParams.set("excludeCategory", excludeCategory);
  }
  if (excludeId) {
    url.searchParams.set("excludeId", excludeId);
  }
  if (excludeQuestion) {
    url.searchParams.set("excludeQuestion", excludeQuestion);
  }
  if (excludeCategories.length > 0) {
    url.searchParams.set("excludeCategories", excludeCategories.join(","));
  }
  if (excludeQuestions.length > 0) {
    url.searchParams.set("excludeQuestions", excludeQuestions.join("|"));
  }

  const response = await fetch(url.toString(), {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    if (response.status === 429) {
      throw new Error("API limiet bereikt. Wacht even en probeer opnieuw.");
    }
    throw new Error("Live vraag ophalen mislukt.");
  }

  const data = await response.json();
  if (
    !data ||
    !Array.isArray(data.answers) ||
    data.answers.length !== 4 ||
    !Number.isInteger(data.correctIndex) ||
    data.correctIndex < 0 ||
    data.correctIndex > 3
  ) {
    throw new Error("Invalid question payload");
  }

  return { ...data, origin: "live" };
}

function normalizeQuestion(text) {
  return text.trim().toLowerCase();
}

function isDuplicateQuestion(text) {
  const normalized = normalizeQuestion(text);
  return answeredQuestions.has(normalized);
}

function markAnsweredQuestion(text, category) {
  const normalized = normalizeQuestion(text);
  answeredQuestions.add(normalized);
  answeredQueue.push(text);
  if (answeredQueue.length > answeredQueueMax) {
    answeredQueue.splice(0, answeredQueue.length - answeredQueueMax);
  }

  if (category) {
    recentCategories.push(category);
    if (recentCategories.length > recentCategoryMax) {
      recentCategories.splice(0, recentCategories.length - recentCategoryMax);
    }
  }
}

function isDuplicateCategory(category) {
  if (!category) {
    return false;
  }
  return recentCategories.includes(category);
}

function getExcludeCategories() {
  const unique = [];
  recentCategories.forEach((category) => {
    if (category && !unique.includes(category)) {
      unique.push(category);
    }
  });
  return unique;
}

function getExcludeQuestions() {
  return answeredQueue.filter(Boolean);
}

function renderLives() {
  livesEl.textContent = lives;
}

function renderScore() {
  scoreEl.textContent = score;
}

function showGameOver() {
  if (lives === 0) {
    gameOverEl.hidden = false;
  }
}

function hideGameOver() {
  if (lives > 0) {
    gameOverEl.hidden = true;
  }
}

async function reportQuestion() {
  if (!currentQuestion) {
    return;
  }

  reportBtn.disabled = true;
  reportStatusEl.textContent = "Rapport wordt opgeslagen...";

  try {
    const response = await fetch("report.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: currentQuestion.id,
        category: currentQuestion.category,
        question: currentQuestion.question,
        answers: currentQuestion.answers,
        correctIndex: currentQuestion.correctIndex,
        sources: currentQuestion.sources || [],
        origin: currentQuestion.origin || "local",
        reportedAt: new Date().toISOString(),
      }),
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(result.error || "Report failed");
    }

    reportStatusEl.textContent =
      result.message || "Bedankt, je rapport is opgeslagen.";
  } catch (error) {
    reportStatusEl.textContent = "Rapporteren mislukt. Probeer opnieuw.";
  } finally {
    reportBtn.disabled = false;
  }
}

retryBtn.addEventListener("click", () => {
  lives = startingLives;
  renderLives();
  score = 0;
  renderScore();
  lastCategory = null;
  lastQuestionIndex = null;
  answeredQuestions.clear();
  answeredQueue.length = 0;
  recentCategories.length = 0;
  prefetchedQuestion = null;
  prefetchPromise = null;
  loadNextQuestion();
});

reportBtn.addEventListener("click", reportQuestion);

renderLives();
renderScore();
loadNextQuestion();
