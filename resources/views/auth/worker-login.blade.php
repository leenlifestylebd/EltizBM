<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">কর্মী লগইন</h1>
            <p class="text-sm text-gray-500 mt-1">ফোন নম্বর ও PIN দিন</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            @if($errors->any())
            <div class="mb-4 px-3 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('worker.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ফোন নম্বর</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required autofocus
                           inputmode="numeric" placeholder="01XXXXXXXXX"
                           class="w-full px-4 py-3 text-lg text-center tracking-wide border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">PIN</label>
                    <input type="password" name="pin" required
                           inputmode="numeric" maxlength="6" placeholder="••••"
                           class="w-full px-4 py-3 text-2xl text-center tracking-[0.4em] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3.5 rounded-xl text-base transition-colors">
                    লগইন
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-400 mt-6">
            অফিস স্টাফ? <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:underline">এখানে লগইন করুন</a>
        </p>
    </div>

</body>
</html>
