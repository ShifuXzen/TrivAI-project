<?php
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . DIRECTORY_SEPARATOR . "config.php";

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
  if (function_exists("mb_strtolower")) {
    return mb_strtolower($trimmed);
  }

  return strtolower($trimmed);
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

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  respond(405, ["error" => "Method not allowed"]);
}

$debug = isset($_GET["debug"]) && $_GET["debug"] === "1";
$excludeId = isset($_GET["excludeId"]) ? trim($_GET["excludeId"]) : "";
$excludeQuestion = isset($_GET["excludeQuestion"]) ? trim($_GET["excludeQuestion"]) : "";
$excludeQuestion = $excludeQuestion !== "" ? safeSubstr($excludeQuestion, 200) : "";
$excludeCategoriesParam = isset($_GET["excludeCategories"]) ? trim($_GET["excludeCategories"]) : "";
$excludeQuestionsParam = isset($_GET["excludeQuestions"]) ? trim($_GET["excludeQuestions"]) : "";
$cachePath = __DIR__ . DIRECTORY_SEPARATOR . "question-cache.json";
$cacheTtl = max(0, (int) ($config["cache_ttl"] ?? 900));
$cacheMax = max(0, (int) ($config["cache_max"] ?? 50));

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

$excludeCategory = isset($_GET["excludeCategory"]) ? trim($_GET["excludeCategory"]) : "";
$requestedCategory = isset($_GET["category"]) ? trim($_GET["category"]) : "";

$excludedCategories = normalizeList(parseList($excludeCategoriesParam, ","));
if ($excludeCategory !== "") {
  $excludedCategories[] = normalizeText($excludeCategory);
  $excludedCategories = array_values(array_unique($excludedCategories));
}

