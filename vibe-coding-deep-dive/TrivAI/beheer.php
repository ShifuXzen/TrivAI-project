<?php

set_time_limit(30);

session_start();

require_once __DIR__ . DIRECTORY_SEPARATOR . "db.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "ai_helpers.php";
$config = require __DIR__ . DIRECTORY_SEPARATOR . "config.php";

function h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function decodeJsonArray(?string $value): array
{
  if ($value === null || $value === "") {
    return [];
  }
  $decoded = json_decode($value, true);
  return is_array($decoded) ? $decoded : [];
}

function buildReworkQuery(array $report): string
{
  $category = $report["category"] ?? "";
  $question = $report["question_text"] ?? "";
  $categoryMap = [
    "Aardrijkskunde" => "aardrijkskunde feiten",
    "Geschiedenis" => "geschiedenis feiten",
    "Wetenschap" => "wetenschap feiten",
    "Sport" => "sport feiten",
    "Kunst" => "kunst feiten",
    "Cultuur" => "cultuur feiten",
    "Muziek" => "muziek feiten",
    "Technologie" => "technologie feiten",
    "Natuur" => "natuur feiten",
    "Literatuur" => "literatuur feiten",
  ];

  $base = $categoryMap[$category] ?? $question;
  if ($base === "") {
    $base = "algemene kennis feiten";
  }

  $templates = [
    "{base} trivia",
    "{base} weetjes",
    "{base} uitleg",
    "feiten over {base}",
    "{category} feiten",
    "quizvragen {base}",
  ];
  $template = $templates[array_rand($templates)];

  return str_replace(
    ["{base}", "{category}"],
    [$base, $category],
    $template
  );
}

function callGroqRework(array $config, array $report, array $sources): array
{
  $category = $report["category"] ?? "Algemeen";
  $questionText = $report["question_text"] ?? "";
  $answers = decodeJsonArray($report["answers"] ?? "");
  $correctIndex = is_numeric($report["correct_index"] ?? null) ? (int) $report["correct_index"] : null;

  $contextLines = array_map(function ($source) {
    $parts = [];
    if (!empty($source["title"])) {
      $parts[] = $source["title"];
    }
    if (!empty($source["snippet"])) {
      $parts[] = $source["snippet"];
    }
    if (!empty($source["url"])) {
      $parts[] = $source["url"];
    }
    return "- " . implode(" - ", $parts);
  }, $sources);

  $systemPrompt = "You correct faulty trivia questions. Output ONLY valid JSON.";
  $userPrompt = "De oorspronkelijke vraag was mogelijk fout. Maak een nieuwe, correcte trivia-vraag in het Nederlands.\n"
    . "Gebruik ALLEEN de bronnen hieronder.\n"
    . "Regels:\n"
    . "- Exact 4 antwoordopties\n"
    . "- Exact 1 correct antwoord\n"
    . "- correctIndex is 0-3\n"
    . "- category: {$category}\n"
    . "Geef JSON met velden: question, answers, correctIndex, category.\n\n"
    . "Oorspronkelijke vraag:\n{$questionText}\n";

  if (count($answers) > 0) {
    $userPrompt .= "Oorspronkelijke antwoorden:\n- " . implode("\n- ", $answers) . "\n";
  }
  if ($correctIndex !== null) {
    $userPrompt .= "Oorspronkelijke correctIndex: {$correctIndex}\n";
  }
  $userPrompt .= "\nBronnen:\n" . implode("\n", $contextLines);
  $questionData = ai_groq_generate_json(
    $config,
    $systemPrompt,
    $userPrompt,
    0.5,
    450
  );

  $question = $questionData["question"] ?? "";
  $answersOut = $questionData["answers"] ?? [];
  $correctOut = $questionData["correctIndex"] ?? null;
  $categoryOut = $questionData["category"] ?? $category;

  if (
    $question === "" ||
    !is_array($answersOut) ||
    count($answersOut) !== 4 ||
    !is_int($correctOut) ||
    $correctOut < 0 ||
    $correctOut > 3
  ) {
    throw new RuntimeException("LLM gaf een ongeldige vraagstructuur.");
  }

  return [
    "question" => $question,
    "answers" => array_values($answersOut),
    "correctIndex" => $correctOut,
    "category" => $categoryOut,
  ];
}

