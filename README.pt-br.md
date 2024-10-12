# KaririCode Framework: Componente Dotenv

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

Um componente robusto e flexível para gerenciamento de variáveis de ambiente no KaririCode Framework, fornecendo recursos avançados para lidar com arquivos .env em aplicações PHP.

## Funcionalidades

- Parse e carregamento de variáveis de ambiente a partir de arquivos .env
- Suporte para interpolação de variáveis
- **Detecção e conversão automática de tipos**
  - Detecta e converte tipos comuns (string, inteiro, float, booleano, array, JSON)
  - Preserva tipos de dados para uso mais preciso em sua aplicação
- **Sistema de tipos personalizável**
  - Extensível com detectores e conversores de tipos personalizados
  - Controle refinado sobre como suas variáveis de ambiente são processadas
- Modo estrito para validação de nomes de variáveis
- Acesso fácil às variáveis de ambiente por meio de uma função auxiliar global
- Suporte para estruturas de dados complexas (arrays e JSON) em variáveis de ambiente

## Instalação

Para instalar o componente Dotenv do KaririCode no seu projeto, execute o seguinte comando:

```bash
composer require kariricode/dotenv
```

## Uso

### Uso Básico

1. Crie um arquivo `.env` no diretório raiz do seu projeto:

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

2. No arquivo de bootstrap da sua aplicação:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\DotenvFactory;
use function KaririCode\Dotenv\env;

$dotenv = DotenvFactory::create(__DIR__ . '/../.env');
$dotenv->load();

// Agora você pode usar a função env() para acessar suas variáveis de ambiente
$appName = env('KARIRI_APP_NAME');
$debug = env('KARIRI_APP_DEBUG');
$jsonConfig = env('KARIRI_JSON_CONFIG');
$arrayConfig = env('KARIRI_ARRAY_CONFIG');
```

### Detecção e Conversão de Tipos

O componente Dotenv do KaririCode detecta e converte automaticamente os seguintes tipos:

- Strings
- Inteiros
- Floats
- Booleanos
- Valores nulos
- Arrays
- Objetos JSON

Exemplo:

```env
STRING_VAR=Hello World
INT_VAR=42
FLOAT_VAR=3.14
BOOL_VAR=true
NULL_VAR=null
ARRAY_VAR=["item1", "item2", "item3"]
JSON_VAR={"key": "value", "nested": {"subkey": "subvalue"}}
```

Quando acessadas usando a função `env()`, essas variáveis serão automaticamente convertidas para seus tipos PHP apropriados:

```php
$stringVar = env('STRING_VAR'); // string: "Hello World"
$intVar = env('INT_VAR');       // inteiro: 42
$floatVar = env('FLOAT_VAR');   // float: 3.14
$boolVar = env('BOOL_VAR');     // booleano: true
$nullVar = env('NULL_VAR');     // null
$arrayVar = env('ARRAY_VAR');   // array: ["item1", "item2", "item3"]
$jsonVar = env('JSON_VAR');     // array: ["key" => "value", "nested" => ["subkey" => "subvalue"]]
```

Essa tipagem automática garante que você esteja trabalhando com os tipos corretos em sua aplicação, reduzindo erros relacionados a tipos e melhorando a confiabilidade geral do código.

### Uso Avançado

#### Detectores de Tipo Personalizados

Crie detectores de tipo personalizados para lidar com formatos específicos:

```php
use KaririCode\Dotenv\Type\Detector\AbstractTypeDetector;

class CustomDetector extends AbstractTypeDetector
{
    public const PRIORITY = 100;

    public function detect(mixed $value): ?string
    {
        // Sua lógica de detecção aqui
        // Retorne o tipo detectado como uma string, ou null se não detectado
    }
}

$dotenv->addTypeDetector(new CustomDetector());
```

#### Conversores de Tipo Personalizados

Crie conversores de tipo personalizados para lidar com tipos de dados específicos:

```php
use KaririCode\Dotenv\Contract\Type\TypeCaster;

class CustomCaster implements TypeCaster
{
    public function cast(mixed $value): mixed
    {
        // Sua lógica de conversão aqui
    }
}

$dotenv->addTypeCaster('custom_type', new CustomCaster());
```

## Desenvolvimento e Testes

Para fins de desenvolvimento e teste, este pacote utiliza Docker e Docker Compose para garantir consistência entre diferentes ambientes. Um Makefile é fornecido para conveniência.

### Pré-requisitos

- Docker
- Docker Compose
- Make (opcional, mas recomendado para facilitar a execução de comandos)

### Configuração para Desenvolvimento

1. Clone o repositório:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-dotenv.git
   cd kariricode-dotenv
   ```

2. Configure o ambiente:

   ```bash
   make setup-env
   ```

3. Inicie os containers Docker:

   ```bash
   make up
   ```

4. Instale as dependências:
   ```bash
   make composer-install
   ```

### Comandos Make Disponíveis

- `make up`: Inicia todos os serviços em segundo plano
- `make down`: Para e remove todos os containers
- `make build`: Constrói as imagens Docker
- `make shell`: Acessa o shell do container PHP
- `make test`: Executa os testes
- `make coverage`: Executa a cobertura de testes com formatação visual
- `make cs-fix`: Executa o PHP CS Fixer para corrigir o estilo do código
- `make quality`: Executa todos os comandos de qualidade (cs-check, test, security-check)

Para uma lista completa de comandos disponíveis, execute:

```bash
make help
```

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Suporte e Comunidade

- **Documentação**: [https://kariricode.org/docs/dotenv](https://kariricode.org/docs/dotenv)
- **Rastreador de Problemas**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-dotenv/issues)
- **Comunidade**: [Comunidade KaririCode Club](https://kariricode.club)

## Agradecimentos

- A equipe do KaririCode Framework e contribuidores.
- Inspirado por outras bibliotecas populares de Dotenv para PHP.

---

Construído com ❤️ pela equipe KaririCode. Capacitando desenvolvedores a criar aplicações PHP mais robustas e flexíveis.
