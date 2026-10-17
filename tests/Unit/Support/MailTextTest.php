<?php

declare(strict_types=1);

use App\Support\MailText;
use Illuminate\Mail\Markdown;

test('markdown syntax in user text is escaped', function () {
    expect(MailText::plain('[Sign in](http:evil.example).pdf'))->toBe('\[Sign in\]\(http:evil.example\).pdf');
});

test('a link smuggled into a mail line renders as plain text', function () {
    $html = (string) Markdown::parse(MailText::plain('[Sign in](http:evil.example).pdf'));

    expect($html)->not->toContain('<a ')->and($html)->toContain('Sign in');
});

test('null becomes an empty string', function () {
    expect(MailText::plain(null))->toBe('');
});
