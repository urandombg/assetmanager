@extends('layouts.main')
@section('content')
<div class="row">
    <h1>
        Активи
    </h1>
</div>
<b-container fluid>
    <a href="/assets/create" class="btn btn-dark">Добави</a>
</b-container>
    <b-container fluid>
        <asset-list></asset-list>
    </b-container>
@endsection
