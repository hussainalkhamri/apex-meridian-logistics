<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تشخيص وإصلاح النظام — Shipmate Diagnostics</title>
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;950&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',
                        brand: '#2563eb',
                        success: '#16a34a',
                        warning: '#ea580c',
                        danger: '#dc2626',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="text-slate-800 py-10 px-4">

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <header class="flex items-center justify-between border-b pb-6">
            <div>
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 border border-blue-150">PRODUCTION HELP DESK</span>
                <h1 class="text-2xl font-black text-slate-900 mt-2">لوحة تشخيص وتأمين العمليات</h1>
                <p class="text-xs text-slate-500 mt-1">فحص حالة الاتصال بقاعدة البيانات، صلاحيات المجلدات، وتصحيح كاش الخادم في استضافة Hostinger.</p>
            </div>
            <a href="/login" class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold hover:bg-slate-50">
                لوحة التحكم الرئيسية
            </a>
        </header>

        <!-- System Checks Grid -->
        <section class="grid gap-4 md:grid-cols-2">
            @foreach($checks as $key => $check)
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4">
                    <!-- Status Icon -->
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg 
                        @if($check['status'] === 'success') bg-green-50 text-green-700 border border-green-150
                        @elseif($check['status'] === 'warning') bg-orange-50 text-orange-700 border border-orange-150
                        @else bg-red-50 text-red-700 border border-red-150 @endif">
                        @if($check['status'] === 'success')
                            <i data-lucide="check-circle" class="h-5 w-5"></i>
                        @elseif($check['status'] === 'warning')
                            <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                        @else
                            <i data-lucide="x-circle" class="h-5 w-5"></i>
                        @endif
                    </span>
                    
                    <div class="space-y-1 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-slate-900">{{ $check['label'] }}</span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 font-bold uppercase tracking-wider">{{ $check['value'] }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ $check['message'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        <!-- Dynamic Fixes Panel -->
        <section class="bg-slate-900 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
            <h2 class="text-base font-extrabold text-white flex items-center gap-2 mb-4">
                <i data-lucide="wrench" class="h-5 w-5 text-blue-500"></i>
                <span>لوحة التحكم في الحلول التلقائية (Hotfixes Dashboard)</span>
            </h2>
            <p class="text-xs text-slate-400 mb-6">
                قم باختيار الإجراء المطلوب وسيقوم الخادم بتنفيذه وعرض الناتج التفصيلي فوراً.
            </p>

            <div class="grid gap-3 sm:grid-cols-3">
                <button onclick="runFix('clear_cache')" class="h-11 bg-white/10 hover:bg-white/15 border border-white/10 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2">
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                    <span>تحديث ومسح الكاش</span>
                </button>
                <button onclick="runFix('run_seeders')" class="h-11 bg-amber-650 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2">
                    <i data-lucide="database-backup" class="h-4 w-4"></i>
                    <span>تغذية الأدوار والمستخدمين</span>
                </button>
                <button onclick="runFix('run_migrations')" class="h-11 bg-blue-650 hover:bg-blue-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2">
                    <i data-lucide="play" class="h-4 w-4"></i>
                    <span>تشغيل الهجرات (Migrate)</span>
                </button>
            </div>

            <!-- Terminal Output -->
            <div class="mt-6 border border-white/10 rounded-xl bg-black/60 overflow-hidden">
                <div class="flex items-center justify-between bg-black/40 px-4 py-2 border-b border-white/5 text-[10px] font-mono text-slate-400">
                    <span>مخرج منفذ الأوامر (Terminal Output)</span>
                    <button onclick="clearTerminal()" class="hover:text-white">Clear</button>
                </div>
                <pre id="terminal" class="p-4 text-xs font-mono text-green-400 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap">-- في انتظار اختيار أمر لتنفيذه --</pre>
            </div>
        </section>
    </div>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        if (window.lucide) window.lucide.createIcons();

        function runFix(action) {
            const term = document.getElementById('terminal');
            term.innerText = `> Running Artisan Action: ${action} ... Please wait...`;
            term.className = "p-4 text-xs font-mono text-blue-400 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap";

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/diagnose/fix', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ action: action })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    term.innerText = data.output;
                    term.className = "p-4 text-xs font-mono text-green-400 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap";
                } else {
                    term.innerText = `[ERROR]: ${data.error || 'Unknown Error'}`;
                    term.className = "p-4 text-xs font-mono text-red-400 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap";
                }
            })
            .catch(err => {
                term.innerText = `[FAILED CONNECTION]: ${err.message}`;
                term.className = "p-4 text-xs font-mono text-red-500 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap";
            });
        }

        function clearTerminal() {
            document.getElementById('terminal').innerText = '-- في انتظار اختيار أمر لتنفيذه --';
            document.getElementById('terminal').className = "p-4 text-xs font-mono text-green-400 overflow-x-auto min-h-[120px] max-h-[300px] leading-relaxed whitespace-pre-wrap";
        }
    </script>
</body>
</html>
