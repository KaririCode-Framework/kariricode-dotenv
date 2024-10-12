# KaririCode Framework: Dotenv Component

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

A robust and flexible environment variable management component for the KaririCode Framework, providing advanced features for handling .env files in PHP applications.

## Features

- Parse and load environment variables from .env files
- Support for variable interpolation
- **Automatic type detection and casting**
  - Detects and converts common types (string, integer, float, boolean, array, JSON)
  - Preserves data types for more accurate usage in your application
- **Customizable type system**
  - Extensible with custom type detectors and casters
  - Fine-grained control over how your environment variables are processed
- Strict mode for variable name validation
- Easy access to environment variables through a global helper function
- Support for complex data structures (arrays and JSON) in environment variables

## Installation

To install the KaririCode Dotenv component in your project, run the following command:

```bash
composer require kariricode/dotenv
```

## Usage

### Basic Usage

1. Create a `.env` file in your project's root directory:

```env
KARIRI_APP_ENV=develop
KARIRI_APP_NAME=KaririCode
KARIRI_PHP_VERSION=8.3
KARIRI_PHP_PORT=9003
KARIRI_APP_DEBUG=true
KARIRI_APP_URL=https://kariricode.com
KARIRI_MAIL_FROM_NAME="${KARIRI_APP_NAME}"
KARIRI_JSON_CONFIG={"key": "value", "nested": {"subkey": "subvalue"}}
KARIRI_ARRAY_CONFIG=["item1", "item2", "item with spaces"]
```

2. In your application's bootstrap file:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\DotenvFactory;
use function KaririCode\Dotenv\env;

$dotenv = DotenvFactory::create(__DIR__ . '/../.env');
$dotenv->load();

// Now you can use the env() function to access your environment variables
$appName = env('KARIRI_APP_NAME');
$debug = env('KARIRI_APP_DEBUG');
$jsonConfig = env('KARIRI_JSON_CONFIG');
$arrayConfig = env('KARIRI_ARRAY_CONFIG');
```

### Type Detection and Casting

The KaririCode Dotenv component automatically detects and casts the following types:

- Strings
- Integers
- Floats
- Booleans
- Null values
- Arrays
- JSON objects

Example:

```env
STRING_VAR=Hello World
INT_VAR=42
FLOAT_VAR=3.14
BOOL_VAR=true
NULL_VAR=null
ARRAY_VAR=["item1", "item2", "item3"]
JSON_VAR={"key": "value", "nested": {"subkey": "subvalue"}}
```

When accessed using the `env()` function, these variables will be automatically cast to their appropriate PHP types:

```php
$stringVar = env('STRING_VAR'); // string: "Hello World"
$intVar = env('INT_VAR');       // integer: 42
$floatVar = env('FLOAT_VAR');   // float: 3.14
$boolVar = env('BOOL_VAR');     // boolean: true
$nullVar = env('NULL_VAR');     // null
$arrayVar = env('ARRAY_VAR');   // array: ["item1", "item2", "item3"]
$jsonVar = env('JSON_VAR');     // array: ["key" => "value", "nested" => ["subkey" => "subvalue"]]
```

This automatic typing ensures that you're working with the correct data types in your application, reducing type-related errors and improving overall code reliability.

### Advanced Usage

#### Custom Type Detectors

Create custom type detectors to handle specific formats:

```php
use KaririCode\Dotenv\Type\Detector\AbstractTypeDetector;

class CustomDetector extends AbstractTypeDetector
{
    public const PRIORITY = 100;

    public function detect(mixed $value): ?string
    {
        // Your detection logic here
        // Return the detected type as a string, or null if not detected
    }
}

$dotenv->addTypeDetector(new CustomDetector());
```

#### Custom Type Casters

Create custom type casters to handle specific data types:

```php
use KaririCode\Dotenv\Contract\TypeCaster;

class CustomCaster implements TypeCaster
{
    public function cast(mixed $value): mixed
    {
        // Your casting logic here
    }
}

$dotenv->addTypeCaster('custom_type', new CustomCaster());
```

## Development and Testing

For development and testing purposes, this package uses Docker and Docker Compose to ensure consistency across different environments. A Makefile is provided for convenience.

### Prerequisites

- Docker
- Docker Compose
- Make (optional, but recommended for easier command execution)

### Setup for Development

1. Clone the repository:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-dotenv.git
   cd kariricode-dotenv
   ```

2. Set up the environment:

   ```bash
   make setup-env
   ```

3. Start the Docker containers:

   ```bash
   make up
   ```

4. Install dependencies:
   ```bash
   make composer-install
   ```

### Available Make Commands

- `make up`: Start all services in the background
- `make down`: Stop and remove all containers
- `make build`: Build Docker images
- `make shell`: Access the shell of the PHP container
- `make test`: Run tests
- `make coverage`: Run test coverage with visual formatting
- `make cs-fix`: Run PHP CS Fixer to fix code style
- `make quality`: Run all quality commands (cs-check, test, security-check)

For a full list of available commands, run:

```bash
make help
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support and Community

- **Documentation**: [https://kariricode.org/docs/dotenv](https://kariricode.org/docs/dotenv)
- **Issue Tracker**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-dotenv/issues)
- **Community**: [KaririCode Club Community](https://kariricode.club)

## Acknowledgments

- The KaririCode Framework team and contributors.
- Inspired by other popular PHP Dotenv libraries.

---

Built with ❤️ by the KaririCode team. Empowering developers to build more robust and flexible PHP applications.