$adminUser = getenv("ADMIN_USER") ?: "admin";
$adminPassword = getenv("ADMIN_PASSWORD") ?: "";
$adminPasswordHash = getenv("ADMIN_PASSWORD_HASH") ?: "";
$loginError = "";

if (isset($_GET["logout"])) {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
  header("Location: beheer.php");
  exit;
}

if (!($_SESSION["admin_authenticated"] ?? false)) {
  if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "login") {
    $username = trim($_POST["username"] ?? "");
    $password = (string) ($_POST["password"] ?? "");
    $validUser = $adminUser === "" || hash_equals($adminUser, $username);
    $validPass = false;
    if ($adminPasswordHash !== "") {
      $validPass = password_verify($password, $adminPasswordHash);
    } elseif ($adminPassword !== "") {
      $validPass = hash_equals($adminPassword, $password);
    }

    if ($validUser && $validPass) {
      $_SESSION["admin_authenticated"] = true;
      header("Location: beheer.php");
      exit;
    }

    $loginError = "Onjuiste inloggegevens.";
  } elseif ($adminPassword === "" && $adminPasswordHash === "") {
    $loginError = "ADMIN_PASSWORD ontbreekt in .env.";
  }

  ?>
  <!doctype html>
  <html lang="nl">
    <head>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>TrivAI - Beheer login</title>
      <style>
        :root {
          color-scheme: light;
          font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
          margin: 0;
          background: #f5f7fb;
          color: #1f2937;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
          padding: 20px;
        }
        .card {
          background: #fff;
          padding: 28px;
          border-radius: 16px;
          box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
          width: min(420px, 100%);
        }
        h1 {
          margin: 0 0 8px;
          font-size: 24px;
        }
        p {
          margin: 0 0 16px;
          color: #6b7280;
        }
        label {
          display: block;
          font-weight: 600;
          margin-bottom: 6px;
        }
        input {
          width: 100%;
          padding: 10px 12px;
          border-radius: 10px;
          border: 1px solid #d1d5db;
          margin-bottom: 14px;
          font-size: 14px;
        }
        button {
          width: 100%;
          padding: 10px 16px;
          border-radius: 999px;
          border: none;
          background: #2563eb;
          color: #fff;
          font-weight: 600;
          cursor: pointer;
        }
        .error {
          background: #fee2e2;
          color: #b91c1c;
          padding: 10px 12px;
          border-radius: 10px;
          margin-bottom: 12px;
        }
      </style>
    </head>
    <body>
      <div class="card">
        <h1>Beheer login</h1>
        <p>Log in om AI‑rapporten te beheren.</p>
        <?php if ($loginError !== ""): ?>
          <div class="error"><?php echo h($loginError); ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="login" />
          <label for="username">Gebruikersnaam</label>
          <input id="username" name="username" type="text" autocomplete="username" required />
          <label for="password">Wachtwoord</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required />
          <button type="submit">Inloggen</button>
        </form>
      </div>
    </body>
  </html>
  <?php
  exit;
}

$error = null;
$message = isset($_GET["message"]) ? trim($_GET["message"]) : "";
$messageType = isset($_GET["messageType"]) ? trim($_GET["messageType"]) : "success";
$filter = isset($_GET["status"]) ? trim($_GET["status"]) : "open";
$allowedFilters = ["open", "approved", "reworked", "all"];
if (!in_array($filter, $allowedFilters, true)) {
  $filter = "open";
}

