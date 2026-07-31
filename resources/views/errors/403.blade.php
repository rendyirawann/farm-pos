@extends('errors.layout')
@section('code', '403')
@section('title', 'Akses Ditolak')
@section('icon', '🔒')
@section('message', 'Kamu tidak punya izin untuk membuka halaman ini. Hubungi Owner/Admin bila menurutmu ini keliru.')
@section('actions')
    <a href="/admin/dashboard" class="btn btn-primary">Ke Dashboard</a>
    <button class="btn btn-ghost" onclick="history.back()">Kembali</button>
@endsection