$excludedQuestions = [];
if ($excludeQuestion !== "") {
  $excludedQuestions[] = normalizeText($excludeQuestion);
}
if ($excludeQuestionsParam !== "") {
  $more = parseList($excludeQuestionsParam, "|");
  foreach ($more as $item) {
    $excludedQuestions[] = normalizeText($item);
  }
}
$excludedQuestions = array_values(array_unique($excludedQuestions));

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
  if (count($excludedQuestions) > 0) {
    $itemQuestion = $item["question"] ?? "";
    if ($itemQuestion !== "" && in_array(normalizeText($itemQuestion), $excludedQuestions, true)) {
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
$start = 0;
if ($startMax > 0) {
  $start = random_int(0, $startMax);
}
$params = http_build_query([
  "engine" => $config["serp_engine"],
  "q" => $query,
  "api_key" => $config["serp_api_key"],
  "hl" => $config["serp_hl"],
  "gl" => $config["serp_gl"],
  "num" => $config["serp_num"],
  "google_domain" => $config["serp_google_domain"],
  "start" => $start,
  "output" => "json",
]);

$ch = curl_init($config["serp_endpoint"] . "?" . $params);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Accept: application/json",
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_ENCODING, "");

$serpRaw = curl_exec($ch);
$serpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$serpError = curl_error($ch);
curl_close($ch);

if ($serpRaw === false || $serpStatus < 200 || $serpStatus >= 300) {
  if ($serpStatus === 429) {
    respond(429, ["error" => "SerpAPI rate limit reached"]);
  }
  $payload = ["error" => "SerpAPI request failed"];
  if ($debug) {
    $payload["serp_status"] = $serpStatus;
    $payload["serp_error"] = $serpError;
    $payload["serp_body"] = $serpRaw ? mb_substr($serpRaw, 0, 2000) : "";
  }
  respond(502, $payload);
}

$serpData = json_decode($serpRaw, true);
if (isset($serpData["error"])) {
  $payload = ["error" => "SerpAPI error: " . $serpData["error"]];
  if ($debug) {
    $payload["serp_body"] = mb_substr($serpRaw, 0, 2000);
  }
  respond(502, $payload);
}

$status = $serpData["search_metadata"]["status"] ?? "";
if ($status !== "" && $status !== "Success") {
  $payload = ["error" => "SerpAPI status: " . $status];
  if ($debug) {
    $payload["serp_body"] = mb_substr($serpRaw, 0, 2000);
  }
  respond(502, $payload);
}

$results = $serpData["organic_results"] ?? [];
if (!is_array($results) || count($results) === 0) {
  respond(502, ["error" => "No results from SerpAPI"]);
}

$sources = [];
foreach ($results as $item) {
  if (count($sources) >= 5) {
    break;
  }
  $title = $item["title"] ?? "";
  $url = $item["link"] ?? "";
  $snippet = $item["snippet"] ?? "";
  if ($title === "" && $snippet === "") {
    continue;
  }
  $sources[] = [
    "title" => $title,
    "url" => $url,
    "snippet" => $snippet,
  ];
}

if (count($sources) === 0) {
  respond(502, ["error" => "No usable sources"]);
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
$userPrompt = "Gebruik alleen de bronnen hieronder. Maak 1 trivia-vraag in het Nederlands.\n"
  . "Regels:\n"
  . "- Exact 4 antwoordopties\n"
  . "- Exact 1 correct antwoord\n"
  . "- correctIndex is 0-3\n"
  . "- category: {$category}\n"
  . ($excludeQuestion !== "" ? "- Vermijd exact deze vraag: \"{$excludeQuestion}\"\n" : "")
  . (count($excludedQuestions) > 0
    ? "- Vermijd exact deze vragen: \"" . implode("\" | \"", $excludedQuestions) . "\"\n"
    : "")
  . (count($excludedCategories) > 0
    ? "- Vermijd deze categorieën: " . implode(", ", $excludedCategories) . "\n"
    : "")
  . "Geef JSON met velden: question, answers, correctIndex, category.\n\n"
  . "Bronnen:\n"
  . implode("\n", $contextLines);

$llmPayload = [
  "model" => $config["groq_model"],
  "messages" => [
    ["role" => "system", "content" => $systemPrompt],
    ["role" => "user", "content" => $userPrompt],
  ],
  "temperature" => 0.7,
  "max_tokens" => 400,
];

$groqUrl = rtrim($config["groq_endpoint"], "/") . "/chat/completions";
$groqHeaders = [
  "Content-Type: application/json",
  "Accept: application/json",
  "Authorization: Bearer " . $config["groq_api_key"],
];

$groqCh = curl_init($groqUrl);
curl_setopt($groqCh, CURLOPT_HTTPHEADER, $groqHeaders);
curl_setopt($groqCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($groqCh, CURLOPT_TIMEOUT, $config["groq_timeout"]);
curl_setopt($groqCh, CURLOPT_POST, true);
curl_setopt($groqCh, CURLOPT_POSTFIELDS, json_encode($llmPayload));

$groqRaw = curl_exec($groqCh);
$groqStatus = curl_getinfo($groqCh, CURLINFO_HTTP_CODE);
$groqError = curl_error($groqCh);
curl_close($groqCh);

if ($groqRaw === false || $groqStatus < 200 || $groqStatus >= 300) {
  if ($groqStatus === 429) {
    respond(429, ["error" => "Groq rate limit reached"]);
  }
  $payload = ["error" => "Groq request failed"];
  if ($debug) {
    $payload["groq_status"] = $groqStatus;
    $payload["groq_error"] = $groqError;
    $payload["groq_body"] = $groqRaw ? mb_substr($groqRaw, 0, 2000) : "";
  }
  respond(502, $payload);
}

$groqData = json_decode($groqRaw, true);
$content = $groqData["choices"][0]["message"]["content"] ?? "";
if ($content === "") {
  $payload = ["error" => "Groq response missing content"];
  if ($debug) {
    $payload["groq_body"] = mb_substr($groqRaw, 0, 2000);
  }
  respond(502, $payload);
}

$cleanContent = trim($content);
if (preg_match("/```(?:json)?\\s*(\\{.*\\}|\\[.*\\])\\s*```/s", $cleanContent, $match)) {
  $cleanContent = $match[1];
}

$questionData = json_decode($cleanContent, true);
if (!is_array($questionData)) {
  $payload = ["error" => "Groq output was not valid JSON"];
  if ($debug) {
    $payload["groq_body"] = mb_substr($groqRaw, 0, 2000);
    $payload["groq_text"] = mb_substr($cleanContent, 0, 2000);
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

if ($cacheMax > 0) {
  $cacheItems[] = array_merge($response, ["cachedAt" => date("c")]);
  if (count($cacheItems) > $cacheMax) {
    $cacheItems = array_slice($cacheItems, -$cacheMax);
  }
  saveQuestionCache($cachePath, $cacheItems);
}

respond(200, $response);
