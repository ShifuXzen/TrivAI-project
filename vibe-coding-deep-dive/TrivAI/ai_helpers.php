<?php

function ai_safe_substr(string $text, int $length): string
{
  if (function_exists("mb_substr")) {
    return mb_substr($text, 0, $length);
  }
  return substr($text, 0, $length);
}

function ai_extract_json(string $content): string
{
  $clean = trim($content);
  if (preg_match("/```(?:json)?\\s*(\\{.*\\}|\\[.*\\])\\s*```/s", $clean, $match)) {
    return trim($match[1]);
  }
  return $clean;
}

function ai_fetch_serp_sources(
  array $config,
  string $query,
  int $startMax,
  int $maxSources = 5,
  ?array &$debugInfo = null
): array {
  $start = 0;
  if ($startMax > 0) {
    $start = random_int(0, $startMax);
  }

  $params = http_build_query([
    "engine" => $config["serp_engine"] ?? "google",
    "q" => $query,
    "api_key" => $config["serp_api_key"] ?? "",
    "hl" => $config["serp_hl"] ?? "nl",
    "gl" => $config["serp_gl"] ?? "nl",
    "num" => (int) ($config["serp_num"] ?? 6),
    "google_domain" => $config["serp_google_domain"] ?? "google.nl",
    "start" => $start,
    "output" => "json",
  ]);

  $url = rtrim($config["serp_endpoint"] ?? "https://serpapi.com/search.json", "?") . "?" . $params;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
  curl_setopt($ch, CURLOPT_ENCODING, "");

  $raw = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);

  if ($debugInfo !== null) {
    $debugInfo["status"] = $status;
    $debugInfo["error"] = $error;
    $debugInfo["body"] = $raw ? ai_safe_substr($raw, 2000) : "";
  }

  if ($raw === false || $status < 200 || $status >= 300) {
    $code = $status ?: 500;
    if ($status === 429) {
      throw new RuntimeException("SerpAPI rate limit reached", 429);
    }
    throw new RuntimeException(
      "SerpAPI request failed",
      $code
    );
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    throw new RuntimeException("SerpAPI response is onleesbaar", 500);
  }
  if (isset($data["error"])) {
    throw new RuntimeException("SerpAPI error: " . $data["error"], 502);
  }

  $statusValue = $data["search_metadata"]["status"] ?? "";
  if ($statusValue !== "" && $statusValue !== "Success") {
    throw new RuntimeException("SerpAPI status: " . $statusValue, 502);
  }

  $results = $data["organic_results"] ?? [];
  if (!is_array($results) || count($results) === 0) {
    throw new RuntimeException("No results from SerpAPI", 502);
  }

  $sources = [];
  foreach ($results as $item) {
    if (count($sources) >= $maxSources) {
      break;
    }
    $title = $item["title"] ?? "";
    $urlValue = $item["link"] ?? "";
    $snippet = $item["snippet"] ?? "";
    if ($title === "" && $snippet === "") {
      continue;
    }
    $sources[] = [
      "title" => $title,
      "url" => $urlValue,
      "snippet" => $snippet,
    ];
  }

  if (count($sources) === 0) {
    throw new RuntimeException("No usable sources", 502);
  }

  return $sources;
}

function ai_groq_generate_json(
  array $config,
  string $systemPrompt,
  string $userPrompt,
  float $temperature,
  int $maxTokens,
  ?array &$debugInfo = null
): array {
  $payload = [
    "model" => $config["groq_model"] ?? "",
    "messages" => [
      ["role" => "system", "content" => $systemPrompt],
      ["role" => "user", "content" => $userPrompt],
    ],
    "temperature" => $temperature,
    "max_tokens" => $maxTokens,
  ];

  $groqUrl = rtrim($config["groq_endpoint"] ?? "https://api.groq.com/openai/v1", "/") . "/chat/completions";
  $headers = [
    "Content-Type: application/json",
    "Accept: application/json",
    "Authorization: Bearer " . ($config["groq_api_key"] ?? ""),
  ];

  $ch = curl_init($groqUrl);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, (int) ($config["groq_timeout"] ?? 20));
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

  $raw = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);

  if ($debugInfo !== null) {
    $debugInfo["status"] = $status;
    $debugInfo["error"] = $error;
    $debugInfo["body"] = $raw ? ai_safe_substr($raw, 2000) : "";
  }

  if ($raw === false || $status < 200 || $status >= 300) {
    $code = $status ?: 500;
    if ($status === 429) {
      throw new RuntimeException("Groq rate limit reached", 429);
    }
    throw new RuntimeException("Groq request failed", $code);
  }

  $data = json_decode($raw, true);
  $content = $data["choices"][0]["message"]["content"] ?? "";
  if ($content === "") {
    throw new RuntimeException("Groq response missing content", 502);
  }

  $clean = ai_extract_json($content);
  if ($debugInfo !== null) {
    $debugInfo["text"] = ai_safe_substr($clean, 2000);
  }

  $decoded = json_decode($clean, true);
  if (!is_array($decoded)) {
    throw new RuntimeException("Groq output was not valid JSON", 502);
  }

  return $decoded;
}
