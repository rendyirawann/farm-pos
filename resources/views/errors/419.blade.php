@extends('errors.layout')
@section('code', '419')
@section('title', 'Sesi Kedaluwarsa')
@section('icon', '⏳')
@section('message', 'Demi keamanan, sesi Anda sudah berakhir. Silakan masuk kembali untuk melanjutkan.')
@section('actions')
    {{-- URL RELATIF supaya tetap di host/subdomain yang sedang dibuka (mis. laundry.mooda.id).
         "Muat ulang" dihapus: 419 terjadi pada POST, reload hanya mengulang POST yang sama
         dan tetap gagal — yang dibutuhkan user adalah login ulang. --}}
    <a href="/admin/login" class="btn btn-primary">Masuk Kembali</a>
    <a href="/admin/dashboard" class="btn btn-ghost">Ke Dashboard</a>
@endsection
