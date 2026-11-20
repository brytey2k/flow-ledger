<?php

declare(strict_types=1);

use App\Support\UserGuide;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->directory = sys_get_temp_dir() . '/user-guide-search-' . uniqid();
    File::makeDirectory($this->directory);

    $chapters = [
        'approvals' => ['Approval Workflows', 'Who reviews each request.', 1, 'A cash advance should be approved by someone else.'],
        'cash' => ['Cash Advance & Retirement', 'Taking an advance and retiring it.', 2, 'Retire the cash advance. The cash count balances the cashbook.'],
        'requests' => ['Payment Requests', 'Asking for money.', 3, 'Choose the cash branch that pays the request.'],
    ];

    foreach ($chapters as $slug => [$title, $description, $order, $body]) {
        File::put("{$this->directory}/{$slug}.md", "---\ntitle: \"{$title}\"\ndescription: \"{$description}\"\norder: {$order}\n---\n\n{$body}\n");
    }

    $this->guide = new UserGuide($this->directory);
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

test('the chapter with the phrase in its title comes first', function () {
    expect($this->guide->search('cash advance')->keys()->all())->toBe(['cash', 'approvals']);
});

test('every word must appear, in any order and any case', function () {
    expect($this->guide->search('BRANCH cash')->keys()->all())->toBe(['requests'])
        ->and($this->guide->search('cash payroll')->all())->toBe([]);
});

test('a blank query lists every chapter in guide order', function () {
    expect($this->guide->search('   ')->keys()->all())->toBe(['approvals', 'cash', 'requests']);
});
