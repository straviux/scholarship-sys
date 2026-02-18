<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ (isset($page_title)?$page_title.' - ':'').config('app.name', 'Laravel') }}</title>
  <!-- Ionicons - Localized -->
  <link rel="stylesheet" href="{{ asset('vendor/cdn/ionicons/css/ionicons.min.css') }}">

  <!-- Google Fonts - Localized -->
  <link rel="stylesheet" href="{{ asset('vendor/cdn/google-fonts/fonts.css') }}">

  <!-- Styles -->
  <!-- Bootstrap CSS - Localized -->
  <link rel="stylesheet" href="{{ asset('vendor/cdn/bootstrap/css/bootstrap.min.css') }}">

  <!-- Font Awesome 6 Free - Localized -->
  <link rel="stylesheet" href="{{ asset('vendor/cdn/font-awesome/css/all.min.css') }}" />

  <!-- jQuery -->
  <script src="{{ asset('vendor/laravel-file-viewer/officetohtml/jquery/jquery.min.js') }}"></script>
</head>

<body class="hold-transition bg-light">
  <!-- <div id="app"></div> -->
  <div class="container-fluid">
    <!-- Content Wrapper. Contains page content -->
    <div class="content pt-1">
      @yield('content')
    </div>
    <!-- /.content-wrapper -->
  </div>
  <!-- ./wrapper -->
</body>
<!-- Popper.js - Localized -->
<script src="{{ asset('vendor/cdn/popper/popper.min.js') }}"></script>
<!-- Bootstrap JS - Localized -->
<script src="{{ asset('vendor/cdn/bootstrap/js/bootstrap.min.js') }}"></script>

</html>