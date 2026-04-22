@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.gf.dataTable')
@endsection

@section('model')
  @include('pages.category.gf.create')
  @include('pages.category.gf.update') 
@endsection