try {
  $pdo = getDbConnection();

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $id = (int) ($_POST["id"] ?? 0);
    $returnFilter = $_POST["return_status"] ?? $filter;
    if (!in_array($returnFilter, $allowedFilters, true)) {
      $returnFilter = "open";
    }

    if ($id <= 0) {
      header("Location: beheer.php?status=" . urlencode($returnFilter) . "&messageType=error&message=Ongeldig+ID");
      exit;
    }

    if ($action === "approve") {
      $stmt = $pdo->prepare(
        "UPDATE ai_question_reports
         SET status = 'approved', reviewed_at = NOW()
         WHERE id = :id"
      );
      $stmt->execute([":id" => $id]);
      header("Location: beheer.php?status=" . urlencode($returnFilter)
        . "&message=Vraag+goedgekeurd");
      exit;
    }

    if ($action === "delete") {
      $stmt = $pdo->prepare("DELETE FROM ai_question_reports WHERE id = :id");
      $stmt->execute([":id" => $id]);
      header("Location: beheer.php?status=" . urlencode($returnFilter)
        . "&message=Rapport+verwijderd");
      exit;
    }

    if ($action === "rework") {
      if (($config["serp_api_key"] ?? "") === "" || ($config["groq_api_key"] ?? "") === "") {
        header("Location: beheer.php?status=" . urlencode($returnFilter)
          . "&messageType=error&message=SerpAPI+of+Groq+config+ontbreekt");
        exit;
      }

      $stmt = $pdo->prepare(
        "SELECT id, category, question_text, answers, correct_index
         FROM ai_question_reports
         WHERE id = :id"
      );
      $stmt->execute([":id" => $id]);
      $report = $stmt->fetch();

      if (!$report) {
        header("Location: beheer.php?status=" . urlencode($returnFilter) . "&messageType=error&message=Rapport+niet+gevonden");
        exit;
      }

      try {
        $query = buildReworkQuery($report);
        $startMax = max(0, (int) ($config["serp_start_max"] ?? 0));
        $sources = ai_fetch_serp_sources($config, $query, $startMax, 5);
        $reworked = callGroqRework($config, $report, $sources);
      } catch (Throwable $reworkError) {
        error_log("Rework failed: " . $reworkError->getMessage());
        header("Location: beheer.php?status=" . urlencode($returnFilter)
          . "&messageType=error&message=Herstructureren+mislukt");
        exit;
      }

      $answersJson = json_encode($reworked["answers"], JSON_UNESCAPED_UNICODE);
      if ($answersJson === false) {
        $answersJson = "[]";
      }
      $sourcesJson = json_encode($sources, JSON_UNESCAPED_UNICODE);
      if ($sourcesJson === false) {
        $sourcesJson = "[]";
      }

      $stmt = $pdo->prepare(
        "UPDATE ai_question_reports
         SET status = 'reworked',
             reviewed_at = NOW(),
             reworked_question_text = :question,
             reworked_answers = :answers,
             reworked_correct_index = :correct_index,
             reworked_sources = :sources,
             reworked_at = NOW()
         WHERE id = :id"
      );
      $stmt->execute([
        ":question" => $reworked["question"],
        ":answers" => $answersJson,
        ":correct_index" => $reworked["correctIndex"],
        ":sources" => $sourcesJson,
        ":id" => $id,
      ]);

      header("Location: beheer.php?status=" . urlencode($returnFilter) . "&message=Vraag+geherstructureerd");
      exit;
    }
  }

  $where = "";
  $params = [];
  if ($filter !== "all") {
    $where = "WHERE status = :status";
    $params[":status"] = $filter;
  }

  $stmt = $pdo->prepare(
    "SELECT id, question_id, category, question_text, answers, correct_index, sources, origin,
            reported_at, created_at, status, reviewed_at,
            reworked_question_text, reworked_answers, reworked_correct_index, reworked_sources, reworked_at
     FROM ai_question_reports
     {$where}
     ORDER BY reported_at DESC, id DESC
     LIMIT 200"
  );
  $stmt->execute($params);
  $reports = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = $e->getMessage();
  error_log("Beheer error: " . $e->getMessage());
  $reports = [];
}

