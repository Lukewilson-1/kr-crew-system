<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('depots')) {
            Schema::table('depots', function (Blueprint $table) {
                if (! Schema::hasColumn('depots', 'color')) {
                    $table->string('color', 32)->nullable()->after('region');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('depots')) {
            Schema::table('depots', function (Blueprint $table) {
                if (Schema::hasColumn('depots', 'color')) {
                    $table->dropColumn('color');
                }
            });
        }
    }
};
