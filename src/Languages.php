<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * The wording files: one `<language>.php` per language, shipped in `lang/`
 * and optionally in the site's own directories, which win key by key. A site
 * adds a language by dropping a file of the same shape.
 *
 * Each file returns `['texts' => [...], 'categories' => [...]]`.
 */
final class Languages
{
    /** @var array<string, array> */
    private array $loaded = [];

    /** @param string[] $siteDirs Directories holding the site's own files. */
    public function __construct(private readonly array $siteDirs = [])
    {
    }

    /** Languages with a file, shipped or the site's, sorted. */
    public function available(): array
    {
        $languages = [];

        foreach ([Paths::lang(), ...$this->siteDirs] as $dir) {
            foreach (glob($dir . '/*.php') ?: [] as $file) {
                $languages[] = basename($file, '.php');
            }
        }

        $languages = array_values(array_unique($languages));
        sort($languages);

        return $languages;
    }

    /** The banner's wording in one language; empty when it has no file. */
    public function texts(string $language): array
    {
        $texts = $this->load($language)['texts'] ?? [];

        return is_array($texts) ? array_filter($texts, 'is_string') : [];
    }

    /** Wording of the shipped categories in one language, keyed by handle. */
    public function categories(string $language): array
    {
        $categories = $this->load($language)['categories'] ?? [];

        return is_array($categories) ? $categories : [];
    }

    private function load(string $language): array
    {

        if (!preg_match('/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $language)) {
            return [];
        }

        if (isset($this->loaded[$language])) {
            return $this->loaded[$language];
        }

        $wording = [];

        foreach ([Paths::lang(), ...$this->siteDirs] as $dir) {
            $file = $dir . '/' . $language . '.php';

            if (is_file($file) && is_array($data = require $file)) {
                $wording = array_replace_recursive($wording, $data);
            }
        }

        return $this->loaded[$language] = $wording;
    }
}
