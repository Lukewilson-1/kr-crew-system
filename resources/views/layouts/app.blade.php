<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ config('app.name', 'KR Crew System') }}</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
  <script>
    window.FIREBASE_CONFIG = {!! json_encode($firebaseConfig ?? []) !!};
  </script>
</head>
<body>
  @yield('content')
  <script type="module" src="{{ asset('js/firebase.js') }}"></script>
  <script type="module" src="{{ asset('js/helpers.js') }}"></script>
  <script type="module" src="{{ asset('js/constants.js') }}"></script>
  <script type="module" src="{{ asset('js/app.js') }}"></script>
</body>
</html>
