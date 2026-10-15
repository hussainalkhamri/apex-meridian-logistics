<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocalizeStaticHtml
{
    /**
     * Translate legacy hard-coded Arabic Blade copy when the English locale is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->getLocale() !== 'en' || $request->expectsJson()) {
            return $response;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '' || ! str_contains($content, '<html')) {
            return $response;
        }

        $localized = str_replace(
            '<html lang="ar" dir="rtl">',
            '<html lang="en" dir="ltr">',
            $content
        );

        $translations = $this->translations();

        if ($translations !== []) {
            $localized = $this->applyTranslations($localized, $translations);
        }

        if ($localized !== $content) {
            $response->setContent($localized);
            $response->headers->remove('Content-Length');
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function translations(): array
    {
        static $translations = null;

        if ($translations !== null) {
            return $translations;
        }

        $translations = [];
        $arabicMessages = Lang::get('messages', [], 'ar');
        $englishMessages = Lang::get('messages', [], 'en');

        if (is_array($arabicMessages) && is_array($englishMessages)) {
            foreach ($arabicMessages as $key => $source) {
                $target = $englishMessages[$key] ?? null;

                if (! is_string($source) || ! is_string($target) || $source === '' || $source === $target) {
                    continue;
                }

                $translations[$source] = $target;
            }
        }

        foreach (['static', 'static-extra'] as $group) {
            $staticTranslations = Lang::get($group, [], 'en');

            if (! is_array($staticTranslations)) {
                continue;
            }

            foreach ($staticTranslations as $source => $target) {
                if (! is_string($source) || ! is_string($target) || $source === '' || $source === $target) {
                    continue;
                }

                $translations[$source] = $target;
            }
        }

        uksort(
            $translations,
            fn (string $a, string $b): int => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8')
        );

        return $translations;
    }

    /**
     * @param array<string, string> $translations
     */
    private function applyTranslations(string $content, array $translations): string
    {
        foreach ($translations as $source => $target) {
            if (! preg_match('/\p{Arabic}/u', $source)) {
                $content = str_replace($source, $target, $content);

                continue;
            }

            [$prefix, $suffix] = $this->arabicBoundaries($source);
            $pattern = '/' . $prefix . preg_quote($source, '/') . $suffix . '/u';
            $translated = preg_replace($pattern, $target, $content);

            if (is_string($translated)) {
                $content = $translated;
            }
        }

        return $content;
    }

    /**
     * Short Arabic fragments such as "لا توجد" or "النظام" should only be
     * translated when they stand alone, otherwise they create mixed phrases.
     *
     * @return array{0: string, 1: string}
     */
    private function arabicBoundaries(string $source): array
    {
        $startsWithArabic = preg_match('/^\p{Arabic}/u', $source);
        $endsWithArabic = preg_match('/\p{Arabic}$/u', $source);

        $prefix = $startsWithArabic ? '(?<!\p{Arabic})' : '';
        $suffix = $endsWithArabic ? '(?!\p{Arabic})' : '';

        if (preg_match('/^[\p{Arabic}\s]+$/u', $source)) {
            $wordCount = count(preg_split('/\s+/u', trim($source), -1, PREG_SPLIT_NO_EMPTY));

            if ($wordCount <= 2) {
                $prefix = $startsWithArabic ? '(?<!\p{Arabic}\s)(?<!\p{Arabic})' : '';
                $suffix = $endsWithArabic ? '(?!\p{Arabic})(?!\s\p{Arabic})' : '';
            }
        }

        return [$prefix, $suffix];
    }
}
