<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تشخيص النظام — دخول آمن</title>
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="relative w-full max-w-md bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-2xl shadow-2xl text-white">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black tracking-tight text-white mb-2">لوحة تشخيص النظام</h1>
            <p class="text-xs text-slate-350">الرجاء إدخال رمز الأمان (Security PIN) للوصول إلى لوحة الفحص والمساعدة البرمجية.</p>
        </div>

        @if($error)
            <div class="mb-4 rounded-lg bg-red-500/20 border border-red-500/40 p-3 text-xs text-red-200 text-center font-bold">
                {{ $error }}
            </div>
        @endif

        <form action="/diagnose" method="GET" class="space-y-4">
            <div>
                <label for="pin" class="block text-xs font-bold text-slate-300 mb-1.5">رمز الدخول الآمن (PIN)</label>
                <input type="password" name="pin" id="pin" required placeholder="••••••••" class="w-full h-11 px-4 border border-white/10 bg-black/30 rounded-xl text-sm text-center font-bold tracking-widest text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            </div>

            <button type="submit" class="w-full h-11 bg-blue-600 hover:bg-blue-500 rounded-xl text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center">
                تأكيد الدخول والفحص
            </button>
        </form>

        <div class="mt-6 text-[10px] text-slate-400 text-center">
            * يتم ضبط الرمز من إعداد DIAGNOSTIC_PIN في ملف البيئة.
        </div>
    </div>

</body>
</html>