?>
<!doctype html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TrivAI - Beheer</title>
    <style>
      :root {
        color-scheme: light;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }
      body {
        margin: 0;
        background: #f5f7fb;
        color: #1f2937;
      }
      .page {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px 60px;
      }
      header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
      }
      .header-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }
      h1 {
        font-size: 28px;
        margin: 0;
      }
      a.back {
        text-decoration: none;
        background: #2563eb;
        color: #fff;
        padding: 10px 16px;
        border-radius: 999px;
        font-weight: 600;
      }
      a.logout {
        background: #6b7280;
      }
      .filters {
        display: flex;
        gap: 10px;
        margin-bottom: 18px;
        flex-wrap: wrap;
      }
      .filter {
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 999px;
        background: #e5e7eb;
        color: #111827;
        font-size: 13px;
        font-weight: 600;
      }
      .filter.active {
        background: #111827;
        color: #fff;
      }
      .note {
        margin: 0 0 16px;
        color: #4b5563;
      }
      .card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
      }
      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
      }
      th,
      td {
        text-align: left;
        vertical-align: top;
        padding: 12px 10px;
        border-bottom: 1px solid #e5e7eb;
      }
      th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
      }
      .answers,
      .sources {
        margin: 6px 0 0;
        padding-left: 18px;
      }
      .tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        background: #e0e7ff;
        color: #1e40af;
        font-size: 12px;
        font-weight: 600;
        margin-left: 6px;
      }
      .status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
      }
      .status.open {
        background: #fde68a;
        color: #92400e;
      }
      .status.approved {
        background: #bbf7d0;
        color: #166534;
      }
      .status.reworked {
        background: #bfdbfe;
        color: #1e40af;
      }
      .question-block {
        margin-bottom: 8px;
      }
      .question-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
        text-transform: uppercase;
      }
      .reworked {
        margin-top: 10px;
        padding: 10px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
      }
      .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .btn {
        border: none;
        border-radius: 999px;
        padding: 6px 12px;
        font-weight: 600;
        cursor: pointer;
      }
      .btn.approve {
        background: #16a34a;
        color: #fff;
      }
      .btn.rework {
        background: #f97316;
        color: #fff;
      }
      .notice {
        padding: 12px;
        border-radius: 12px;
        margin: 0 0 16px;
      }
      .notice.success {
        background: #dcfce7;
        color: #166534;
      }
      .notice.error {
        background: #fee2e2;
        color: #b91c1c;
      }
      .error {
        padding: 12px;
        background: #fee2e2;
        color: #b91c1c;
        border-radius: 12px;
      }
      .empty {
        padding: 20px;
        text-align: center;
        color: #6b7280;
      }
    </style>
  </head>
  <body>
    <div class="page">
      <header>
        <h1>AI vraagrapporten</h1>
        <div class="header-actions">
          <a class="back" href="index.php">Terug naar spel</a>
          <a class="back logout" href="beheer.php?logout=1">Uitloggen</a>
        </div>
      </header>

      <div class="filters">
        <a class="filter <?php echo $filter === "open" ? "active" : ""; ?>" href="beheer.php?status=open">Open</a>
        <a class="filter <?php echo $filter === "reworked" ? "active" : ""; ?>" href="beheer.php?status=reworked">Herstructureerd</a>
        <a class="filter <?php echo $filter === "approved" ? "active" : ""; ?>" href="beheer.php?status=approved">Goedgekeurd</a>
        <a class="filter <?php echo $filter === "all" ? "active" : ""; ?>" href="beheer.php?status=all">Alles</a>
      </div>

      <?php if ($message): ?>
        <p class="notice <?php echo $messageType === "error" ? "error" : "success"; ?>">
          <?php echo h($message); ?>
        </p>
      <?php endif; ?>

      <?php if ($error): ?>
        <p class="error">Database fout: <?php echo h($error); ?></p>
      <?php else: ?>
        <p class="note">Laatste <?php echo count($reports); ?> rapporten (max 200).</p>
        <div class="card">
          <?php if (count($reports) === 0): ?>
            <div class="empty">Nog geen rapporten gevonden.</div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Status</th>
                  <th>Tijd</th>
                  <th>Categorie</th>
                  <th>Vraag</th>
                  <th>Antwoorden</th>
                  <th>Bronnen</th>
                  <th>Acties</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reports as $report): ?>
                  <?php
                    $answers = decodeJsonArray($report["answers"] ?? "");
                    $sources = decodeJsonArray($report["sources"] ?? "");
                    $correctIndex = is_numeric($report["correct_index"] ?? null) ? (int) $report["correct_index"] : -1;
                    $timeValue = $report["reported_at"] ?? $report["created_at"] ?? "";
                    $statusValue = $report["status"] ?? "open";
                    $reworkedQuestion = $report["reworked_question_text"] ?? "";
                    $reworkedAnswers = decodeJsonArray($report["reworked_answers"] ?? "");
                    $reworkedSources = decodeJsonArray($report["reworked_sources"] ?? "");
                    $reworkedCorrectIndex = is_numeric($report["reworked_correct_index"] ?? null)
                      ? (int) $report["reworked_correct_index"]
                      : -1;
                  ?>
                  <tr>
                    <td><?php echo h((string) ($report["id"] ?? "")); ?></td>
                    <td><span class="status <?php echo h($statusValue); ?>"><?php echo h($statusValue); ?></span></td>
                    <td><?php echo h((string) $timeValue); ?></td>
                    <td><?php echo h((string) ($report["category"] ?? "-")); ?></td>
                    <td>
                      <div class="question-block">
                        <div class="question-label">Origineel</div>
                        <div><?php echo h((string) ($report["question_text"] ?? "")); ?></div>
                      </div>
                      <?php if ($reworkedQuestion !== ""): ?>
                        <div class="reworked">
                          <div class="question-label">Herstructureerd</div>
                          <div><?php echo h((string) $reworkedQuestion); ?></div>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (count($answers) > 0): ?>
                        <div class="question-label">Origineel</div>
                        <ol class="answers">
                          <?php foreach ($answers as $index => $answer): ?>
                            <li>
                              <?php echo h((string) $answer); ?>
                              <?php if ($index === $correctIndex): ?>
                                <span class="tag">Correct</span>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ol>
                      <?php endif; ?>

                      <?php if (count($reworkedAnswers) > 0): ?>
                        <div class="question-label">Herstructureerd</div>
                        <ol class="answers">
                          <?php foreach ($reworkedAnswers as $index => $answer): ?>
                            <li>
                              <?php echo h((string) $answer); ?>
                              <?php if ($index === $reworkedCorrectIndex): ?>
                                <span class="tag">Correct</span>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ol>
                      <?php endif; ?>

                      <?php if (count($answers) === 0 && count($reworkedAnswers) === 0): ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (count($sources) > 0): ?>
                        <div class="question-label">Origineel</div>
                        <ul class="sources">
                          <?php foreach ($sources as $source): ?>
                            <?php
                              $title = "";
                              $url = "";
                              if (is_array($source)) {
                                $title = $source["title"] ?? "";
                                $url = $source["url"] ?? "";
                              } elseif (is_string($source)) {
                                $title = $source;
                              }
                            ?>
                            <li>
                              <?php if ($url): ?>
                                <a href="<?php echo h($url); ?>" target="_blank" rel="noopener noreferrer">
                                  <?php echo h($title ?: $url); ?>
                                </a>
                              <?php else: ?>
                                <?php echo h($title ?: "-"); ?>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>

                      <?php if (count($reworkedSources) > 0): ?>
                        <div class="question-label">Herstructureerd</div>
                        <ul class="sources">
                          <?php foreach ($reworkedSources as $source): ?>
                            <?php
                              $title = "";
                              $url = "";
                              if (is_array($source)) {
                                $title = $source["title"] ?? "";
                                $url = $source["url"] ?? "";
                              } elseif (is_string($source)) {
                                $title = $source;
                              }
                            ?>
                            <li>
                              <?php if ($url): ?>
                                <a href="<?php echo h($url); ?>" target="_blank" rel="noopener noreferrer">
                                  <?php echo h($title ?: $url); ?>
                                </a>
                              <?php else: ?>
                                <?php echo h($title ?: "-"); ?>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>

                      <?php if (count($sources) === 0 && count($reworkedSources) === 0): ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($statusValue === "open" || $statusValue === "reworked"): ?>
                        <form method="post" class="actions">
                          <input type="hidden" name="id" value="<?php echo h((string) $report["id"]); ?>" />
                          <input type="hidden" name="return_status" value="<?php echo h($filter); ?>" />
                          <button class="btn approve" type="submit" name="action" value="approve">Goedkeuren</button>
                          <button class="btn rework" type="submit" name="action" value="rework">Herstructureren</button>
                        </form>
                      <?php elseif ($statusValue === "approved"): ?>
                        <form method="post" class="actions">
                          <input type="hidden" name="id" value="<?php echo h((string) $report["id"]); ?>" />
                          <input type="hidden" name="return_status" value="<?php echo h($filter); ?>" />
                          <button class="btn rework" type="submit" name="action" value="delete">Verwijderen</button>
                        </form>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </body>
</html>
