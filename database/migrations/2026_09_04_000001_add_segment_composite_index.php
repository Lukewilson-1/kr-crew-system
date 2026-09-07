<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index to speed up the daily segment lookups performed by
        // the roster/monthly views and CrewStatusService::syncSegments().
        Schema::table('crew_status_segments', function (Blueprint $table) {
            $index = $table->getTable().'_crew_month_day_index';
            $columns = ['crew_record_id', 'month_key', 'day'];
            if (! $this->indexExists($table->getTable(), $index)) {
                $table->index($columns, $index);
            }
        });
    }

    public function down(): void
    {
        Schema::table('crew_status_segments', function (Blueprint $table) {
            $table->dropIndex($table->getTable().'_crew_month_day_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return collect($connection->select('PRAGMA index_list('.$connection->getQueryGrammar()->wrapTable($table).')'))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $row = $connection->selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $index]
            );

            return (int) ($row->c ?? 0) > 0;
        }

        $row = $connection->selectOne(
            'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            [$table, $index]
        );

        return $row !== null;
    }
};
