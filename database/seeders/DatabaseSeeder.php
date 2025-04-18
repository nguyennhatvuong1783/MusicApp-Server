<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		// User::factory(10)->create();

		DB::table('users')->insert([[
			'username' => 'admin',
			'email' => 'admin@gmail.com',
			'password' => Hash::make('admin'),
			'phone' => '0987654321',
			'account_type' => 'admin',
			'is_active' => true
		], [
			'username' => 'user',
			'email' => 'user@gmail.com',
			'password' => Hash::make('user'),
			'phone' => '0987654312',
			'account_type' => 'free',
			'is_active' => true
		]]);

		DB::table('genres')->insert([[
			'name' => 'Pop',
			'description' => 'A genre of popular music that originated in its modern form in the United States and the United Kingdom during the late 1950s and early 1960s.',
		], [
			'name' => 'Rock',
			'description' => 'A genre of popular music that originated as "rock and roll" in the United States in the late 1940s and early 1950s.',
		], [
			'name' => 'Hip-Hop',
			'description' => 'A genre of popular music developed in the United States by inner-city African Americans and Latino Americans in the Bronx borough of New York City in the 1970s.',
		], [
			'name' => 'R&B',
			'description' => 'A genre of popular music that originated in African American communities in the 1940s.',
		], [
			'name' => 'Jazz',
			'description' => 'A music genre that originated in the African American communities of New Orleans, United States, in the late 19th and early 20th centuries.',
		], [
			'name' => 'Classical',
			'description' => 'A broad term that usually refers to music produced or rooted in the traditions of Western culture, including both liturgical and secular music.',
		]]);

		DB::table('artists')->insert([[
			'name' => 'Sơn Tùng M-TP',
			'biography' => 'Sơn Tùng M-TP is a Vietnamese singer, songwriter, and actor. He is known for his unique style and has gained a massive following in Vietnam and beyond.',
			'image_url' => 'https://i.scdn.co/image/ab6761610000f1785a79a6ca8c60e4ec1440be53',
		], [
			'name' => 'HIEUTHUHAI',
			'biography' => 'HIEUTHUHAI is a Vietnamese rapper and singer known for his unique style and powerful lyrics. He has gained popularity in the Vietnamese hip-hop scene.',
			'image_url' => 'https://i.scdn.co/image/ab6761610000517421942907035a43a2d118c55c',
		], [
			'name' => 'Dương Domic',
			'biography' => 'Dương Domic is a Vietnamese singer and songwriter known for his soulful voice and emotional performances. He has a growing fanbase in Vietnam.',
			'image_url' => 'https://i.scdn.co/image/ab67616100005174352d5672d70464e67c3ae963',
		], [
			'name' => 'SOOBIN',
			'biography' => 'SOOBIN is a Vietnamese singer and songwriter known for his catchy melodies and engaging performances. He has made a name for himself in the Vietnamese music industry.',
			'image_url' => 'https://i.scdn.co/image/ab67616100005174b9c9e23c646125922719489e',
		], [
			'name' => 'ERIK',
			'biography' => 'ERIK is a Vietnamese singer and songwriter known for his unique voice and emotional ballads. He has gained popularity in the Vietnamese pop music scene.',
			'image_url' => 'https://i.scdn.co/image/ab67616100005174916407e907705dc1ab9010c3',
		]]);

		DB::table('albums')->insert([[
			'title' => 'm-tp M-TP',
			'artist_id' => 1,
			'image_url' => 'https://i.scdn.co/image/ab67616d00001e02794744c57c9f35db88249842',
			'genre_id' => 1,
		], [
			'title' => 'Ai Cũng Phải Bắt Đầu Từ Đâu Đó',
			'artist_id' => 2,
			'image_url' => 'https://i.scdn.co/image/ab67616d00001e02c006b0181a3846c1c63e178f',
			'genre_id' => 2,
		], [
			'title' => 'Dữ Liệu Quý',
			'artist_id' => 3,
			'image_url' => 'https://i.scdn.co/image/ab67616d00001e02aa8b2071efbaa7ec3f41b60b',
			'genre_id' => 3,
		], [
			'title' => 'BẬT NÓ LÊN',
			'artist_id' => 4,
			'image_url' => 'https://i.scdn.co/image/ab67616d00001e028bdbdf691a5b791a5afb515b',
			'genre_id' => 4,
		], [
			'title' => 'Dù Cho Tận Thế',
			'artist_id' => 5,
			'image_url' => 'https://i.scdn.co/image/ab67616d00001e02f2247e7271ee0ecd57d96f62',
			'genre_id' => 5,
		]]);

		DB::table('songs')->insert([[
			'title' => 'Cơn Mưa Ngang Qua',
			'album_id' => 1,
			'duration' => 288,
			'file_url' => 'https://res.cloudinary.com/dpzt1mkbh/video/upload/v1744629130/ConMuaNgangQua-SonTungMTP-1142953_tezia4.mp3',
			'release_date' => '2023-01-01',
		], [
			'title' => 'Anh Sai Rồi',
			'album_id' => 1,
			'duration' => 252,
			'file_url' => 'https://res.cloudinary.com/dpzt1mkbh/video/upload/v1744620656/AnhSaiRoi-SonTungMTP-2647024_khmtgm.mp3',
			'release_date' => '2023-02-01',
		], [
			'title' => 'Nắng Ấm Xa Dần',
			'album_id' => 1,
			'duration' => 188,
			'file_url' => 'https://res.cloudinary.com/dpzt1mkbh/video/upload/v1744629165/NangAmXaDan-SonTungMTP-2697291_a68rzt.mp3',
			'release_date' => '2023-03-01',
		]]);

		DB::table('songs_artists')->insert([[
			'song_id' => 1,
			'artist_id' => 1,
		], [
			'song_id' => 2,
			'artist_id' => 1,
		], [
			'song_id' => 3,
			'artist_id' => 1,
		]]);

		DB::table('song_genres')->insert([[
			'song_id' => 1,
			'genre_id' => 1,
		], [
			'song_id' => 1,
			'genre_id' => 2,
		], [
			'song_id' => 2,
			'genre_id' => 3,
		], [
			'song_id' => 2,
			'genre_id' => 4,
		], [
			'song_id' => 3,
			'genre_id' => 5,
		], [
			'song_id' => 3,
			'genre_id' => 6,
		]]);

		DB::table('playlists')->insert([[
			'title' => 'Playlist 1',
			'description' => 'A collection of my favorite songs.',
			'image_url' => 'https://cdn.dribbble.com/userupload/20851422/file/original-b82fd38c350d47a4f8f4e689f609993a.png',
			'user_id' => 1,
		], [
			'title' => 'Playlist 2',
			'description' => 'Chill Vibes',
			'image_url' => 'https://cdn.dribbble.com/userupload/20851422/file/original-b82fd38c350d47a4f8f4e689f609993a.png',
			'user_id' => 2,
		]]);

		DB::table('playlist_songs')->insert([[
			'playlist_id' => 1,
			'song_id' => 1,
			'position' => 1,
		], [
			'playlist_id' => 1,
			'song_id' => 2,
			'position' => 2,
		], [
			'playlist_id' => 2,
			'song_id' => 3,
			'position' => 1,
		], [
			'playlist_id' => 2,
			'song_id' => 1,
			'position' => 2,
		]]);
	}
}
