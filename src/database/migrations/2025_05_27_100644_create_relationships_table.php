<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelationshipsTable extends Migration
{
    public function up()
    {
        Schema::create('relationships', function (Blueprint $table) {
            $table->id(); // 主キーを自動生成（Railsのidに相当）
            $table->unsignedBigInteger('follower_id');
            $table->unsignedBigInteger('followed_id');
            $table->timestamps();

            // 外部キー制約（もしusersテーブルが存在すれば）
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('followed_id')->references('id')->on('users')->onDelete('cascade');

            // follower_id と followed_id の組み合わせはユニークにしたいなら
            $table->unique(['follower_id', 'followed_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('relationships');
    }
}
