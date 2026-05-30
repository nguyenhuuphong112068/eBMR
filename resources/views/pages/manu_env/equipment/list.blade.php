@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.manu_env.equipment.dataTable')
@endsection

@section('model')
    @include('pages.manu_env.equipment.create')
    @include('pages.manu_env.equipment.update')
@endsection
