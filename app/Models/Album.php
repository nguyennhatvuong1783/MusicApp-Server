<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
	use HasFactory;

	protected $fillable = [
		'artist_id',
		'title',
		'release_date',
		'image_url',
		'genre_id',
		'description'
	];

	public function artist()
	{
		return $this->belongsTo(Artist::class);
	}

	public function genre()
	{
		return $this->belongsTo(Genre::class);
	}

	public function songs()
	{
		return $this->hasMany(Song::class);
	}
}
