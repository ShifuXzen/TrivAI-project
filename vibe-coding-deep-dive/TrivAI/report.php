<?php
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

$raw = file_get_contents("php://input");
$payload = json_decode($raw, true);

if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(["error" => "Invalid JSON"]);
  exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . "db.php";

function safeLimit(string $text, int $limit): string
{
  if (function_exists("mb_substr")) {
    return mb_substr($text, 0, $limit);
  }
  return substr($text, 0, $limit);
}

function rateLimitOrFail(string $path, string $key, int $limit, int $windowSeconds): void
{
  if ($limit <= 0) {
    return;
  }

  $dir = dirname($path);
  if (!is_dir($dir) || !is_writable($dir)) {
    return;
  }

  $now = time();
  $data = [];
  if (file_exists($path)) {
    $raw = file_get_contents($path);
    $decoded = $raw ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
      $data = $decoded;
    }
  }

  foreach ($data as $ip => $timestamps) {
    if (!is_array($timestamps)) {
      unset($data[$ip]);
      continue;
    }
    $filtered = array_values(array_filter($timestamps, function ($ts) use ($now, $windowSeconds) {
      return is_int($ts) && ($now - $ts) < $windowSeconds;
    }));
    if (count($filtered) === 0) {
      unset($data[$ip]);
    } else {
      $data[$ip] = $filtered;
    }
  }

  $entries = $data[$key] ?? [];
  if (count($entries) >= $limit) {
    http_response_code(429);
    echo json_encode(["error" => "Te veel rapporten. Probeer later opnieuw."]);
    error_log("Report rate limit hit for IP: " . $key);
    exit;
  }

  $entries[] = $now;
  $data[$key] = $entries;
  file_put_contents($path, json_encode($data), LOCK_EX);
}

$ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";
$rateLimit = max(0, (int) (getenv("REPORT_RATE_LIMIT") ?: 5));
$rateWindow = max(10, (int) (getenv("REPORT_RATE_WINDOW") ?: 60));
$ratePath = __DIR__ . DIRECTORY_SEPARATOR . "report-rate.json";
rateLimitOrFail($ratePath, $ip, $rateLimit, $rateWindow);

$question = trim((string) ($payload["question"] ?? ""));
if ($question === "") {
  http_response_code(400);
  echo json_encode(["error" => "Vraag ontbreekt"]);
  exit;
}

$answers = $payload["answers"] ?? [];
if (!is_array($answers) || count($answers) !== 4) {
  http_response_code(400);
  echo json_encode(["error" => "Antwoorden ontbreken of zijn ongeldig"]);
  exit;
}

$answers = array_map(function ($answer) {
  return safeLimit((string) $answer, 200);
}, $answers);

$report = [
  "id" => safeLimit((string) ($payload["id"] ?? ""), 64),
  "category" => safeLimit((string) ($payload["category"] ?? ""), 64),
  "question" => safeLimit($question, 500),
  "answers" => $answers,
  "correctIndex" => $payload["correctIndex"] ?? null,
  "sources" => $payload["sources"] ?? [],
  "origin" => safeLimit((string) ($payload["origin"] ?? "local"), 32),
  "reportedAt" => $payload["reportedAt"] ?? date("c"),
  "userAgent" => safeLimit((string) ($_SERVER["HTTP_USER_AGENT"] ?? ""), 255),
  "ip" => safeLimit((string) $ip, 64),
];

$answersJson = json_encode($report["answers"], JSON_UNESCAPED_UNICODE);
if ($answersJson === false) {
  $answersJson = "[]";
}

$sourcesJson = json_encode($report["sources"], JSON_UNESCAPED_UNICODE);
if ($sourcesJson === false) {
  $sourcesJson = null;
}

$correctIndex = is_numeric($report["correctIndex"]) ? (int) $report["correctIndex"] : 0;
if ($correctIndex < 0 || $correctIndex > 3) {
  $correctIndex = 0;
}

$reportedAt = null;
if (!empty($report["reportedAt"])) {
  try {
    $reportedAt = (new DateTime($report["reportedAt"]))->format("Y-m-d H:i:s");
  } catch (Exception $e) {
    $reportedAt = null;
  }
}
if ($reportedAt === null) {
  $reportedAt = date("Y-m-d H:i:s");
}

try {
  $pdo = getDbConnection();
  $stmt = $pdo->prepare(
    "INSERT INTO ai_question_reports
      (question_id, category, question_text, answers, correct_index, sources, origin, reported_at, user_agent, ip_address)
     VALUES
      (:question_id, :category, :question_text, :answers, :correct_index, :sources, :origin, :reported_at, :user_agent, :ip_address)"
  );
  $stmt->execute([
    ":question_id" => $report["id"],
    ":category" => $report["category"],
    ":question_text" => $report["question"],
    ":answers" => $answersJson,
    ":correct_index" => $correctIndex,
    ":sources" => $sourcesJson,
    ":origin" => $report["origin"],
    ":reported_at" => $reportedAt,
    ":user_agent" => $report["userAgent"],
    ":ip_address" => $report["ip"],
  ]);
} catch (Throwable $error) {
  http_response_code(500);
  error_log("Report save failed: " . $error->getMessage());
  echo json_encode(["error" => "Database fout bij opslaan report."]);
  exit;
}

echo json_encode(["message" => "Bedankt, je rapport is opgeslagen."]);
