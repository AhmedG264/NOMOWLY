@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-teal-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold mb-3 border border-teal-400/30">
                <span>🚀</span>
                <span>لوحة تحكم المسوق (Marketer Workspace)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">مرحباً، {{ auth()->user()->marketerProfile?->full_name ?? auth()->user()->name }}</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                التخصص الأساسي: <span class="text-teal-400 font-bold">{{ auth()->user()->marketerProfile?->primarySpecialization?->specialization?->label() ?? 'مسوق معتمد' }}</span>
                • الحساب: <span class="text-emerald-400 font-bold">نشط وموثق</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('marketer.wallet.index') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>💳</span>
                <span>محفظتي والأرباح</span>
            </a>
            <a href="{{ route('marketer.campaigns.index') }}" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>📋</span>
                <span>حملاتي وأكواد التتبع</span>
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-teal-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-black">
                    💳
                </div>
                <h3 class="text-base font-black text-slate-900">محفظتي وسحب الأرباح</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    متابعة أرصدتك المتاحة بالعملات المختلفة، سجل العمولات المستحقة، وتقديم طلبات سحب الأرباح المصرفية.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('marketer.wallet.index') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition">
                    المحفظة وطلبات السحب ←
                </a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-black">
                    📋
                </div>
                <h3 class="text-base font-black text-slate-900">حملاتي وأكواد الخصم المعتمدة</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    متابعة الحملات التي تقدمت إليها، نسخ الروابط الذكية وأكواد التتبع، ومراقبة النقرات والمبيعات المحققة.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('marketer.campaigns.index') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-black transition">
                    إدارة الحملات والأكواد ←
                </a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-black">
                    🔍
                </div>
                <h3 class="text-base font-black text-slate-900">سوق الحملات والفرص</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    استكشف مئات الحملات المتاحة لكبرى المتاجر والعلامات التجارية في السعودية ومصر والخليج وقدم طلبك فوراً.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('marketplace') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition">
                    تصفح سوق الحملات ←
                </a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-700 flex items-center justify-center text-xl font-black">
                    💡
                </div>
                <h3 class="text-base font-black text-slate-900">دليل الشركاء والمبدعين</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    تعرف على آليات التسويق بالأداء، شروط احتساب العمولات، وأفضل ممارسات تحقيق أعلى معدلات تحويل.
                </p>
            </div>
            <div class="pt-5">
                <a href="{{ route('for-marketers') }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition">
                    دليل المسوقين ←
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
