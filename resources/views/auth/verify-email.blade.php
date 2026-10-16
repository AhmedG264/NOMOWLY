@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-12rem)] flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-600/20 mb-3">
                ✉️
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                تأكيد البريد الإلكتروني
            </h1>
            <p class="mt-2 text-sm text-slate-600 font-medium">
                يرجى تأكيد بريدك الإلكتروني لضمان أمان حسابك وتلقي الإشعارات المالية المهمة
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-2xl">
                📬
            </div>

            <p class="text-sm text-slate-700 font-medium leading-relaxed mb-6">
                تم تسجيل حسابك بنجاح. في بيئة التطوير الحالية، يمكنك محاكاة النقر على رابط التحقق مباشرة لتفعيل بريدك الإلكتروني فورياً:
            </p>

            <form method="POST" action="{{ route('verification.verify') }}">
                @csrf
                <button type="submit"
                    class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-sm font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 cursor-pointer">
                    <span>تأكيد بريدي الآن (محاكاة الاختبار)</span>
                    <span>✓</span>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-xs font-bold text-slate-600 hover:text-emerald-600">
                    العودة للرئيسية
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-700 cursor-pointer">
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
