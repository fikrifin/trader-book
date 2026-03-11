<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-6">
        <div class="w-full rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Error 404</p>
            <h1 class="mt-2 text-3xl font-bold">Halaman Tidak Ditemukan</h1>
            <p class="mt-3 text-sm text-gray-600">URL yang kamu akses tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Kembali ke Dashboard</a>
        </div>
    </main>
</body>
</html>
