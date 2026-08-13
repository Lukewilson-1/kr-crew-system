<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CopyEndOfDayStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crew:copy-end-of-day-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy previous day final status into the new day for crew records if not manually set.';

    public function handle(): int
    {
        $tz = config('app.timezone') ?: date_default_timezone_get();
        $yesterday = new \DateTimeImmutable('yesterday', new \DateTimeZone($tz));
        $today = new \DateTimeImmutable('now', new \DateTimeZone($tz));

        $yKey = $yesterday->format('Y-m');
        $yDay = (int) $yesterday->format('j');
        $tKey = $today->format('Y-m');
        $tDay = (int) $today->format('j');

        $this->info("Copying statuses from {$yKey}-d{$yDay} to {$tKey}-d{$tDay}");

        $rows = DB::table('crew_records')->get();
        foreach ($rows as $row) {
            $recordId = $row->record_id;

            // If today's segment already exists, skip.
            $exists = DB::table('crew_status_segments')
                ->where('crew_record_id', $recordId)
                ->where('month_key', $tKey)
                ->where('day', $tDay)
                ->exists();
            if ($exists) {
                continue;
            }

            // Try to get last segment for yesterday.
            $seg = DB::table('crew_status_segments')
                ->where('crew_record_id', $recordId)
                ->where('month_key', $yKey)
                ->where('day', $yDay)
                ->orderByDesc('sort_order')
                ->orderByDesc('created_at')
                ->first();

            $status = null;
            if ($seg && !empty($seg->status_code)) {
                $status = $seg->status_code;
            } else {
                // Fallback to monthly payload in crew_records
                $payload = json_decode($row->payload ?? '{}', true) ?: [];
                $monthly = $payload['monthly'] ?? [];
                $key = 'd'.$yDay;
                if (isset($monthly[$key]) && $monthly[$key]) {
                    $status = $monthly[$key];
                }
            }

            if (!$status) {
                continue;
            }

            // Insert today's segment copying status
            $payloadMeta = json_encode(['copiedFrom' => "{$yKey}-d{$yDay}", 'auto' => true, 'source' => 'copied_end_of_day']);
            DB::table('crew_status_segments')->insert([
                'segment_id' => (string) Str::uuid(),
                'crew_record_id' => $recordId,
                'crew_id' => $row->crew_id ?? null,
                'depot_code' => $row->depot ?? null,
                'month_key' => $tKey,
                'day' => $tDay,
                'sort_order' => 100,
                'status_code' => $status,
                'train_type' => null,
                'route' => null,
                'book_time' => null,
                'rest_started_at' => null,
                'away_depot' => null,
                'notes' => 'Auto-copied from previous day final status',
                'metadata' => $payloadMeta,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('Done.');
        return 0;
    }
}
