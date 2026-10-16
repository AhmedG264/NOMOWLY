@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-12rem)] flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-600/20 mb-3">
                🔐
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                إعادة تعيين كلمة المرور
            </h1>
            <p class="mt-2 text-sm text-slate-600 font-medium">
                قم بإدخال كلمة مرور جديدة وقوية لحسابك
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80">
            <!-- Errors Alert -->
            @if ($errors->any())
                <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-medium">
                    <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-900">
                        <span>⚠️</span>
                        <span>تعذر تحديث كلمة المرور:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pr-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        البريد الإلكتروني
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email) }}"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        كلمة المرور الجديدة
                    </label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        placeholder="8 أحرف على الأقل (أرقام وأحرف)"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        تأكيد كلمة المرور الجديدة
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        placeholder="أعد إدخال كلمة المرور"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-sm font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 cursor-pointer">
                        <span>حفظ كلمة المرور وتسجيل الدخول</span>
                        <span>←</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
