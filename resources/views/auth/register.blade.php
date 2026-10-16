@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto" x-data="{ selectedRole: '{{ old('role', request('role', 'MARKETER')) }}' }">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-600/20 mb-3">
            ن
        </div>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
            إنشاء حساب جديد في منصة نمولي
        </h1>
        <p class="mt-2 text-sm text-slate-600 font-medium">
            لديك حساب بالفعل؟
            <a href="{{ route('login') }}" class="font-bold text-emerald-600 hover:text-emerald-700 underline underline-offset-4 mr-1">
                تسجيل الدخول
            </a>
        </p>
    </div>

    <div class="bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80">
        <!-- Error Alerts -->
        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-medium">
                <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-900">
                    <span>⚠️</span>
                    <span>يرجى تصحيح الأخطاء التالية للمتابعة:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pr-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
            @csrf

            <!-- Role Selection Cards -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                    اختر نوع الحساب
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Marketer Choice -->
                    <label class="relative flex flex-col p-4 sm:p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none"
                        :class="selectedRole === 'MARKETER' ? 'border-emerald-600 bg-emerald-50/50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="role" value="MARKETER" x-model="selectedRole" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-black text-slate-900 text-sm sm:text-base">مسوق بالعمولة (Marketer)</span>
                            <span class="text-xl">🚀</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">
                            لصناع المحتوى والمؤثرين ومشتري الإعلانات والمسوقين بالأداء الراغبين بتحقيق أرباح عبر العمولات.
                        </p>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] font-bold"
                            :class="selectedRole === 'MARKETER' ? 'text-emerald-700' : 'text-slate-400'">
                            <span class="w-2 h-2 rounded-full" :class="selectedRole === 'MARKETER' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                            <span>محدد كنوع الحساب</span>
                        </div>
                    </label>

                    <!-- Brand Choice -->
                    <label class="relative flex flex-col p-4 sm:p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none"
                        :class="selectedRole === 'BRAND' ? 'border-emerald-600 bg-emerald-50/50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="role" value="BRAND" x-model="selectedRole" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-black text-slate-900 text-sm sm:text-base">علامة تجارية (Brand)</span>
                            <span class="text-xl">🏬</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">
                            للمتاجر الإلكترونية والشركات والعلامات التجارية لزيادة المبيعات عبر شبكة تسويق بالأداء.
                        </p>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] font-bold"
                            :class="selectedRole === 'BRAND' ? 'text-emerald-700' : 'text-slate-400'">
                            <span class="w-2 h-2 rounded-full" :class="selectedRole === 'BRAND' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                            <span>محدد كنوع الحساب</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Basic User Info -->
            <div class="pt-2 border-t border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            الاسم الكامل
                        </label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                            placeholder="مثال: أحمد محمد"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            البريد الإلكتروني
                        </label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                            placeholder="name@example.com"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                    </div>
                </div>
            </div>

            <!-- Password Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        كلمة المرور
                    </label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        placeholder="8 أحرف على الأقل (أرقام وأحرف)"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        تأكيد كلمة المرور
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        placeholder="أعد إدخال كلمة المرور"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>
            </div>

            <!-- Brand Specific Fields (Alpine reactive) -->
            <div x-show="selectedRole === 'BRAND'" x-cloak class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center gap-2 text-sm font-black text-slate-900">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">🏢</span>
                    <span>بيانات العلامة التجارية والمتجر الإلكتروني</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="company_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            اسم المتجر أو الشركة
                        </label>
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}"
                            placeholder="مثال: متجر الأناقة"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                    </div>

                    <div>
                        <label for="currency" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            العملة الأساسية لمتجرك
                        </label>
                        <select id="currency" name="currency"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                            @foreach($currencies as $code => $info)
                                <option value="{{ $code }}" {{ old('currency', 'EGP') === $code ? 'selected' : '' }}>
                                    {{ $info['name'] }} ({{ $code }} - {{ $info['symbol'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="website_url" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        رابط المتجر أو الموقع الإلكتروني (اختياري)
                    </label>
                    <input id="website_url" name="website_url" type="url" placeholder="https://yourstore.com" value="{{ old('website_url') }}"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>
            </div>

            <!-- Marketer Specific Fields (Alpine reactive) -->
            <div x-show="selectedRole === 'MARKETER'" x-cloak class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center gap-2 text-sm font-black text-slate-900">
                    <span class="w-6 h-6 rounded-lg bg-teal-100 text-teal-800 flex items-center justify-center text-xs">🎯</span>
                    <span>تخصصات التسويق والعمل</span>
                </div>

                <div>
                    <label for="primary_specialization" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        التخصص الرئيسي (إلزامي)
                    </label>
                    <select id="primary_specialization" name="primary_specialization"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                        @foreach($specializations as $spec)
                            <option value="{{ $spec->value }}" {{ old('primary_specialization') === $spec->value ? 'selected' : '' }}>
                                {{ $spec->label() }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">يحدد هذا التخصص العروض والحملات الأكثر ملاءمة لمهاراتك في لوحة التحكم.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        تخصصات إضافية تمارسها (اختياري)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        @foreach($specializations as $spec)
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50/40 hover:bg-slate-100 cursor-pointer text-xs font-bold text-slate-700 select-none transition">
                                <input type="checkbox" name="specializations[]" value="{{ $spec->value }}"
                                    class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                                <span>{{ $spec->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit"
                    class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-sm font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 cursor-pointer">
                    <span>تأكيد وإنشاء الحساب</span>
                    <span>←</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
