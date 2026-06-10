<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELTIZ — Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-white flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-sm">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-1">
                <div class="w-7 h-7 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 4a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1zM2 9a1 1 0 011-1h9a1 1 0 010 2H3a1 1 0 01-1-1zM2 14a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1z"/>
                    </svg>
                </div>
                <span class="text-xl font-black text-gray-900 tracking-tight">ELTIZ</span>
            </div>
        </div>

        <div class="mb-7">
            <h1 class="text-2xl font-bold text-gray-900">Forgot password?</h1>
            <p class="text-sm text-gray-400 mt-1">Enter your email and we'll send a reset link.</p>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-5">
            <p class="text-green-700 text-sm">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5">
            @foreach($errors->all() as $error)
                <p class="text-red-600 text-sm">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                       placeholder="you@example.com">
            </div>
            <button type="submit"
                    class="w-full bg-gray-900 hover:bg-gray-800 active:scale-[.98] text-white font-semibold py-3 rounded-xl transition-all text-sm tracking-wide">
                Send reset link
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-900 font-semibold hover:underline underline-offset-2">&larr; Back to sign in</a>
        </div>
    </div>
</body>
</html>
