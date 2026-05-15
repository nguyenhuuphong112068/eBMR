@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.MaterialSpec.dataTable')
@endsection

@section('model')
    @include('pages.materData.MaterialSpec.create')
    @include('pages.materData.MaterialSpec.update')
@endsection
