@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.MaterialRole.dataTable')
@endsection

@section('model')
    @include('pages.materData.MaterialRole.create')
    @include('pages.materData.MaterialRole.update')
@endsection
