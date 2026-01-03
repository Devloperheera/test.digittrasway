<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('web_assets/images/logo.png') }}">
    <title>@yield('title', 'DigiTransway - Home')</title>

    @include('Website.Layout.css')
    @yield('custom_css')
</head>
<body>
    @include('Website.Layout.navbar')
    @include('Website.Layout.sidebar')
        @yield('content')
    @include('Website.Layout.js')
    @yield('custom_js')
</body>
</html>
