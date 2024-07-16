<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleTokensTable extends Migration
{
    public function up()
    {
        Schema::create('google_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->json('access_token');
            $table->json('access_info');
            $table->timestamps();
        });
    }
    

    public function down()
    {
        Schema::dropIfExists('google_tokens');
    }
}
