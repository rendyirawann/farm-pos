@extends('errors.layout')
@section('code', '402')
@section('title', 'Pembayaran Diperlukan')
@section('icon', '💳')
@section('message', 'Fitur ini memerlukan langganan aktif. Perbarui paket langgananmu untuk terus menggunakan Mooda.')
@section('actions')
    <a href="{{ url('/admin/billing') }}" class="btn btn-primary">Perbarui Langganan</a>
    <a href="{{ url('/admin/dashboard') }}" class="btn btn-ghost">Ke Dashboard</a>
@endsection
