<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('genres', function (Blueprint $table) {
			$table->id();
			$table->string('name', 50)->unique();
			$table->text('description')->nullable();
			$table->timestamps();
		});

		Schema::create('artists', function (Blueprint $table) {
			$table->id();
			$table->string('name', 100);
			$table->text('biography')->nullable();
			$table->string('image_url', 255)->nullable();
			$table->timestamps();
		});

		Schema::create('albums', function (Blueprint $table) {
			$table->id();
			$table->foreignId('artist_id')->constrained('artists')->onDelete('cascade');
			$table->string('title', 100);
			$table->date('release_date')->nullable();
			$table->string('image_url', 255)->nullable();
			$table->foreignId('genre_id')->nullable()->constrained('genres')->onDelete('set null');
			$table->text('description')->nullable();
			$table->timestamps();
		});

		Schema::create('songs', function (Blueprint $table) {
			$table->id();
			$table->string('title', 100);
			$table->foreignId('album_id')->nullable()->constrained('albums')->onDelete('set null');
			$table->integer('duration'); // in seconds
			$table->string('file_url');
			$table->date('release_date')->nullable();
			$table->timestamps();
		});

		Schema::create('songs_artists', function (Blueprint $table) {
			$table->foreignId('song_id')->constrained('songs')->onDelete('cascade');
			$table->foreignId('artist_id')->constrained('artists')->onDelete('cascade');
			$table->primary(['song_id', 'artist_id']);
			$table->timestamps();
		});

		Schema::create('playlists', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
			$table->string('title', 100);
			$table->text('description')->nullable();
			$table->string('image_url', 255)->nullable();
			$table->timestamps();
		});

		Schema::create('playlist_songs', function (Blueprint $table) {
			$table->foreignId('playlist_id')->constrained('playlists')->onDelete('cascade');
			$table->foreignId('song_id')->constrained('songs')->onDelete('cascade');
			$table->primary(['playlist_id', 'song_id']);
			$table->integer('position');
			$table->timestamps();
		});

		Schema::create('song_genres', function (Blueprint $table) {
			$table->foreignId('song_id')->constrained('songs')->onDelete('cascade');
			$table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
			$table->primary(['song_id', 'genre_id']);
			$table->timestamps();
		});

		Schema::create('history', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
			$table->foreignId('song_id')->constrained('songs')->onDelete('cascade');
			$table->timestamp('played_at')->useCurrent();
			$table->integer('progress')->nullable(); // in seconds
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('history');
		Schema::dropIfExists('song_genres');
		Schema::dropIfExists('playlist_songs');
		Schema::dropIfExists('playlists');
		Schema::dropIfExists('songs_artists');
		Schema::dropIfExists('songs');
		Schema::dropIfExists('albums');
		Schema::dropIfExists('artists');
		Schema::dropIfExists('genres');
	}
};
