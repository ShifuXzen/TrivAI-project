<?php
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . DIRECTORY_SEPARATOR . "config.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "ai_helpers.php";

function respond(int $status, array $payload): void
{
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function safeSubstr(string $text, int $length): string
{
  if (function_exists("mb_substr")) {
    return mb_substr($text, 0, $length);
  }

  return substr($text, 0, $length);
}

function normalizeText(string $text): string
{
  $trimmed = trim($text);
  $lowered = function_exists("mb_strtolower")
    ? mb_strtolower($trimmed)
    : strtolower($trimmed);

  $normalized = $lowered;
  if (class_exists("Normalizer")) {
    $form = Normalizer::normalize($normalized, Normalizer::FORM_D);
    if ($form !== false && $form !== null) {
      $normalized = $form;
    }
  }

  $withoutMarks = preg_replace("/\\p{Mn}+/u", "", $normalized);
  if ($withoutMarks !== null) {
    $normalized = $withoutMarks;
  }

  if (function_exists("iconv")) {
    $translit = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $normalized);
    if ($translit !== false && $translit !== null) {
      $normalized = $translit;
    }
  }

  $normalized = preg_replace("/[^\\p{L}\\p{N}\\s]+/u", " ", $normalized) ?? $normalized;
  $normalized = preg_replace("/\\s+/u", " ", $normalized) ?? $normalized;
  return trim($normalized);
}

function parseList(string $value, string $delimiter = ","): array
{
  if ($value === "") {
    return [];
  }

  $parts = array_map("trim", explode($delimiter, $value));
  return array_values(array_filter($parts, fn($item) => $item !== ""));
}

function normalizeList(array $items): array
{
  $out = [];
  foreach ($items as $item) {
    $out[] = normalizeText($item);
  }
  return array_values(array_unique($out));
}

function isQuestionExcluded(string $question, array $excludedQuestions): bool
{
  if ($question === "" || count($excludedQuestions) === 0) {
    return false;
  }

  return in_array(normalizeText($question), $excludedQuestions, true);
}

function buildUserPrompt(
  string $category,
  string $excludeQuestion,
  array $excludedQuestions,
  array $excludedCategories,
  array $contextLines
): string {
  $prompt = "Gebruik alleen de bronnen hieronder. Maak 1 trivia-vraag in het Nederlands.\n"
    . "Regels:\n"
    . "- Exact 4 antwoordopties\n"
    . "- Exact 1 correct antwoord\n"
    . "- correctIndex is 0-3\n"
    . "- category: {$category}\n";

  if ($excludeQuestion !== "") {
    $prompt .= "- Vermijd exact deze vraag: \"{$excludeQuestion}\"\n";
  }
  if (count($excludedQuestions) > 0) {
    $prompt .= "- Vermijd exact deze vragen: \"" . implode("\" | \"", $excludedQuestions) . "\"\n";
  }
  if (count($excludedCategories) > 0) {
    $prompt .= "- Vermijd deze categorieen: " . implode(", ", $excludedCategories) . "\n";
  }

  $prompt .= "Geef JSON met velden: question, answers, correctIndex, category.\n\n"
    . "Bronnen:\n"
    . implode("\n", $contextLines);

  return $prompt;
}

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "";
if ($requestMethod === "") {
  $requestMethod = "CLI";
}

if (!in_array($requestMethod, ["GET", "POST", "CLI"], true)) {
  respond(405, ["error" => "Method not allowed"]);
}

$request = $_GET;
if ($requestMethod === "POST") {
  $postData = $_POST;
  $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
  if ($contentType !== "" && stripos($contentType, "application/json") !== false) {
    $rawBody = file_get_contents("php://input");
    if ($rawBody !== false && $rawBody !== "") {
      $decoded = json_decode($rawBody, true);
      if (is_array($decoded)) {
        $postData = array_merge($postData, $decoded);
      }
    }
  }
  $request = array_merge($request, $postData);
}

$debug = isset($request["debug"]) && $request["debug"] === "1";
$excludeId = isset($request["excludeId"]) ? trim($request["excludeId"]) : "";
$excludeQuestion = isset($request["excludeQuestion"]) ? trim($request["excludeQuestion"]) : "";
$excludeQuestion = $excludeQuestion !== "" ? safeSubstr($excludeQuestion, 200) : "";
$excludeCategoriesParam = isset($request["excludeCategories"]) ? trim($request["excludeCategories"]) : "";
$excludeQuestionsParam = isset($request["excludeQuestions"]) ? trim($request["excludeQuestions"]) : "";
$cachePath = __DIR__ . DIRECTORY_SEPARATOR . "question-cache.json";
$cacheTtl = max(0, (int) ($config["cache_ttl"] ?? 900));
$cacheMax = max(0, (int) ($config["cache_max"] ?? 50));
$historyPath = __DIR__ . DIRECTORY_SEPARATOR . "question-history.json";
$historyTtl = (int) ($config["history_ttl"] ?? 0);
$historyMax = max(0, (int) ($config["history_max"] ?? 500));

