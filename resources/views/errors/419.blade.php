@extends('errors.layout')
@section('code', '419')
@section('title', 'Sesi Kedaluwarsa')
@section('icon', '⏳')
@section('message', 'Demi keamanan, halaman ini sudah kedaluwarsa (session/token). Muat ulang lalu coba lagi.')
@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
    <a href="{{ url('/admin/dashboard') }}" class="btn btn-ghost">Ke Dashboard</a>
@endsection
