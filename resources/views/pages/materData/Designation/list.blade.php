@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Designation.dataTable')
@endsection

@section('model')
    @include('pages.materData.Designation.create')
    @include('pages.materData.Designation.update')
@endsection
