@extends('layouts.main')
@section('content')
    <b-container fluid>
        <asset-details :id="{{ $asset->id }}"></asset-details>
    </b-container>
@endsection
