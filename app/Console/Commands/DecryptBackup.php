<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupEncryptor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('backup:decrypt {input : Encrypted backup file (.dump.enc)} {output : Where to write the decrypted pg_dump file} {--private-key-file= : File containing the private backup key}')]
#[Description('Decrypt a downloaded backup so it can be restored with pg_restore')]
class DecryptBackup extends Command
{
    public function handle(BackupEncryptor $encryptor): int
    {
        $inputArgument = $this->argument('input');
        $outputArgument = $this->argument('output');
        $input = is_string($inputArgument) ? $inputArgument : '';
        $output = is_string($outputArgument) ? $outputArgument : '';
        $keyFile = $this->option('private-key-file');

        if (! is_file($input)) {
            $this->error('The input file does not exist.');

            return self::FAILURE;
        }
        if ($output === '' || file_exists($output) || is_link($output)) {
            $this->error('Choose an output path that does not already exist.');

            return self::FAILURE;
        }
        if (! is_string($keyFile) || ! is_file($keyFile)) {
            $this->error('Pass --private-key-file=/path/to/key.');

            return self::FAILURE;
        }

        try {
            $encryptor->decryptFile($input, $output, trim((string) file_get_contents($keyFile)));
        } catch (Throwable $throwable) {
            @unlink($output);
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        chmod($output, 0600);
        $this->info("Decrypted to {$output}. Restore with: pg_restore --no-owner --dbname=<database> {$output}");
        $this->warn('The decrypted file is plaintext customer data. Delete it as soon as the restore is done.');

        return self::SUCCESS;
    }
}
