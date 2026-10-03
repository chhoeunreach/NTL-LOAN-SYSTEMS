<?php

namespace Database\Seeders;

use App\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the `systems` key/value flags that the application reads on boot.
 *
 * `loanmanagement_version` marks the module as installed and is checked by
 * InstallController, ModuleUtil and the module updater.
 */
class FirstSetupSystemPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('systems')) {
            $this->command?->warn('systems table not found, skipping system properties.');

            return;
        }

        System::setProperty('loanmanagement_version', (string) config('loanmanagement.version', '1.0.0'));

        foreach ($this->optionalProperties() as $key => $value) {
            if (System::getProperty($key) === null) {
                System::setProperty($key, $value);
            }
        }

        $this->command?->info('System properties seeded: loanmanagement_version, '.implode(', ', array_keys($this->optionalProperties())).'.');
    }

    /**
     * Flags that are only written when absent, so re-seeding never clobbers an
     * operator's configuration.
     */
    protected function optionalProperties(): array
    {
        return [
            'enable_business_based_username' => '0',
        ];
    }
}