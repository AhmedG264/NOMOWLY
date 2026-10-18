@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ depositModalOpen: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>🏬</span>
                <span>المحفظة المالية للعلامة التجارية • Brand Wallet & Escrow</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">محفظة المتجر وإدارة ميزانيات الحملات</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                الجهة: <span class="text-white font-bold">{{ auth()->user()->brandProfile?->company_name ?? auth()->user()->name }}</span>
                • العملة المعتمدة: <span class="text-emerald-400 font-bold">{{ $selectedCurrency }}</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="depositModalOpen = true"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2 cursor-pointer">
                <span>➕</span>
                <span>شحن المحفظة</span>
            </button>
            <a href="{{ route('brand.campaigns.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                الحملات
            </a>
            <a href="{{ route('brand.dashboard') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Multi-Currency Balances Cards -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">أرصدة المحفظة بالعملات المعتمدة</h2>
                <p class="text-xs text-slate-500">معزولة ومستقلة محاسبياً لكل عملة على حدة، ولا يتم خلط أسعار الصرف</p>
            </div>
            @if ($reconciliation)
                <div>
                    @if ($reconciliation['is_matched'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span>✓</span>
                            <span>مطابقة كشف الحساب: 100% متطابق</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <span>⚠️</span>
                            <span>تفاوت بين الرصيد ودفتر الأستاذ قيد المراجعة</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($currencies as $curr)
                @php
                    $wallet = $wallets[$curr->value] ?? null;
                    $avail = $wallet ? \App\Services\Math\MoneyMath::format($wallet->available_balance) : '0.0000';
                    $reserved = $wallet ? \App\Services\Math\MoneyMath::format($wallet->reserved_balance) : '0.0000';
                    $total = $wallet ? \App\Services\Math\MoneyMath::format($wallet->total_balance) : '0.0000';
                    $isSelected = $selectedCurrency === $curr->value;
                @endphp
                <a href="{{ route('brand.wallet.index', ['currency' => $curr->value]) }}"
                   class="bg-white rounded-3xl border {{ $isSelected ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-slate-300' }} p-5 shadow-xs transition flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-black {{ $isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                                <span>{{ $curr->symbol() }}</span>
                                <span>{{ $curr->value }}</span>
                            </span>
                            <span class="text-[11px] font-bold text-slate-400">{{ $curr->label() }}</span>
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs text-slate-500 font-medium">الرصيد المتاح (لتمويل الحملات)</div>
                            <div class="text-2xl font-black text-slate-900 tracking-tight">
                                {{ \App\Services\Math\MoneyMath::display($avail, $curr->value) }}
                                <span class="text-xs font-bold text-slate-400">{{ $curr->value }}</span>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-amber-700">
                                <span>المحجوز كضمان للحملات (Escrow):</span>
                                <span class="font-bold">{{ \App\Services\Math\MoneyMath::display($reserved, $curr->value) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[11px]">
                                <span>إجمالي الرصيد الفعلي:</span>
                                <span class="font-bold text-slate-700">{{ \App\Services\Math\MoneyMath::display($total, $curr->value) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-[11px] font-bold {{ $isSelected ? 'text-emerald-700' : 'text-slate-400' }}">
                        {{ $isSelected ? '• العملة المحددة حالياً للكشف' : 'انقر لعرض الكشف بهذه العملة ←' }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Escrow Budget & Safety Notice -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 p-6 rounded-3xl flex items-start gap-4">
            <span class="text-3xl shrink-0">🔒</span>
            <div class="space-y-1.5 text-xs text-emerald-950">
                <h3 class="font-black text-sm text-emerald-900">آلية الضمان المالي لحملات الأداء (Escrow Safety Mechanism):</h3>
                <p class="leading-relaxed text-slate-600">
                    عند إطلاق أو تمويل أي حملة تسويقية، يتم نقل المبلغ المخصص من رصيدك المتاح إلى الرصيد المحجوز بالضمان (<span class="font-bold font-mono">Reserved Balance</span>).
                    تظل هذه الأموال ملكاً لمتجرك ومحجوزة كضمان لمكافأة المسوقين عند تحقيق مبيعات مؤكدة، ولا يتم خصمها نهائياً إلا فور اعتمادك المباشر لطلب المبيعات. يمكنك تحرير الفائض عند إيقاف الحملة.
                </p>
                <div class="pt-1 flex items-center gap-4 font-bold text-[11px] text-emerald-800">
                    <span>• الرصيد المتاح حالياً بالـ {{ $selectedCurrency }}: {{ \App\Services\Math\MoneyMath::display($activeWallet->available_balance, $selectedCurrency) }}</span>
                    <span>• الضمان المحجوز: {{ \App\Services\Math\MoneyMath::display($activeWallet->reserved_balance, $selectedCurrency) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black">
                    📢
                </div>
                <h3 class="text-sm font-black text-slate-900">الحملات النشطة ذات الميزانيات</h3>
                <p class="text-xs text-slate-500">
                    عدد حملات متجرك المسجلة: <span class="font-bold text-slate-800">{{ $campaigns->count() }}</span> حملة
                </p>
            </div>
            <div class="pt-4">
                <a href="{{ route('brand.campaigns.index') }}" class="block w-full text-center py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                    إدارة الحملات والميزانيات ←
                </a>
            </div>
        </div>
    </div>

    <!-- Deposit Information / Payment Modal -->
    <div x-show="depositModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="depositModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-right space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black">💳</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900">شحن رصيد المحفظة المالية</h3>
                        <p class="text-xs text-slate-500">العملة المحددة: <span class="font-bold text-emerald-600">{{ $selectedCurrency }}</span></p>
                    </div>
                </div>
                <button type="button" @click="depositModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
            </div>

            @if ($isPaymentGatewayAvailable)
                <!-- Electronic Gateway Form -->
                <form method="POST" action="{{ route('brand.wallet.deposit') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $selectedCurrency }}">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">المبلغ المراد شحنه ({{ $selectedCurrency }}) *</label>
                        <input type="number" step="any" min="1" name="amount" required placeholder="مثال: 500"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">بوابة الدفع الإلكتروني *</label>
                        <select name="provider" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                            @foreach ($availableGateways as $key => $gw)
                                <option value="{{ $key }}">{{ $gw->getProvider()->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">طريقة الدفع المفضلة</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="mada" checked class="sr-only">
                                <span class="text-sm font-black text-slate-800">مدى / بطاقة</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Mada / Card</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="applepay" class="sr-only">
                                <span class="text-sm font-black text-slate-800">أبل باي</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Apple Pay</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="creditcard" class="sr-only">
                                <span class="text-sm font-black text-slate-800">فيزا / ماستر</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Visa/Master</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-[11px] text-emerald-800 space-y-1">
                        <p class="font-bold">🔒 حماية وتشفير مصرفي معتمد:</p>
                        <p>تتم معالجة عمليات الدفع عبر خوادم البوابة المشفرة. لا يتم إضافة الرصيد إلى محفظتك إلا بعد وصول تأكيد رسمي مشفر من مزود الدفع.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="depositModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                            إلغاء
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                            متابعة للدفع الآمن ←
                        </button>
                    </div>
                </form>
            @else
                <!-- No Live Gateway Configured Notice -->
                <div class="space-y-4 text-xs text-slate-700 leading-relaxed">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-amber-900 space-y-2">
                        <div class="flex items-center gap-2 font-black text-sm">
                            <span>ℹ️</span>
                            <span>بوابات الدفع الإلكتروني قيد الربط المؤسسي:</span>
                        </div>
                        <p>
                            شحن المحفظة اللحظي عبر البطاقات الائتمانية وبوابات الدفع الإقليمية (ميسر، تاب، مدى) قيد الإعداد المؤسسي وستتاح للمدفوعات المباشرة فور تفعيل الاعتمادات البنكية.
                        </p>
                    </div>

                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-2">
                        <h4 class="font-bold text-slate-900">الإيداع المصرفي المباشر عبر الحساب البنكي:</h4>
                        <p class="text-slate-600">
                            لشحن رصيد محفظتك حالياً وتفعيل ميزانيات الحملات، يُرجى إجراء تحويل بنكي إلى حساب منصة نمولي الرسمي وإرسال إشعار التحويل لفريق الحسابات المالية لإيداع الرصيد في محفظتك فوراً بموجب قيد مزدوج معتمد.
                        </p>
                        <div class="pt-2 border-t border-slate-200 font-mono text-[11px] text-slate-800 space-y-1">
                            <div>البنك: <span class="font-bold">مصرف الراجحي / البنك الأهلي السعودي</span></div>
                            <div>البريد المالي: <span class="font-bold text-emerald-700">finance@nomowly.com</span></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="depositModalOpen = false"
                                class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                            فهمت ذلك، إغلاق
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Deposits History (سجل عمليات الشحن والإيداع) -->
    @if(isset($deposits) && $deposits->isNotEmpty())
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900">سجل عمليات شحن المحفظة (Payment Deposits)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">تتبع حالات عمليات الدفع الإلكتروني والإيداعات بالعملة المحددة ({{ $selectedCurrency }})</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم العملية</th>
                            <th class="p-4">مزود الدفع</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">الصافي المودع</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">تاريخ الإنشاء</th>
                            <th class="p-4">تاريخ التأكيد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($deposits as $dep)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    {{ $dep->deposit_number }}
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    {{ $dep->provider->label() }}
                                </td>
                                <td class="p-4 font-bold text-slate-900">
                                    {{ \App\Services\Math\MoneyMath::display($dep->amount, $dep->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $dep->currency->value }}</span>
                                </td>
                                <td class="p-4 font-black text-emerald-700">
                                    {{ \App\Services\Math\MoneyMath::display($dep->net_amount, $dep->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $dep->currency->value }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $dep->status->badgeClass() }}">
                                        {{ $dep->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500">
                                    {{ $dep->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-600">
                                    @if ($dep->paid_at)
                                        <span class="text-emerald-700 font-bold">{{ $dep->paid_at->format('Y-m-d H:i') }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Financial Statement (كشف الحساب التفصيلي) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-900">كشف حساب الحركات والعمليات المالية</h3>
                <p class="text-xs text-slate-500 mt-0.5">سجل الحركات المرحلة في دفتر القيود المحاسبي المزدوج</p>
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="{{ route('brand.wallet.index') }}" class="flex flex-wrap items-center gap-2 text-xs w-full lg:w-auto">
                <input type="hidden" name="currency" value="{{ $selectedCurrency }}">

                <select name="transaction_type" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">جميع أنواع الحركات</option>
                    @foreach (\App\Enums\LedgerTransactionType::cases() as $type)
                        <option value="{{ $type->value }}" {{ ($filters['transaction_type'] ?? '') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach (\App\Enums\LedgerTransactionStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1">
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" placeholder="من تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-slate-400">إلى</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" placeholder="إلى تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="بحث برقم المعاملة..."
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs w-36 focus:ring-2 focus:ring-emerald-500 focus:outline-none">

                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition cursor-pointer">
                    تصفية
                </button>

                @if (!empty($filters['transaction_type']) || !empty($filters['status']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['q']))
                    <a href="{{ route('brand.wallet.index', ['currency' => $selectedCurrency]) }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        إلغاء التصفية
                    </a>
                @endif
            </form>
        </div>

        @if ($transactions->isEmpty())
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد حركات مالية مسجلة بهذه العملة ومعايير التصفية المحددة.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم القيد المرجعي</th>
                            <th class="p-4">نوع الحركة</th>
                            <th class="p-4">الحملة المرتبطة</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">طبيعة القيد</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">الرصيد المتاح بعد</th>
                            <th class="p-4">الضمان المحجوز بعد</th>
                            <th class="p-4">البيان والتفاصيل</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transactions as $tx)
                            @php
                                $associatedCampaign = null;
                                if ($tx->ledgerTransaction) {
                                    if ($tx->ledgerTransaction->source_type === \App\Models\Campaign::class) {
                                        $associatedCampaign = $campaignsMap->get($tx->ledgerTransaction->source_id);
                                    } elseif ($tx->ledgerTransaction->source_type === \App\Models\Conversion::class) {
                                        $conv = $conversionsMap->get($tx->ledgerTransaction->source_id);
                                        $associatedCampaign = $conv?->campaign;
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    {{ $tx->ledgerTransaction?->transaction_number ?? substr($tx->ledger_transaction_id, 0, 8) }}
                                </td>
                                <td class="p-4">
                                    @if ($tx->ledgerTransaction)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $tx->ledgerTransaction->transaction_type->badgeClass() }}">
                                            {{ $tx->ledgerTransaction->transaction_type->label() }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if ($associatedCampaign)
                                        <a href="{{ route('brand.campaigns.edit', $associatedCampaign->id) }}" class="font-bold text-emerald-700 hover:text-emerald-900 underline truncate block max-w-[130px]" title="{{ $associatedCampaign->title }}">
                                            {{ $associatedCampaign->title }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="p-4 font-black {{ $tx->entry_type->value === 'CREDIT' ? 'text-emerald-700' : 'text-slate-900' }}">
                                    {{ $tx->entry_type->value === 'CREDIT' ? '+' : '-' }}{{ \App\Services\Math\MoneyMath::display($tx->amount, $tx->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $tx->currency->value }}</span>
                                </td>
                                <td class="p-4 font-bold">
                                    <span class="text-[11px] {{ $tx->entry_type->value === 'DEBIT' ? 'text-amber-700' : 'text-blue-700' }}">
                                        {{ $tx->entry_type->label() }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    @if ($tx->ledgerTransaction)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $tx->ledgerTransaction->status->badgeClass() }}">
                                            {{ $tx->ledgerTransaction->status->label() }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono font-bold text-slate-800">
                                    {{ \App\Services\Math\MoneyMath::display($tx->balance_after, $tx->currency->value) }}
                                </td>
                                <td class="p-4 font-mono text-amber-700">
                                    {{ \App\Services\Math\MoneyMath::display($tx->reserved_after, $tx->currency->value) }}
                                </td>
                                <td class="p-4 text-slate-600 max-w-xs truncate" title="{{ $tx->description }}">
                                    {{ $tx->description }}
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $tx->created_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
