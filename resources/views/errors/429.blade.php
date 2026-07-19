@extends('errors.layout')
@section('code', '429')
@section('title', 'Terlalu Banyak Permintaan')
@section('icon', '🐢')
@section('message', 'Kamu mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, lalu coba lagi.')
@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">Coba Lagi</button>
@endsection
