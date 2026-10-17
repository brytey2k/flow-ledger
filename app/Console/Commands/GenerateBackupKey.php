<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupEncryptor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('backup:generate-key')]
#[Description('Generate the key pair used to encrypt database backups')]
class GenerateBackupKey extends Command
{
    public function handle(BackupEncryptor $encryptor): int
    {
        $pair = $encryptor->generateKeyPair();

        $this->info('Put this on the server (.env):');
        $this->line('BACKUP_ENCRYPTION_PUBLIC_KEY=' . $pair['public']);
        $this->newLine();
        $this->warn('Store this PRIVATE key in a password manager and a sealed offline copy. It is NOT recoverable and must never be on the server:');
        $this->line($pair['private']);
        $this->newLine();
        $this->comment('Without the private key the backups cannot be decrypted, by anyone.');

        return self::SUCCESS;
    }
}
