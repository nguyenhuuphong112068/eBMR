@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.co.dataTable')
@endsection

@section('model')
  @include('pages.category.co.create')
  @include('pages.category.co.update') 
@endsection
