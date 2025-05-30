<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'title',
		'description',
		'image_url'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function songs()
	{
		return $this->belongsToMany(Song::class, 'playlist_songs')
			->withPivot('position')
			->orderBy('position');
	}
}
