@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ payoutModalOpen: false, selectedWalletId: '{{ $activeWallet->id }}', selectedCurrency: '{{ $selectedCurrency }}', maxAvailable: '{{ \App\Services\Math\MoneyMath::format($activeWallet->available_balance) }}', submittingPayout: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-teal-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold mb-3 border border-teal-400/30">
                <span>💳</span>
                <span>المحفظة المالية والأرباح المستحقة • Marketer Wallet & Payouts</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">محفظتي وأرباح التسويق</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                المسوق: <span class="text-white font-bold">{{ auth()->user()->marketerProfile?->full_name ?? auth()->user()->name }}</span>
                • العملة النشطة: <span class="text-teal-400 font-bold">{{ $selectedCurrency }}</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if (\App\Services\Math\MoneyMath::isPositive($activeWallet->available_balance))
                <button type="button" @click="payoutModalOpen = true; selectedWalletId = '{{ $activeWallet->id }}'; selectedCurrency = '{{ $selectedCurrency }}'; maxAvailable = '{{ \App\Services\Math\MoneyMath::format($activeWallet->available_balance) }}'"
                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2 cursor-pointer">
                    <span>💸</span>
                    <span>طلب سحب أرباح</span>
                </button>
            @endif
            <a href="{{ route('marketer.campaigns.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                حملاتي والأكواد
            </a>
            <a href="{{ route('marketer.dashboard') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-500 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="flex items-center gap-2">
                <span class="text-rose-600 font-black">✕</span>
                <span>حدث خطأ أثناء معالجة الطلب:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-medium text-rose-700">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Wallets Cards Grid -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">أرصدة المحافظ بالعملات المعتمدة</h2>
                <p class="text-xs text-slate-500">معزولة ومستقلة محاسبياً لكل عملة على حدة، ولا يتم خلط أسعار الصرف</p>
            </div>
            @if ($reconciliation)
                <div>
                    @if ($reconciliation['is_matched'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span>✓</span>
                            <span>مطابقة الرصيد: 100% متطابق مع دفتر الأستاذ</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <span>⚠️</span>
                            <span>تفاوت بين الرصيد ودفتر الأستاذ قيد التدقيق الإداري</span>
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
                    $hasBalance = \App\Services\Math\MoneyMath::isPositive($avail);
                    $isSelected = $selectedCurrency === $curr->value;
                @endphp
                <div class="bg-white rounded-3xl border {{ $isSelected ? 'border-teal-400 ring-2 ring-teal-500/20' : ($hasBalance ? 'border-slate-300' : 'border-slate-200/80') }} p-5 shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <a href="{{ route('marketer.wallet.index', ['currency' => $curr->value]) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-black {{ $isSelected ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' }}">
                                <span>{{ $curr->symbol() }}</span>
                                <span>{{ $curr->value }}</span>
                            </a>
                            <span class="text-[11px] font-bold text-slate-400">{{ $curr->label() }}</span>
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs text-slate-500 font-medium">الرصيد المتاح للسحب</div>
                            <div class="text-2xl font-black text-slate-900 tracking-tight">
                                {{ \App\Services\Math\MoneyMath::display($avail, $curr->value) }}
                                <span class="text-xs font-bold text-slate-400">{{ $curr->value }}</span>
                            </div>
                        </div>

                        @if (\App\Services\Math\MoneyMath::isPositive($reserved))
                            <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-amber-600 font-medium flex items-center justify-between">
                                <span>معلق بطلب سحب:</span>
                                <span class="font-bold">{{ \App\Services\Math\MoneyMath::display($reserved, $curr->value) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        @if ($wallet && $hasBalance)
                            <button type="button"
                                    @click="payoutModalOpen = true; selectedWalletId = '{{ $wallet->id }}'; selectedCurrency = '{{ $curr->value }}'; maxAvailable = '{{ $avail }}'"
                                    class="w-full py-2 px-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-black transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                                <span>💸</span>
                                <span>طلب سحب أرباح</span>
                            </button>
                        @else
                            <button type="button" disabled
                                    class="w-full py-2 px-3 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold cursor-not-allowed">
                                لا يوجد رصيد متاح
                            </button>
                        @endif

                        <a href="{{ route('marketer.wallet.index', ['currency' => $curr->value]) }}"
                           class="block text-center text-[11px] font-bold {{ $isSelected ? 'text-teal-700 font-black' : 'text-slate-400 hover:text-slate-600' }}">
                            {{ $isSelected ? '• العملة النشطة للكشف' : 'تصفية الكشف بهذه العملة ←' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Financial Performance Metrics (Selected Currency) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">الرصيد المتاح ({{ $selectedCurrency }})</div>
            <div class="text-xl font-black text-teal-600 mt-1">
                {{ \App\Services\Math\MoneyMath::display($activeWallet->available_balance, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">جاهز للسحب الفوري</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">معلق بطلب سحب</div>
            <div class="text-xl font-black text-amber-600 mt-1">
                {{ \App\Services\Math\MoneyMath::display($activeWallet->reserved_balance, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-amber-600/80 mt-0.5">محجوز بانتظار التحويل</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات مرحلة بالمحفظة</div>
            <div class="text-xl font-black text-emerald-700 mt-1">
                {{ \App\Services\Math\MoneyMath::display($settledCommissionSum, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-emerald-600 font-medium">أضيفت للرصيد الدفتري</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات معتمدة ومستحقة</div>
            <div class="text-xl font-black text-slate-900 mt-1">
                {{ \App\Services\Math\MoneyMath::display($approvedCommissionSum, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-slate-500 font-medium">مبيعات معتمدة من المتاجر</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات قيد المراجعة</div>
            <div class="text-xl font-black text-slate-600 mt-1">
                {{ \App\Services\Math\MoneyMath::display($pendingCommissionSum, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">تنتظر فحص الدفع والشحن</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">أرباح تم استلامها بالفعل</div>
            <div class="text-xl font-black text-emerald-600 mt-1">
                {{ \App\Services\Math\MoneyMath::display($totalActuallyPaidOut, $selectedCurrency) }}
            </div>
            <div class="text-[10px] text-emerald-600/80 mt-0.5">حُولت لحسابك البنكي</div>
        </div>
    </div>

    <!-- Earnings Breakdown by Campaign (سجل الأرباح حسب الحملة) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل الأرباح والعمولات حسب الحملة التسويقية</h3>
                <p class="text-xs text-slate-500 mt-0.5">تفصيل العمولات المضافة لمحفظتك من كل حملة تسويقية بالعملة المحددة ({{ $selectedCurrency }})</p>
            </div>
            <span class="text-xs font-bold text-slate-400">إجمالي الحملات المنتجة: {{ $campaignEarnings->count() }}</span>
        </div>

        @if ($campaignEarnings->isEmpty())
            <div class="p-8 text-center text-slate-400 text-xs">
                لا توجد أرباح مسجلة بهذه العملة ({{ $selectedCurrency }}) حتى الآن. عند اعتماد تحويلات مبيعاتك ستظهر هنا مصنفة حسب الحملة.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">العلامة التجارية</th>
                            <th class="p-4">عدد التحويلات المعتمدة</th>
                            <th class="p-4">إجمالي العمولات المستلمة</th>
                            <th class="p-4">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($campaignEarnings as $ce)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-bold text-slate-900">
                                    {{ $ce['campaign_title'] }}
                                </td>
                                <td class="p-4 text-slate-600 font-medium">
                                    {{ $ce['company_name'] }}
                                </td>
                                <td class="p-4 font-mono font-bold text-slate-800">
                                    {{ $ce['settled_count'] }} طلب
                                </td>
                                <td class="p-4 font-black text-emerald-600 text-sm">
                                    +{{ \App\Services\Math\MoneyMath::display($ce['total_earned'], $ce['currency']) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $ce['currency'] }}</span>
                                </td>
                                <td class="p-4">
                                    @if (!empty($ce['campaign_slug']))
                                        <a href="{{ route('marketplace.show', $ce['campaign_slug']) }}" class="text-teal-700 hover:text-teal-900 font-bold underline">
                                            تفاصيل الحملة ←
                                        </a>
                                    @else
                                        <a href="{{ route('marketer.campaigns.index') }}" class="text-teal-700 hover:text-teal-900 font-bold underline">
                                            حملاتي ←
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Payout Request Modal -->
    <div x-show="payoutModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="payoutModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-right space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg font-black">💸</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900">طلب سحب أرباح مصرفي</h3>
                        <p class="text-xs text-slate-500">عملة السحب: <span class="font-bold text-teal-600" x-text="selectedCurrency"></span></p>
                    </div>
                </div>
                <button type="button" @click="payoutModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('marketer.wallet.payout-request') }}" class="space-y-4" @submit="if(submittingPayout){ $event.preventDefault(); return false; } submittingPayout = true;">
                @csrf
                <input type="hidden" name="wallet_id" :value="selectedWalletId">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        المبلغ المطلوب سحبه (<span x-text="selectedCurrency"></span>) *
                    </label>
                    <div class="relative">
                        <input type="number" step="any" name="amount" required min="1" :max="maxAvailable"
                               placeholder="أدخل المبلغ المطلوب"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        الحد الأقصى المتاح حالياً: <span class="font-bold text-slate-800" x-text="maxAvailable"></span> <span x-text="selectedCurrency"></span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم البنك / المصرف *</label>
                    <input type="text" name="bank_name" required placeholder="مثال: مصرف الراجحي / البنك الأهلي السعودي"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم صاحب الحساب (المستفيد) *</label>
                    <input type="text" name="account_holder_name" required placeholder="الاسم المطابق لبطاقة الحساب البنكي"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رقم الآيبان (IBAN) أو رقم الحساب *</label>
                    <input type="text" name="account_reference" required placeholder="SA0000000000000000000000"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-teal-500 focus:outline-none dir-ltr text-right">
                    <p class="text-[10px] text-slate-400 mt-1">يتم تشفير وتظليل رقم الحساب تلقائياً لحماية خصوصيتك المصرفية، ولا يظهر علناً لأي جهة.</p>
                </div>

                <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-3 text-[11px] text-amber-800 space-y-1">
                    <p class="font-bold">⚠️ تنبيه أمني وإجرائي:</p>
                    <p>عند تأكيد الطلب، سيتم حجز المبلغ فورياً من رصيدك المتاح لمنع ازدواجية الصرف، وستتم مراجعته والتحويل لحسابك خلال يومين عمل.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="payoutModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="submittingPayout"
                            class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-black shadow-md transition cursor-pointer flex items-center gap-2">
                        <span x-show="!submittingPayout">تأكيد وإرسال طلب السحب</span>
                        <span x-show="submittingPayout" style="display:none;">جارٍ المعالجة...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Statement (كشف الحساب التفصيلي للمسوق) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-900">كشف حساب الحركات والعمليات المالية</h3>
                <p class="text-xs text-slate-500 mt-0.5">سجل الحركات المصرفية والعمولات المرحلة في دفتر القيود المحاسبي</p>
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="{{ route('marketer.wallet.index') }}" class="flex flex-wrap items-center gap-2 text-xs w-full lg:w-auto">
                <input type="hidden" name="currency" value="{{ $selectedCurrency }}">

                <select name="transaction_type" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <option value="">جميع أنواع الحركات</option>
                    @foreach (\App\Enums\LedgerTransactionType::cases() as $type)
                        <option value="{{ $type->value }}" {{ ($filters['transaction_type'] ?? '') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach (\App\Enums\LedgerTransactionStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1">
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" placeholder="من تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <span class="text-slate-400">إلى</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" placeholder="إلى تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="بحث برقم المعاملة..."
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs w-36 focus:ring-2 focus:ring-teal-500 focus:outline-none">

                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition cursor-pointer">
                    تصفية
                </button>

                @if (!empty($filters['transaction_type']) || !empty($filters['status']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['q']))
                    <a href="{{ route('marketer.wallet.index', ['currency' => $selectedCurrency]) }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
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
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">طبيعة القيد</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">الرصيد المتاح بعد</th>
                            <th class="p-4">المحجوز بعد</th>
                            <th class="p-4">البيان والتفاصيل</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transactions as $tx)
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

    <!-- Payout Requests History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل طلبات سحب الأرباح (Payout Requests)</h3>
                <p class="text-xs text-slate-500 mt-0.5">متابعة دقيقة لحالات طلبات السحب المصرفي وتواريخ الموافقة والتحويل</p>
            </div>
        </div>

        @if ($payoutRequests->isEmpty())
            <div class="p-12 text-center text-slate-400 text-xs">
                لم تقم بتقديم أي طلبات سحب أرباح حتى الآن. عند تقديم أول طلب سحب سيظهر سجله هنا مباشرة.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الطلب</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">البنك / الحساب المظلل</th>
                            <th class="p-4">حالة الطلب</th>
                            <th class="p-4">تاريخ الموافقة</th>
                            <th class="p-4">حالة ومرجع الدفع الفعلي</th>
                            <th class="p-4">تاريخ التقديم</th>
                            <th class="p-4">ملاحظات وسبب الرفض</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payoutRequests as $req)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    {{ $req->request_number }}
                                </td>
                                <td class="p-4 font-black text-slate-900">
                                    {{ \App\Services\Math\MoneyMath::display($req->amount, $req->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $req->currency->value }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $req->bank_name ?? '—' }}</div>
                                    <div class="font-mono text-[11px] text-slate-500 mt-0.5 dir-ltr text-right">{{ $req->account_reference ?? '—' }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $req->status->badgeClass() }}">
                                        {{ $req->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-600">
                                    @if ($req->reviewed_at)
                                        <span class="text-emerald-700 font-bold">{{ $req->reviewed_at->format('Y-m-d H:i') }}</span>
                                    @else
                                        <span class="text-slate-400 italic">قيد المراجعة</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-slate-600">
                                    @if ($req->isPaid() && $req->payment_reference)
                                        <div class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-block">
                                            {{ $req->payment_reference }}
                                        </div>
                                        @if ($req->paid_at)
                                            <div class="text-[10px] text-emerald-600 mt-0.5">{{ $req->paid_at->format('Y-m-d H:i') }}</div>
                                        @endif
                                    @elseif ($req->isApproved())
                                        <span class="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            معتمد • بانتظار إشعار البنك
                                        </span>
                                    @elseif ($req->isRejected())
                                        <span class="text-rose-600 font-bold">ملغي / مرفوض</span>
                                    @else
                                        <span class="text-slate-400 italic">بانتظار الموافقة والتحويل</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $req->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-4 text-slate-500 max-w-xs truncate">
                                    @if ($req->rejection_reason)
                                        <span class="text-rose-600 font-bold" title="{{ $req->rejection_reason }}">
                                            سبب الرفض: {{ $req->rejection_reason }}
                                        </span>
                                    @elseif ($req->admin_notes)
                                        <span>{{ $req->admin_notes }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $payoutRequests->links() }}
            </div>
        @endif
    </div>

    <!-- Commission Settlements History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل استحقاقات العمولات (Settled Commissions)</h3>
                <p class="text-xs text-slate-500 mt-0.5">العمولات المرحلة في دفتر الأستاذ والمضافة لمحفظتك من تحويلات المبيعات المؤكدة</p>
            </div>
        </div>

        @if ($settlements->isEmpty())
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد تسويات عمولات مسجلة بعد. عند اعتماد أي تحويل من قبل العلامات التجارية تضاف أرباحك هنا فوراً.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم التحويل الخارجي</th>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">قيمة العمولة المستحقة</th>
                            <th class="p-4">تاريخ التسوية</th>
                            <th class="p-4">الحالة المحاسبية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($settlements as $settlement)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    #{{ $settlement->conversion?->external_order_id ?? '—' }}
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    {{ $settlement->conversion?->campaign?->title ?? '—' }}
                                </td>
                                <td class="p-4 font-black text-emerald-600">
                                    +{{ \App\Services\Math\MoneyMath::display($settlement->commission_amount, $settlement->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $settlement->currency->value }}</span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500">
                                    {{ $settlement->settled_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-4">
                                    @if ($settlement->isReversed())
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            معكوس (طلب ملغي)
                                        </span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            مرحل في المحفظة ✓
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
