<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Behavioral Budget System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-900">

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>