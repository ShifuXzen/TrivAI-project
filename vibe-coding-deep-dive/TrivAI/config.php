<?php

if (!function_exists("loadEnvFile")) {
  function loadEnvFile(string $path): void
  {
    if (!file_exists($path)) {
      return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      return;
    }

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === "" || str_starts_with($line, "#")) {
        continue;
      }

      if (strpos($line, "=") === false) {
        continue;
      }

      [$key, $value] = explode("=", $line, 2);
      $key = trim($key);
      $value = trim($value);
      $value = trim($value, "\"'");

      putenv($key . "=" . $value);
      $_ENV[$key] = $value;
    }
  }
}

$envPath = __DIR__ . DIRECTORY_SEPARATOR . ".env";
loadEnvFile($envPath);

return [
  "serp_api_key" => getenv("SERP_API_KEY") ?: "",
  "serp_endpoint" => getenv("SERP_ENDPOINT") ?: "https://serpapi.com/search.json",
  "serp_engine" => getenv("SERP_ENGINE") ?: "google",
  "serp_gl" => getenv("SERP_GL") ?: "nl",
  "serp_hl" => getenv("SERP_HL") ?: "nl",
  "serp_num" => (int) (getenv("SERP_NUM") ?: 6),
  "serp_google_domain" => getenv("SERP_GOOGLE_DOMAIN") ?: "google.nl",
  "serp_start_max" => (int) (getenv("SERP_START_MAX") ?: 20),
  "groq_api_key" => getenv("GROQ_API_KEY") ?: "",
  "groq_model" => getenv("GROQ_MODEL") ?: "",
  "groq_endpoint" => getenv("GROQ_ENDPOINT") ?: "https://api.groq.com/openai/v1",
  "groq_timeout" => (int) (getenv("GROQ_TIMEOUT") ?: 20),
  "cache_ttl" => (int) (getenv("CACHE_TTL") ?: 900),
  "cache_max" => (int) (getenv("CACHE_MAX") ?: 50),
  "history_ttl" => (int) (getenv("HISTORY_TTL") ?: 0),
  "history_max" => (int) (getenv("HISTORY_MAX") ?: 500),
];