function loadQuestionCache(string $path): array
{
  if (!file_exists($path)) {
    return [];
  }

  $raw = file_get_contents($path);
  if ($raw === false || $raw === "") {
    return [];
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return [];
  }

  return $data;
}

function pruneQuestionCache(array $items, int $ttlSeconds): array
{
  if ($ttlSeconds <= 0) {
    return [];
  }

  $now = time();
  $filtered = [];
  foreach ($items as $item) {
    $cachedAt = $item["cachedAt"] ?? null;
    $timestamp = $cachedAt ? strtotime($cachedAt) : false;
    if ($timestamp === false) {
      continue;
    }
    if (($now - $timestamp) <= $ttlSeconds) {
      $filtered[] = $item;
    }
  }

  return $filtered;
}

function saveQuestionCache(string $path, array $items): void
{
  file_put_contents(
    $path,
    json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );
}

function loadQuestionHistory(string $path): array
{
  if (!file_exists($path)) {
    return [];
  }

  $raw = file_get_contents($path);
  if ($raw === false || $raw === "") {
    return [];
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return [];
  }

  return $data;
}

function pruneQuestionHistory(array $items, int $ttlSeconds): array
{
  if ($ttlSeconds <= 0) {
    return $items;
  }

  $now = time();
  $filtered = [];
  foreach ($items as $item) {
    $cachedAt = $item["cachedAt"] ?? null;
    $timestamp = $cachedAt ? strtotime($cachedAt) : false;
    if ($timestamp === false) {
      continue;
    }
    if (($now - $timestamp) <= $ttlSeconds) {
      $filtered[] = $item;
    }
  }

  return $filtered;
}

function saveQuestionHistory(string $path, array $items): void
{
  file_put_contents(
    $path,
    json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );
}

$missing = [];
$required = [
  "serp_api_key" => "SERP_API_KEY",
  "groq_api_key" => "GROQ_API_KEY",
  "groq_model" => "GROQ_MODEL",
];

foreach ($required as $key => $label) {
  if (($config[$key] ?? "") === "") {
    $missing[] = $label;
  }
}

if ($missing) {
  respond(500, ["error" => "Missing config: " . implode(", ", $missing)]);
}

