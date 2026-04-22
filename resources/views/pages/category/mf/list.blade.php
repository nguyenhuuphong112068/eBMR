@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.mf.dataTable')
@endsection

@section('model')
  @include('pages.category.mf.create')
  @include('pages.category.mf.update') 
@endsection
