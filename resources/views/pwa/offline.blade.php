<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Internet — Eltiz BM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#111827">
    <style>* { font-family: -apple-system, 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="text-center max-w-sm">
        <div class="text-6xl mb-4">📶</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">No Internet Connection</h1>
        <p class="text-gray-500 mb-6 text-sm leading-relaxed">
            Internet connection নেই। Connected হওয়ার পর page টি reload করুন।
        </p>
        <button
            onclick="window.location.reload()"
            class="bg-gray-900 text-white px-6 py-3 rounded-xl font-semibold text-sm active:scale-95 transition-transform">
            🔄 Try Again
        </button>
    </div>
</body>
</html>
