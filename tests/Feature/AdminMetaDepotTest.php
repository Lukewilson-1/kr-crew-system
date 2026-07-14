<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminMetaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminMetaDepotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for this test.');
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        app('db')->purge('sqlite');
        app('db')->reconnect();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_depot_deactivation_soft_deletes_record(): void
    {
        $controller = app(AdminMetaController::class);
        $request = $this->makeJsonRequest([
            'label' => 'North Yard',
            'color' => '#123456',
            'restHours' => 8,
            'active' => false,
        ], 'depotMeta', 'NY');

        $response = $controller->normalizedSave($request, 'depotMeta', 'NY');

        $this->assertTrue($response->getData()->ok);

        $row = DB::table('depots')->where('depot_code', 'NY')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
        $this->assertSame('North Yard', $row->depot_name);
    }

    public function test_depot_delete_soft_deletes_record(): void
    {
        $controller = app(AdminMetaController::class);
        $request = $this->makeJsonRequest([
            'label' => 'South Yard',
            'color' => '#654321',
            'restHours' => 10,
            'active' => true,
        ], 'depotMeta', 'SY');

        $controller->normalizedSave($request, 'depotMeta', 'SY');

        $response = $controller->normalizedDelete('depotMeta', 'SY');

        $this->assertTrue($response->getData()->ok);

        $row = DB::table('depots')->where('depot_code', 'SY')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
    }

    protected function makeJsonRequest(array $payload, string $collection, string $id): Request
    {
        return Request::create(
            "/mysql/meta/{$collection}/{$id}",
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
    }
}
