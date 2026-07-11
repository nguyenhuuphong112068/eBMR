@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.seal.dataTable')
@endsection

@section('model')
    @include('pages.materData.seal.create')
    @include('pages.materData.seal.update')
@endsection
