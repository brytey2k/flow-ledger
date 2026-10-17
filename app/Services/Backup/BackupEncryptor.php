<?php

declare(strict_types=1);

namespace App\Services\Backup;

use RuntimeException;

/**
 * Streams a file through authenticated encryption (libsodium XChaCha20-Poly1305) with a random
 * per-file key that is sealed to the recipient's public key.
 *
 * The server only ever holds the public key, so it can create backups but not read them. The
 * private key stays offline and is supplied only when restoring.
 *
 * Layout: MAGIC | sealed file key | stream header | repeated (4-byte length | encrypted chunk).
 */
class BackupEncryptor
{
    private const MAGIC = "FLBK1\n";

    private const CHUNK_BYTES = 1048576;

    /**
     * @return array{public: string, private: string} base64 encoded key pair
     */
    public function generateKeyPair(): array
    {
        $pair = sodium_crypto_box_keypair();

        return [
            'public' => base64_encode(sodium_crypto_box_publickey($pair)),
            'private' => base64_encode($pair),
        ];
    }

    public function encryptFile(string $plainPath, string $encryptedPath, string $publicKey): void
    {
        $recipient = $this->decodeKey($publicKey, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, 'public');

        $input = $this->open($plainPath, 'rb');
        $output = $this->open($encryptedPath, 'wb');

        $fileKey = sodium_crypto_secretstream_xchacha20poly1305_keygen();
        /** @var array{0: string, 1: string} $init */
        $init = sodium_crypto_secretstream_xchacha20poly1305_init_push($fileKey);
        [$state, $header] = $init;

        fwrite($output, self::MAGIC . sodium_crypto_box_seal($fileKey, $recipient) . $header);
        sodium_memzero($fileKey);

        $chunk = (string) fread($input, self::CHUNK_BYTES);
        do {
            $next = (string) fread($input, self::CHUNK_BYTES);
            $isLast = $next === '' && feof($input);
            $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push(
                $state,
                $chunk,
                '',
                $isLast ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE,
            );
            fwrite($output, pack('N', strlen($encrypted)) . $encrypted);
            $chunk = $next;
        } while (! $isLast);

        fclose($input);
        fclose($output);
    }

    public function decryptFile(string $encryptedPath, string $plainPath, string $privateKey): void
    {
        $pair = $this->decodeKey($privateKey, SODIUM_CRYPTO_BOX_KEYPAIRBYTES, 'private');

        $input = $this->open($encryptedPath, 'rb');

        // The output is plaintext customer data: create it readable by the owner only, from the first byte.
        $previousUmask = umask(0077);
        try {
            $output = $this->open($plainPath, 'wb');
        } finally {
            umask($previousUmask);
        }
        chmod($plainPath, 0600);

        if (fread($input, strlen(self::MAGIC)) !== self::MAGIC) {
            throw new RuntimeException('Not a Flow Ledger backup file.');
        }

        $sealedLength = SODIUM_CRYPTO_SECRETBOX_KEYBYTES + SODIUM_CRYPTO_BOX_SEALBYTES;
        $fileKey = sodium_crypto_box_seal_open((string) fread($input, $sealedLength), $pair);
        if ($fileKey === false) {
            throw new RuntimeException('Wrong private key for this backup.');
        }

        $header = (string) fread($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $fileKey);
        sodium_memzero($fileKey);

        $sawFinal = false;
        while (! feof($input) && ($prefix = fread($input, 4)) !== '' && $prefix !== false) {
            if ($sawFinal) {
                throw new RuntimeException('Backup has data after its final block.');
            }
            $unpacked = unpack('N', $prefix);
            $length = is_array($unpacked) ? ($unpacked[1] ?? 0) : 0;
            if (! is_int($length) || $length < 1) {
                throw new RuntimeException('Backup is corrupted.');
            }
            $block = (string) fread($input, $length);
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $block);
            if (! is_array($result) || ! is_string($result[0] ?? null)) {
                throw new RuntimeException('Backup is corrupted or has been tampered with.');
            }
            $plain = $result[0];
            $tag = $result[1] ?? null;
            fwrite($output, $plain);
            $sawFinal = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
        }

        fclose($input);
        fclose($output);

        if (! $sawFinal) {
            throw new RuntimeException('Backup is truncated.');
        }
    }

    private function decodeKey(string $encoded, int $expectedBytes, string $label): string
    {
        $key = base64_decode(trim($encoded), true);

        if ($key === false || strlen($key) !== $expectedBytes) {
            throw new RuntimeException("The {$label} backup key is missing or malformed.");
        }

        return $key;
    }

    /**
     * @param string $path
     * @param string $mode
     *
     * @return resource
     */
    private function open(string $path, string $mode)
    {
        $handle = @fopen($path, $mode);

        if ($handle === false) {
            throw new RuntimeException("Cannot open {$path}.");
        }

        return $handle;
    }
}
