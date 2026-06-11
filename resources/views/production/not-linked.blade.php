<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">স্বাগতম, {{ $userName }}!</h2>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            আপনার লগইন এখনো কোনো কর্মী-প্রোফাইলের সাথে যুক্ত হয়নি। আপনার ম্যানেজার/অ্যাডমিনকে
            <span class="font-medium text-gray-700">Employees</span> থেকে আপনার অ্যাকাউন্ট যুক্ত করতে বলুন।
        </p>
        <p class="text-xs text-gray-400 mt-3">যুক্ত হয়ে গেলে এই পেজ রিফ্রেশ করলেই ড্যাশবোর্ড দেখতে পাবেন।</p>

        <div class="flex items-center justify-center gap-3 mt-6">
            <a href="{{ route('production.worker.dashboard') }}"
               class="px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors">
                আবার চেষ্টা করুন
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2.5 text-sm font-medium bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-colors">
                    লগআউট
                </button>
            </form>
        </div>
    </div>
</body>
</html>
