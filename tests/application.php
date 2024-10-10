<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\DotenvFactory;

use function KaririCode\Dotenv\env;

use KaririCode\Dotenv\Exception\DotenvException;

function testEnvVariable(string $key, string $expectedType): void
{
    $value = env($key);
    $actualType = gettype($value);

    echo sprintf(
        "%s: %s (Expected: %s, Actual: %s)\n",
        $key,
        is_array($value) || is_object($value) ? json_encode($value) : var_export($value, true),
        $expectedType,
        $actualType
    );

    if ($actualType !== $expectedType) {
        echo "WARNING: Type mismatch for $key\n";
    }
}

try {
    $dotenv = DotenvFactory::create(__DIR__ . '/../.env');
    $dotenv->load();

    $variables = [
        'KARIRI_APP_ENV' => 'string',
        'KARIRI_APP_NAME' => 'string',
        'KARIRI_PHP_VERSION' => 'string',
        'KARIRI_PHP_PORT' => 'integer',
        'KARIRI_APP_DEBUG' => 'boolean',
        'KARIRI_APP_URL' => 'string',
        'KARIRI_DB_CONNECTION' => 'string',
        'KARIRI_DB_HOST' => 'string',
        'KARIRI_DB_PORT' => 'integer',
        'KARIRI_DB_DATABASE' => 'string',
        'KARIRI_DB_USERNAME' => 'string',
        'KARIRI_DB_PASSWORD' => 'string',
        'KARIRI_CACHE_DRIVER' => 'string',
        'KARIRI_SESSION_LIFETIME' => 'integer',
        'KARIRI_MAIL_MAILER' => 'string',
        'KARIRI_MAIL_HOST' => 'string',
        'KARIRI_MAIL_PORT' => 'integer',
        'KARIRI_MAIL_USERNAME' => 'NULL',
        'KARIRI_MAIL_PASSWORD' => 'NULL',
        'KARIRI_MAIL_ENCRYPTION' => 'NULL',
        'KARIRI_MAIL_FROM_ADDRESS' => 'NULL',
        'KARIRI_MAIL_FROM_NAME' => 'string',
        'KARIRI_JSON_CONFIG' => 'string',
        'KARIRI_ARRAY_CONFIG' => 'array',
    ];

    foreach ($variables as $key => $expectedType) {
        testEnvVariable($key, $expectedType);
    }

    // Test variable interpolation
    $appName = env('KARIRI_APP_NAME');
    $mailFromName = env('KARIRI_MAIL_FROM_NAME');
    echo $appName === $mailFromName
        ? "Variable interpolation for KARIRI_MAIL_FROM_NAME works correctly.\n"
        : "WARNING: Variable interpolation for KARIRI_MAIL_FROM_NAME failed.\n";

    // Test JSON parsing
    $jsonConfig = env('KARIRI_JSON_CONFIG');
    $decodedJson = json_decode($jsonConfig, true);
    echo $decodedJson && isset($decodedJson['nested']['subkey']) && 'subvalue' === $decodedJson['nested']['subkey']
        ? "JSON parsing works correctly.\n"
        : "WARNING: JSON parsing failed.\n";

    // Test array parsing
    $arrayConfig = env('KARIRI_ARRAY_CONFIG');
    echo is_array($arrayConfig) && 3 === count($arrayConfig) && 'item with spaces' === $arrayConfig[2]
        ? "Array parsing works correctly.\n"
        : "WARNING: Array parsing failed.\n";
} catch (DotenvException $e) {
    echo 'An error occurred: ' . $e->getMessage() . "\n";
}
