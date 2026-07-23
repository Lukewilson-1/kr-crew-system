<?php

namespace Tests\Unit;

use App\Filament\Pages\Auth\Login as FilamentLoginPage;
use Filament\Schemas\Schema;
use Tests\TestCase;

class FilamentLoginPageTest extends TestCase
{
    public function test_login_page_override_can_build_schema(): void
    {
        $page = new FilamentLoginPage();
        $schema = new Schema();

        $result = $page->form($schema);

        $this->assertInstanceOf(Schema::class, $result);
    }
}
