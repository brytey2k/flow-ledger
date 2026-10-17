<?php

declare(strict_types=1);

namespace App\Support;

class MailText
{
    /**
     * Neutralise Markdown syntax in text a user typed or uploaded before it goes into a mail line. Laravel
     * renders mail lines as Markdown, so a filename such as "[Sign in](http:evil.example).pdf" would
     * otherwise become a working link in an email sent from the application's trusted address.
     *
     * @param string|null $text
     */
    public static function plain(string|null $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]()<>#])/', '\\\\$1', (string) $text);
    }
}
