<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
use App\Services\Backup\BackupEncryptor;

beforeEach(function () {
    $this->dir = sys_get_temp_dir() . '/flbk-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->encryptor = new BackupEncryptor();
    $this->keys = $this->encryptor->generateKeyPair();
});

afterEach(function () {
    array_map('unlink', glob($this->dir . '/*') ?: []);
    rmdir($this->dir);
});

test('a file survives an encrypt and decrypt round trip across several chunks', function () {
    $plain = random_bytes(2_500_000);
    file_put_contents("{$this->dir}/in", $plain);

    $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", $this->keys['public']);
    $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $this->keys['private']);

    expect(file_get_contents("{$this->dir}/out"))->toBe($plain)
        ->and(file_get_contents("{$this->dir}/enc"))->not->toContain(substr($plain, 0, 64));
});
test('an empty file round trips', function () {
    file_put_contents("{$this->dir}/in", '');

    $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", $this->keys['public']);
    $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $this->keys['private']);

    expect(file_get_contents("{$this->dir}/out"))->toBe('');
});
test('the public key alone cannot decrypt', function () {
    file_put_contents("{$this->dir}/in", 'secret');
    $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", $this->keys['public']);

    $other = $this->encryptor->generateKeyPair();

    expect(fn() => $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $other['private']))
        ->toThrow(RuntimeException::class, 'Wrong private key');
});
test('tampering with the ciphertext is detected', function () {
    file_put_contents("{$this->dir}/in", random_bytes(5000));
    $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", $this->keys['public']);

    $bytes = file_get_contents("{$this->dir}/enc");
    $bytes[strlen($bytes) - 20] = $bytes[strlen($bytes) - 20] ^ "\x01";
    file_put_contents("{$this->dir}/enc", $bytes);

    expect(fn() => $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $this->keys['private']))
        ->toThrow(RuntimeException::class);
});
test('a truncated backup is rejected', function () {
    file_put_contents("{$this->dir}/in", random_bytes(2_200_000));
    $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", $this->keys['public']);

    $bytes = file_get_contents("{$this->dir}/enc");
    file_put_contents("{$this->dir}/enc", substr($bytes, 0, intdiv(strlen($bytes), 2)));

    expect(fn() => $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $this->keys['private']))
        ->toThrow(RuntimeException::class);
});
test('a file that is not a backup is rejected', function () {
    file_put_contents("{$this->dir}/enc", 'hello world, not a backup');

    expect(fn() => $this->encryptor->decryptFile("{$this->dir}/enc", "{$this->dir}/out", $this->keys['private']))
        ->toThrow(RuntimeException::class, 'Not a Flow Ledger backup');
});
test('a malformed key is reported clearly', function () {
    file_put_contents("{$this->dir}/in", 'x');

    expect(fn() => $this->encryptor->encryptFile("{$this->dir}/in", "{$this->dir}/enc", 'not-a-key'))
        ->toThrow(RuntimeException::class, 'public backup key');
});
