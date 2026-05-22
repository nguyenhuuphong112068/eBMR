@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.market.dataTable')
@endsection

@section('model')
    @include('pages.materData.market.create')
    @include('pages.materData.market.update')
@endsection
