<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacySqliteData extends Command
{
    protected $signature = 'data:import-sqlite
        {path : Absolute path to the old database.sqlite file}
        {--connection= : Destination connection to import into (defaults to the app\'s default connection)}';

    protected $description = 'Copy shop data (users, items, stock-ins, sales) from an old SQLite database file into the current database, preserving IDs, so it does not need to be re-entered after switching database engines.';

    /** Import order matters: items before the tables that reference item_id. */
    private const TABLES = ['users', 'items', 'stock_ins', 'sales'];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("No SQLite file found at: {$path}");

            return self::FAILURE;
        }

        $destinationName = $this->option('connection') ?: config('database.default');

        config(['database.connections.legacy_sqlite' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        $source = DB::connection('legacy_sqlite');
        $target = DB::connection($destinationName);
        $isMysql = $target->getDriverName() === 'mysql';

        $this->info("Importing from [{$path}] into the '{$destinationName}' connection...");

        if ($isMysql) {
            $target->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach (self::TABLES as $table) {
            if (! Schema::connection('legacy_sqlite')->hasTable($table)) {
                $this->warn("  {$table}: not present in the source file, skipping.");

                continue;
            }

            $rows = $source->table($table)->orderBy('id')->get();

            if ($rows->isEmpty()) {
                $this->line("  {$table}: nothing to import.");

                continue;
            }

            $target->table($table)->truncate();

            foreach ($rows->chunk(200) as $chunk) {
                $target->table($table)->insert(
                    $chunk->map(fn ($row) => (array) $row)->all()
                );
            }

            if ($isMysql) {
                $maxId = (int) $rows->max('id');
                $target->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = ".($maxId + 1));
            }

            $this->info("  {$table}: imported {$rows->count()} row(s).");
        }

        if ($isMysql) {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Done. Your existing items, stock-ins, sales and login are now in the new database.');

        return self::SUCCESS;
    }
}
