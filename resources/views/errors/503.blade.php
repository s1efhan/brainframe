<!DOCTYPE html>
<html lang="de">

<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="initial-scale=1.0, width=device-width">
  <link rel="icon" href="/favicon.ico">
  @vite('resources/brainframe/css/style.scss')
  @vite('resources/brainframe/js/app.js')
  <style> body {display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; width: 100vw;
            margin: 0;
    overflow: hidden;
}</style>
</head>

<body class="wartungsarbeiten">
    <div class="wartungsarbeiten-container">
<h2>Wartungsarbeiten</h2>
<h3> SERVICE UNAVAILABLE  - <b>503</b></h3>
<p>Currently under construction </p>
</div>
</body>

</html>