<?php

namespace Boshnik\PbAuth\Support;

/**
 * Настройки компонента: поставочные значения плюс то, что переопределил сайт.
 *
 * Сайтовый файл лежит в `core/App/config/pbauth.php` — компонент его только
 * читает и никогда не поставляет, поэтому обновление не может его затереть.
 * Через него меняются шаблоны, правила валидации, редиректы, группы
 * пользователей и подмена самих контроллеров — то есть всё, ради чего раньше
 * приходилось копировать контроллеры в App/ и править их там.
 */
class Config
{
    protected static ?array $config = null;

    public static function all(): array
    {
        if (static::$config !== null) {
            return static::$config;
        }

        $defaults = require __DIR__ . '/../../config/defaults.php';
        $site = [];

        $file = MODX_CORE_PATH . 'App/config/pbauth.php';
        if (file_exists($file)) {
            $loaded = include $file;
            if (is_array($loaded)) {
                $site = $loaded;
            }
        }

        return static::$config = static::merge($defaults, $site);
    }

    public static function get(string $key, $default = null)
    {
        $value = static::all();

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Правила валидации для действия.
     *
     * Сайт задаёт поля поштучно: неизвестное поле добавляется, известное
     * заменяется, а `null` убирает поставочное поле совсем — иначе, чтобы
     * убрать одно правило, пришлось бы переписывать весь набор.
     */
    public static function rules(string $action, array $replacements = []): array
    {
        $rules = static::get("rules.$action", []);
        $rules = array_filter($rules, fn($rule) => $rule !== null && $rule !== false);

        if (empty($replacements)) {
            return $rules;
        }

        return array_map(
            fn($rule) => is_string($rule) ? strtr($rule, $replacements) : $rule,
            $rules
        );
    }

    public static function reload(): void
    {
        static::$config = null;
    }

    /**
     * Словари сливаются по ключам вглубь, списки сайт заменяет целиком.
     *
     * Иначе `rules.profile` пришлось бы переписывать целиком ради одного поля,
     * а список групп пользователя нельзя было бы сократить — только дополнить.
     */
    protected static function merge(array $defaults, array $site): array
    {
        foreach ($site as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])
                && !static::isList($value) && !static::isList($defaults[$key])
            ) {
                $defaults[$key] = static::merge($defaults[$key], $value);
                continue;
            }
            $defaults[$key] = $value;
        }

        return $defaults;
    }

    protected static function isList(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }
}
