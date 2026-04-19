@extends('layout.master')

@section('title', 'eBMR Editor (Document Style)')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <div class="content-wrapper" id="mainContent" style="background-color: #f1f3f4; min-height: 100vh;">
        @include('pages.ebmr.designer.partials.toolbar')
        @include('pages.ebmr.designer.partials.canvas')
    </div>

    @include('pages.ebmr.designer.partials.modals')
    @include('pages.ebmr.designer.partials.styles')

    {{-- Script Modules --}}
    @include('pages.ebmr.designer.scripts.state')
    @include('pages.ebmr.designer.scripts.render')
    @include('pages.ebmr.designer.scripts.table_ops')
    @include('pages.ebmr.designer.scripts.table_advanced')
    @include('pages.ebmr.designer.scripts.ui_handlers')
    @include('pages.ebmr.designer.scripts.persistence')
    @include('pages.ebmr.designer.scripts.events')
@endsection
