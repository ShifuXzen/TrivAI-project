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

$report = [
  "id" => $payload["id"] ?? null,
  "category" => $payload["category"] ?? null,
  "question" => $payload["question"] ?? null,
  "answers" => $payload["answers"] ?? [],
  "correctIndex" => $payload["correctIndex"] ?? null,
  "sources" => $payload["sources"] ?? [],
  "origin" => $payload["origin"] ?? "local",
  "reportedAt" => $payload["reportedAt"] ?? date("c"),
  "userAgent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
  "ip" => $_SERVER["REMOTE_ADDR"] ?? "",
];

require_once __DIR__ . DIRECTORY_SEPARATOR . "db.php";

$answersJson = json_encode($report["answers"], JSON_UNESCAPED_UNICODE);
if ($answersJson === false) {
  $answersJson = "[]";
}

$sourcesJson = json_encode($report["sources"], JSON_UNESCAPED_UNICODE);
if ($sourcesJson === false) {
  $sourcesJson = null;
}

$correctIndex = is_numeric($report["correctIndex"]) ? (int) $report["correctIndex"] : 0;

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
  echo json_encode(["error" => "Database fout bij opslaan report."]);
  exit;
}

echo json_encode(["message" => "Bedankt, je rapport is opgeslagen."]);
