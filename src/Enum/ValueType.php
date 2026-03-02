<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Enum;

/**
 * Discriminates the semantic type of an environment variable value.
 *
 * Detection order matters: detectors run from highest to lowest priority.
 * The first detector that matches wins. Default priority order:
 *
 *   Null (200) → Boolean (190) → Integer (180) → Float (170)
 *   → Json (160) → Array (150) → String (fallback)
 *
 * ARFA 1.3 P1: Each case is an immutable identity — no backing value needed.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
enum ValueType
{
    case String;
    case Integer;
    case Float;
    case Boolean;
    case Null;
    case Json;
    case Array;
}
