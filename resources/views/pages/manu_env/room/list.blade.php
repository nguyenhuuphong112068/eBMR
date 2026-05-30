@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.manu_env.room.dataTable')
@endsection

@section('model')
    @include('pages.manu_env.room.assign')
    @include('pages.manu_env.room.condition')
    @include('pages.manu_env.room.relatedForm')
@endsection
