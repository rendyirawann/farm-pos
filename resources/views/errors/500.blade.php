@extends('errors.layout')
@section('code', '500')
@section('title', 'Terjadi Kesalahan')
@section('icon', '🛠️')
@section('message', 'Ada gangguan di sisi server kami. Tim Mooda sudah diberi tahu — silakan coba beberapa saat lagi.')
@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
    <a href="{{ url('/admin/dashboard') }}" class="btn btn-ghost">Ke Dashboard</a>
@endsection
