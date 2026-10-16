@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>🛡️</span>
                <span>لوحة التحكم الإدارية العليا (Super Admin Control Center)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">مرحباً، {{ auth()->user()->name }}</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                البريد الإلكتروني المعتمد: {{ auth()->user()->email }} • الدور: <span class="text-emerald-400 font-bold">SUPER_ADMIN</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>📢</span>
                <span>إشراف وإدارة الحملات</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-medium flex items-center gap-2">
            <span class="text-emerald-500 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Key Navigation Hub Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-black">
                    📢
                </div>
                <h3 class="text-base font-black text-slate-900">الإشراف على حملات المنصة</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    مراجعة جميع الحملات المنشورة من قبل العلامات التجارية، تعديل حالات النشر (نشطة، متوقفة، مكتملة)، ومتابعة الطلبات.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('admin.campaigns.index') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition">
                    إدارة الحملات العامة ←
                </a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-black">
                    🛍️
                </div>
                <h3 class="text-base font-black text-slate-900">سوق الحملات المباشر</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    فحص السوق العام للحملات والتحقق من طريقة ظهور العروض ومعدلات التحويل والعمولات للمستخدمين.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('marketplace') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition">
                    معاينة السوق ←
                </a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-700 flex items-center justify-center text-xl font-black">
                    🔒
                </div>
                <h3 class="text-base font-black text-slate-900">أمان الحساب والمصادقة</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    حساب الإدارة العليا محمي بسياسات التشفير القوية، تجديد الجلسات التلقائي، وسجلات التدقيق الصارمة لكافة العمليات الحساسة.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('password.change') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                    تحديث كلمة المرور
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
