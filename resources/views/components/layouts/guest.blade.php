@props(["title" => null])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Postbag: self-hosted newsletters with double opt-in, campaigns and one-click unsubscribe.">
    <title>{{ $title ? "$title · " : '' }}Postbag</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-12">
    <main class="w-full max-w-md">{{ $slot }}</main>
</body>
</html>
