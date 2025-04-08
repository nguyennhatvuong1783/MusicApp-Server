<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
	use HasFactory;

	protected $fillable = [
		'title',
		'album_id',
		'duration',
		'file_url',
		'release_date'
	];

	public function album()
	{
		return $this->belongsTo(Album::class);
	}

	public function artists()
	{
		return $this->belongsToMany(Artist::class, 'songs_artists');
	}

	public function genres()
	{
		return $this->belongsToMany(Genre::class, 'song_genres');
	}

	public function playlists()
	{
		return $this->belongsToMany(Playlist::class, 'playlist_songs');
	}

	public function history()
	{
		return $this->hasMany(History::class);
	}
}
