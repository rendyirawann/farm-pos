@extends('errors.layout')
@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')
@section('icon', '🧭')
@section('message', 'Halaman yang kamu cari tidak ada, sudah dipindahkan, atau alamatnya salah ketik.')
@section('actions')
    <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary">Ke Dashboard</a>
    <button class="btn btn-ghost" onclick="history.back()">Kembali</button>
@endsection
