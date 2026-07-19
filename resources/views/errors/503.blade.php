@extends('errors.layout')
@section('code', '503')
@section('title', 'Sedang Pemeliharaan')
@section('icon', '🚧')
@section('message', 'Mooda sedang dalam pemeliharaan singkat untuk peningkatan layanan. Silakan kembali beberapa saat lagi.')
@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
@endsection
