<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('departments')) {
            return;
        }

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name');
            // Hex, chosen from the palette on the model. Stored rather than
            // derived from the name so it stays put when a department is renamed.
            $table->string('color', 7);
            $table->timestamps();

            // Departments belong to one organization, and two of the same name
            // inside it would be indistinguishable in the sidebar.
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};
