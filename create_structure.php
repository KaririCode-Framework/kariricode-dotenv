<?php

$structure = [
    'tests/Unit/Contract' => ['DotenvTest.php'],
    'tests/Unit/Loader' => ['ArrayLoaderTest.php', 'FileLoaderTest.php'],
    'tests/Unit/Parser' => ['DefaultParserTest.php', 'StrictParserTest.php'],
    'tests/Unit/Type/Caster' => [
        'ArrayCasterTest.php',
        'BooleanCasterTest.php',
        'FloatCasterTest.php',
        'IntegerCasterTest.php',
        'JsonCasterTest.php',
        'NullCasterTest.php',
        'StringCasterTest.php',
        'TypeCasterRegistryTest.php'
    ],
    'tests/Unit/Type/Detector' => [
        'ArrayDetectorTest.php',
        'BooleanDetectorTest.php',
        'JsonDetectorTest.php',
        'NullDetectorTest.php',
        'NumericDetectorTest.php',
        'StringDetectorTest.php',
        'TypeDetectorRegistryTest.php'
    ],
    'tests/Unit' => ['DotenvTest.php', 'DotenvFactoryTest.php'],
    'tests/Integration' => ['DotenvIntegrationTest.php', 'TypeSystemIntegrationTest.php'],
    'tests/Functional' => ['DotenvFunctionalTest.php']
];

foreach ($structure as $dir => $files) {
    // Cria o diretório, caso não exista
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Diretório criado: $dir\n";
    }

    // Cria os arquivos dentro do diretório
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        file_put_contents($filePath, "<?php\n\n// Test case for $file\n");
        echo "Arquivo criado: $filePath\n";
    }
}

echo "Estrutura de diretórios e arquivos criada com sucesso!\n";
