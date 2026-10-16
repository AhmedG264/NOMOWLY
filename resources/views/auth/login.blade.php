@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-12rem)] flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Badge -->
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-600/20 mb-3">
                ن
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                تسجيل الدخول إلى نمولي
            </h1>
            <p class="mt-2 text-sm text-slate-600 font-medium">
                سوق التسويق بالأداء الإقليمي — مرحباً بك مجدداً
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80">
            <!-- Session Status Alert -->
            @if (session('status'))
                <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-medium flex items-center gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-black">✓</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Warning Alert -->
            @if (session('warning'))
                <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 font-medium flex items-center gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">!</span>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            <!-- Error Alerts -->
            @if ($errors->any())
                <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-medium">
                    <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-900">
                        <span>⚠️</span>
                        <span>تعذر إتمام تسجيل الدخول:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pr-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        البريد الإلكتروني
                    </label>
                    <div class="relative">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            placeholder="name@example.com"
                            class="block w-full px-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-rose-300 ring-1 ring-rose-300 bg-rose-50/20' : 'border-slate-200 bg-slate-50/40' }} text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            كلمة المرور
                        </label>
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                            نسيت كلمة المرور؟
                        </a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            placeholder="••••••••"
                            class="block w-full px-4 py-3 rounded-xl border {{ $errors->has('password') ? 'border-rose-300 ring-1 ring-rose-300 bg-rose-50/20' : 'border-slate-200 bg-slate-50/40' }} text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input id="remember" name="remember" type="checkbox"
                            class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        <span class="text-xs text-slate-600 font-medium">تذكر تسجيل دخولي على هذا المتصفح</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-sm font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 cursor-pointer">
                        <span>تسجيل الدخول</span>
                        <span>←</span>
                    </button>
                </div>
            </form>

            <!-- Bottom Register Link -->
            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-600 font-medium">
                    ليس لديك حساب في المنصة بعد؟
                    <a href="{{ route('register') }}" class="font-bold text-emerald-600 hover:text-emerald-700 underline underline-offset-4 mr-1">
                        إنشاء حساب جديد
                    </a>
                </p>
            </div>
        </div>

        <!-- Security Note -->
        <div class="mt-6 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200/80 text-[11px] text-slate-500 font-medium">
                <span>🔒</span>
                <span>جلسة مشفرة ومحمية ببروتوكولات الأمان القياسية لمنصة نمولي</span>
            </div>
        </div>
    </div>
</div>
@endsection
