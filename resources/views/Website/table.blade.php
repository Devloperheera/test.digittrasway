@extends('Website.Layout.master')

@section('custom_css')
    <style>
    </style>
@endsection

@section('content')
    <div class="container mt-4">
        <div id="table-section" class="table-container">

            <h2 class="section-title">
                <i class="fas fa-table me-2"></i>
                 Records
            </h2>

            <div class="table-responsive">
                <table id="employeeTable" class="table table-striped table-hover">

                </table>
            </div>

        </div>
    </div>
@endsection

@section('custom_js')
@endsection
