<?php

namespace App\Services;

class ContactDetectionService
{
    /**
     * @return array{
     *     has_contact_info: bool,
     *     warnings: list<string>,
     *     matches: array{emails: list<string>, phones: list<string>, social: list<string>}
     * }
     */
    public function analyze(string $text): array
    {
        $emails = $this->detectEmails($text);
        $phones = $this->detectPhones($text);
        $social = $this->detectSocialHandles($text);

        $warnings = [];

        if ($emails !== []) {
            $warnings[] = 'Message appears to contain an email address. Keep communication on-platform.';
        }

        if ($phones !== []) {
            $warnings[] = 'Message appears to contain a phone number. Keep communication on-platform.';
        }

        if ($social !== []) {
            $warnings[] = 'Message appears to contain social media contact details. Keep communication on-platform.';
        }

        return [
            'has_contact_info' => $warnings !== [],
            'warnings' => $warnings,
            'matches' => [
                'emails' => $emails,
                'phones' => $phones,
                'social' => $social,
            ],
        ];
    }

    public function containsContactInfo(string $text): bool
    {
        return $this->analyze($text)['has_contact_info'];
    }

    /**
     * @return list<string>
     */
    private function detectEmails(string $text): array
    {
        preg_match_all(
            '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
            $text,
            $matches,
        );

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * @return list<string>
     */
    private function detectPhones(string $text): array
    {
        preg_match_all(
            '/(?:\+?\d{1,3}[\s\-().]?)?(?:\(?\d{2,4}\)?[\s\-.]?)?\d{3}[\s\-.]?\d{4}\b/',
            $text,
            $matches,
        );

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            fn (string $phone) => preg_match('/\d/', $phone) === 1,
        )));
    }

    /**
     * @return list<string>
     */
    private function detectSocialHandles(string $text): array
    {
        $patterns = [
            '/\b(?:https?:\/\/)?(?:www\.)?(?:facebook|fb)\.com\/[^\s]+/i',
            '/\b(?:https?:\/\/)?(?:www\.)?(?:instagram|instagr\.am)\.com\/[^\s]+/i',
            '/\b(?:https?:\/\/)?(?:www\.)?(?:linkedin)\.com\/[^\s]+/i',
            '/\b(?:https?:\/\/)?(?:www\.)?(?:twitter|x)\.com\/[^\s]+/i',
            '/\b(?:https?:\/\/)?(?:www\.)?(?:tiktok)\.com\/[^\s]+/i',
            '/\b(?:https?:\/\/)?(?:www\.)?(?:wa\.me|whatsapp\.com)\/[^\s]+/i',
            '/\b(?:telegram|t\.me)\/[^\s]+/i',
        ];

        $found = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $found = array_merge($found, $matches[0] ?? []);
        }

        return array_values(array_unique($found));
    }
}