$categories = [
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

$excludeCategory = isset($request["excludeCategory"]) ? trim($request["excludeCategory"]) : "";
$requestedCategory = isset($request["category"]) ? trim($request["category"]) : "";

$excludedCategories = normalizeList(parseList($excludeCategoriesParam, ","));
if ($excludeCategory !== "") {
  $excludedCategories[] = normalizeText($excludeCategory);
  $excludedCategories = array_values(array_unique($excludedCategories));
}

$excludedQuestionsRaw = [];
$excludedQuestions = [];
if ($excludeQuestion !== "") {
  $excludedQuestionsRaw[] = $excludeQuestion;
  $excludedQuestions[] = normalizeText($excludeQuestion);
}
if ($excludeQuestionsParam !== "") {
  $more = parseList($excludeQuestionsParam, "|");
  foreach ($more as $item) {
    $excludedQuestionsRaw[] = $item;
    $excludedQuestions[] = normalizeText($item);
  }
}
$excludedQuestionsRaw = array_values(array_unique($excludedQuestionsRaw));
$excludedQuestions = array_values(array_unique($excludedQuestions));

$historyItems = pruneQuestionHistory(loadQuestionHistory($historyPath), $historyTtl);
$historyQuestions = [];
foreach ($historyItems as $item) {
  $historyQuestion = $item["question"] ?? "";
  if ($historyQuestion === "") {
    continue;
  }
  $historyQuestions[] = normalizeText($historyQuestion);
}
$historyQuestions = array_values(array_unique($historyQuestions));
$excludedQuestionsCombined = array_values(array_unique(array_merge($excludedQuestions, $historyQuestions)));

$cacheItems = pruneQuestionCache(loadQuestionCache($cachePath), $cacheTtl);

$availableCategories = array_keys($categories);
if ($excludeCategory !== "") {
  $availableCategories = array_values(array_filter(
    $availableCategories,
    fn($category) => $category !== $excludeCategory
  ));
}
if (count($excludedCategories) > 0) {
  $availableCategories = array_values(array_filter(
    $availableCategories,
    fn($category) => !in_array(normalizeText($category), $excludedCategories, true)
  ));
}

$category = null;
if ($requestedCategory !== "" && isset($categories[$requestedCategory])) {
  $category = $requestedCategory;
} else {
  $cachedCategories = [];
  foreach ($cacheItems as $item) {
    $itemCategory = $item["category"] ?? "";
    if ($itemCategory === "") {
      continue;
    }
    if ($excludeCategory !== "" && $itemCategory === $excludeCategory) {
      continue;
    }
    if (count($excludedCategories) > 0 && in_array(normalizeText($itemCategory), $excludedCategories, true)) {
      continue;
    }
    if (!in_array($itemCategory, $availableCategories, true)) {
      continue;
    }
    $cachedCategories[$itemCategory] = true;
  }

  if (count($cachedCategories) > 0) {
    $keys = array_keys($cachedCategories);
    $category = $keys[array_rand($keys)];
  } else {
    if (count($availableCategories) === 0) {
      $availableCategories = array_keys($categories);
    }
    $category = $availableCategories[array_rand($availableCategories)];
  }
}

$cacheHitIndex = null;
foreach ($cacheItems as $index => $item) {
  if (($item["category"] ?? "") !== $category) {
    continue;
  }
  if ($excludeId !== "" && ($item["id"] ?? "") === $excludeId) {
    continue;
  }
  if ($excludeQuestion !== "") {
    $itemQuestion = $item["question"] ?? "";
    if ($itemQuestion !== "" && normalizeText($itemQuestion) === normalizeText($excludeQuestion)) {
      continue;
    }
  }
  if (count($excludedQuestionsCombined) > 0) {
    $itemQuestion = $item["question"] ?? "";
    if ($itemQuestion !== "" && in_array(normalizeText($itemQuestion), $excludedQuestionsCombined, true)) {
      continue;
    }
  }
  if (count($excludedCategories) > 0) {
    $itemCategory = $item["category"] ?? "";
    if ($itemCategory !== "" && in_array(normalizeText($itemCategory), $excludedCategories, true)) {
      continue;
    }
  }
  $cacheHitIndex = $index;
  break;
}

if ($cacheHitIndex !== null) {
  $cached = $cacheItems[$cacheHitIndex];
  array_splice($cacheItems, $cacheHitIndex, 1);
  saveQuestionCache($cachePath, $cacheItems);
  unset($cached["cachedAt"]);
  $cached["origin"] = "cache";
  if ($historyMax > 0) {
    $historyItems[] = [
      "question" => $cached["question"] ?? "",
      "category" => $cached["category"] ?? "",
      "cachedAt" => date("c"),
    ];
    if (count($historyItems) > $historyMax) {
      $historyItems = array_slice($historyItems, -$historyMax);
    }
    saveQuestionHistory($historyPath, $historyItems);
  }
  respond(200, $cached);
}

$baseQuery = $categories[$category];
$queryTemplates = [
  "{base} trivia",
  "{base} quizvragen",
  "{base} weetjes",
  "{base} pubquizvragen",
  "{base} vragen en antwoorden",
  "kennisvragen {category}",
  "{category} quizvragen",
  "leuke feiten {category}",
  "top 10 feiten {category}",
];
$template = $queryTemplates[array_rand($queryTemplates)];
$query = str_replace(
  ["{base}", "{category}"],
  [$baseQuery, $category],
  $template
);
$startMax = max(0, (int) ($config["serp_start_max"] ?? 0));
$serpDebug = $debug ? [] : null;
try {
  $sources = ai_fetch_serp_sources(
    $config,
    $query,
    $startMax,
    5,
    $serpDebug
  );
} catch (RuntimeException $e) {
  error_log("SerpAPI error: " . $e->getMessage());
  if ($e->getCode() === 429) {
    respond(429, ["error" => "SerpAPI rate limit reached"]);
  }
  $payload = ["error" => $e->getMessage()];
  if ($debug) {
    $payload["serp_status"] = $serpDebug["status"] ?? null;
    $payload["serp_error"] = $serpDebug["error"] ?? null;
    $payload["serp_body"] = $serpDebug["body"] ?? "";
  }
  respond(502, $payload);
}

$contextLines = array_map(function ($source) {
  $parts = [];
  if ($source["title"] !== "") {
    $parts[] = $source["title"];
  }
  if ($source["snippet"] !== "") {
    $parts[] = $source["snippet"];
  }
  if ($source["url"] !== "") {
    $parts[] = $source["url"];
  }
  return "- " . implode(" - ", $parts);
}, $sources);

$systemPrompt = "You generate Dutch trivia questions. Output ONLY valid JSON.";
$maxAttempts = max(1, (int) ($config["groq_max_attempts"] ?? 3));
$attempts = 0;
$questionData = null;
$questionText = "";
$answers = [];
$correctIndex = null;
$finalCategory = $category;
$excludedQuestionsPrompt = $excludedQuestionsRaw;
$excludedQuestionsNormalized = $excludedQuestionsCombined;
$groqDebug = $debug ? [] : null;

while ($attempts < $maxAttempts) {
  $attempts += 1;
  $userPrompt = buildUserPrompt(
    $category,
    $excludeQuestion,
    $excludedQuestionsPrompt,
    $excludedCategories,
    $contextLines
  );

  $groqDebugAttempt = $debug ? [] : null;
  try {
    $questionData = ai_groq_generate_json(
      $config,
      $systemPrompt,
      $userPrompt,
      0.7,
      400,
      $groqDebugAttempt
    );
  } catch (RuntimeException $e) {
    error_log("Groq error: " . $e->getMessage());
    if ($e->getCode() === 429) {
      respond(429, ["error" => "Groq rate limit reached"]);
    }
    $payload = ["error" => $e->getMessage()];
    if ($debug) {
      $payload["groq_status"] = $groqDebugAttempt["status"] ?? null;
      $payload["groq_error"] = $groqDebugAttempt["error"] ?? null;
      $payload["groq_body"] = $groqDebugAttempt["body"] ?? "";
      $payload["groq_text"] = $groqDebugAttempt["text"] ?? "";
    }
    respond(502, $payload);
  }

  $questionText = $questionData["question"] ?? "";
  $answers = $questionData["answers"] ?? [];
  $correctIndex = $questionData["correctIndex"] ?? null;
  $finalCategory = $questionData["category"] ?? $category;

  if (
    $questionText === "" ||
    !is_array($answers) ||
    count($answers) !== 4 ||
    !is_int($correctIndex) ||
    $correctIndex < 0 ||
    $correctIndex > 3
  ) {
    respond(502, ["error" => "LLM returned invalid question structure"]);
  }

  if (isQuestionExcluded($questionText, $excludedQuestionsNormalized)) {
    $excludedQuestionsPrompt[] = $questionText;
    $excludedQuestionsPrompt = array_values(array_unique($excludedQuestionsPrompt));
    $excludedQuestionsNormalized[] = normalizeText($questionText);
    $excludedQuestionsNormalized = array_values(array_unique($excludedQuestionsNormalized));

    if ($attempts >= $maxAttempts) {
      $payload = ["error" => "LLM returned duplicate question"];
      if ($debug) {
        $payload["groq_status"] = $groqDebugAttempt["status"] ?? null;
        $payload["groq_error"] = $groqDebugAttempt["error"] ?? null;
        $payload["groq_body"] = $groqDebugAttempt["body"] ?? "";
        $payload["groq_text"] = $groqDebugAttempt["text"] ?? "";
        $payload["attempts"] = $attempts;
      }
      respond(409, $payload);
    }
    continue;
  }

  $groqDebug = $groqDebugAttempt;
  break;
}

if ($questionData === null) {
  respond(502, ["error" => "LLM did not return a question"]);
}

$id = $questionData["id"] ?? ("ai-live-" . bin2hex(random_bytes(4)));

$response = [
  "id" => $id,
  "category" => $finalCategory,
  "question" => $questionText,
  "answers" => array_values($answers),
  "correctIndex" => $correctIndex,
  "sources" => $sources,
  "origin" => "live",
];

if ($historyMax > 0) {
  $historyItems[] = [
    "question" => $questionText,
    "category" => $finalCategory,
    "cachedAt" => date("c"),
  ];
  if (count($historyItems) > $historyMax) {
    $historyItems = array_slice($historyItems, -$historyMax);
  }
  saveQuestionHistory($historyPath, $historyItems);
}

if ($cacheMax > 0) {
  $cacheItems[] = array_merge($response, ["cachedAt" => date("c")]);
  if (count($cacheItems) > $cacheMax) {
    $cacheItems = array_slice($cacheItems, -$cacheMax);
  }
  saveQuestionCache($cachePath, $cacheItems);
}

respond(200, $response);

