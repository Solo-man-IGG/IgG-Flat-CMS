<?php

namespace CMS;

use Symfony\Component\Yaml\Yaml;

class Lang
{
    private static ?array $defaults = null;
    private static ?array $languagePack = null;
    private static ?array $custom = null;
    private static string $dataDir = '';

    public static function init(string $dataDir): void
    {
        self::$dataDir = $dataDir;
    }

    public static function get(string $key, mixed ...$args): string
    {
        self::load();

        $value = self::$custom[$key] ?? self::$languagePack[$key] ?? self::$defaults[$key] ?? $key;

        if ($args) {
            return sprintf($value, ...$args);
        }

        return $value;
    }

    public static function has(string $key): bool
    {
        self::load();
        return isset(self::$custom[$key]) || isset(self::$languagePack[$key]) || isset(self::$defaults[$key]);
    }

    public static function allDefaults(): array
    {
        self::load();
        return self::$defaults ?? [];
    }

    /**
     * Returns the effective base values for the current language (language pack + defaults).
     */
    public static function allBase(): array
    {
        self::load();
        return array_merge(self::$defaults ?? [], self::$languagePack ?? []);
    }

    public static function allCustom(): array
    {
        self::load();
        return self::$custom ?? [];
    }

    public static function getCustomPath(): string
    {
        return self::$dataDir . '/custom_lang.yaml';
    }

    public static function getDefaultsPath(): string
    {
        return self::$dataDir . '/default_lang.yaml';
    }

    public static function saveCustom(array $overrides): void
    {
        // Remove keys with empty values
        $overrides = array_filter($overrides, fn($v) => $v !== '' && $v !== null);
        ksort($overrides);

        $path = self::getCustomPath();
        file_put_contents($path, Yaml::dump($overrides, 4, 2));
        self::$custom = $overrides;
    }

    public static function getActiveLanguage(): string
    {
        $path = self::$dataDir . '/active_lang';
        if (file_exists($path)) {
            return trim(file_get_contents($path));
        }
        return 'en';
    }

    public static function switchLanguage(string $lang): void
    {
        $path = self::$dataDir . '/active_lang';
        file_put_contents($path, $lang);
        self::$languagePack = null;
        self::$defaults = null;
        self::$custom = null;
    }

    public static function getAvailableLanguages(): array
    {
        $langDir = self::$dataDir . '/lang';
        if (!is_dir($langDir)) {
            return ['en' => 'English'];
        }
        $files = glob($langDir . '/*.yaml');
        $languages = [];
        foreach ($files as $file) {
            $code = basename($file, '.yaml');
            $content = Yaml::parseFile($file);
            if ($content && isset($content['lang.display_name'])) {
                $languages[$code] = $content['lang.display_name'];
            } else {
                $languages[$code] = $code;
            }
        }
        return $languages;
    }

    private static function load(): void
    {
        if (self::$defaults !== null) {
            return;
        }

        if (!self::$dataDir) {
            self::$dataDir = dirname(__DIR__) . '/data';
        }

        // Load defaults (English base)
        $defaultsPath = self::getDefaultsPath();
        self::$defaults = file_exists($defaultsPath)
            ? (Yaml::parseFile($defaultsPath) ?: [])
            : [];

        // Load active language pack
        $activeLang = self::getActiveLanguage();
        $langDir = self::$dataDir . '/lang';
        $langFile = $langDir . '/' . $activeLang . '.yaml';
        self::$languagePack = file_exists($langFile)
            ? (Yaml::parseFile($langFile) ?: [])
            : [];

        // Load custom overrides
        $customPath = self::getCustomPath();
        self::$custom = file_exists($customPath)
            ? (Yaml::parseFile($customPath) ?: [])
            : [];
    }
}
