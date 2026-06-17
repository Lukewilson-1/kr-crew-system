<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'KR Crew System') }}</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
  @yield('content')
  <script type="module" src="{{ asset('js/mysql.js') }}"></script>
  <script type="module" src="{{ asset('js/helpers.js') }}"></script>
  <script type="module" src="{{ asset('js/constants.js') }}"></script>
  <script type="module" src="{{ asset('js/app.js') }}"></script>
</body>
</html>
