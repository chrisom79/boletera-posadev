<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizers')->update(['name' => 'Posadev']);
    }

    public function down(): void
    {
        DB::table('organizers')->update(['name' => 'Christian Gomez']);
    }
};