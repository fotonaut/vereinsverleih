<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Vereinsverleih') }}</title>
        <meta name="description" content="Vereinsinventar teilen: Vereine tragen ihr Inventar ein und verleihen es an andere Vereine oder Privatpersonen.">

        <link rel="icon" href="/favicon.ico" sizes="48x48">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#1f6f5c">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Vereinsverleih">
        <meta property="og:title" content="Vereinsverleih – Was ein Verein hat, kann der nächste leihen">
        <meta property="og:description" content="Vereinsinventar teilen, anfragen und verwalten. Kostenlos und Open Source.">
        <meta property="og:image" content="{{ url('/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
