@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ reversalModalOpen: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>📑</span>
                <span>تفاصيل المعاملة والقيود المحاسبية المزدوجة</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight font-mono">
                {{ $transaction->transaction_number }}
            </h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تاريخ الترحيل: {{ $transaction->posted_at->format('Y-m-d H:i:s') }} • العملة: <span class="font-bold text-white">{{ $transaction->currency->value }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.finance.transactions') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة لسجل القيود
            </a>
            @if($transaction->isPosted() && !$transaction->reversal)
                <button @click="reversalModalOpen = true" type="button" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition cursor-pointer">
                    عكس المعاملة (قيد تسوية)
                </button>
            @endif
        </div>
    </div>

    <!-- Status Alerts -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-bold text-emerald-800 flex items-center gap-2">
            <span class="text-emerald-500 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs font-bold text-rose-800">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($transaction->isReversed() || $transaction->reversal)
        <div class="bg-purple-50 border border-purple-200 p-5 rounded-3xl text-xs text-purple-900 flex items-start gap-3">
            <span class="text-xl">ℹ️</span>
            <div>
                <p class="font-black text-sm">هذه المعاملة تم عكسها بقيد تسوية تصحيحي (Storno Reversal):</p>
                <p class="text-purple-700 mt-1">
                    سبب العكس: <span class="font-bold">{{ $transaction->reversal_reason ?? 'تسوية محاسبية' }}</span>
                </p>
                @if($transaction->reversal)
                    <p class="mt-2">
                        معاملة القيد العكسي:
                        <a href="{{ route('admin.finance.transactions.show', $transaction->reversal) }}" class="font-mono font-black underline">
                            {{ $transaction->reversal->transaction_number }}
                        </a>
                    </p>
                @endif
            </div>
        </div>
    @endif

    <!-- Transaction Metadata Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block mb-1">نوع العملية:</span>
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border {{ $transaction->transaction_type->badgeClass() }}">
                {{ $transaction->transaction_type->label() }}
            </span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block mb-1">حالة المعاملة:</span>
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border {{ $transaction->status->badgeClass() }}">
                {{ $transaction->status->label() }}
            </span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block mb-1">مفتاح عدم التكرار (Idempotency Key):</span>
            <span class="text-xs font-mono font-bold text-slate-800 break-all">{{ $transaction->idempotency_key }}</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block mb-1">المصدر والمرجع:</span>
            <span class="text-xs font-medium text-slate-700">
                {{ $transaction->source_type ? class_basename($transaction->source_type) . ' #' . $transaction->source_id : 'قيد يدوي / نظامي' }}
            </span>
        </div>
    </div>

    <!-- Description & Balance Check -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-base font-black text-slate-900">البيان المحاسبي:</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $transaction->description }}</p>
            </div>
            <div>
                @if($isBalanced)
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span>✓</span>
                        <span>القيد متوازن محاسبياً (المدين = الدائن = {{ \App\Services\Math\MoneyMath::display($totalDebits, $transaction->currency) }})</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                        <span>⚠️</span>
                        <span>تفاوت محاسبي غير متوازن!</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Double-Entry Line Items (Ledger Entries) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200/80">
            <h3 class="text-base font-black text-slate-900">بنود القيود المحاسبية (Debit & Credit Line Items)</h3>
            <p class="text-xs text-slate-500 mt-0.5">الحسابات المدينة والدائنة المتأثرة بهذه المعاملة في دفتر الأستاذ</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                    <tr>
                        <th class="p-4">رمز الحساب واسمه</th>
                        <th class="p-4">طبيعة الحساب</th>
                        <th class="p-4">المحفظة المرتبطة</th>
                        <th class="p-4">النوع</th>
                        <th class="p-4 text-emerald-800">مدين (Debit)</th>
                        <th class="p-4 text-purple-800">دائن (Credit)</th>
                        <th class="p-4">البيان الفرعي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($transaction->entries as $entry)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <div class="font-mono font-bold text-slate-900">{{ $entry->account->code }}</div>
                                <div class="text-[11px] text-slate-600">{{ $entry->account->name_ar }}</div>
                            </td>
                            <td class="p-4 text-slate-500 text-[11px]">
                                {{ $entry->account->type->label() }}
                            </td>
                            <td class="p-4">
                                @if($entry->wallet)
                                    <a href="{{ route('admin.finance.wallets.show', $entry->wallet) }}" class="text-indigo-600 hover:underline font-bold">
                                        {{ $entry->wallet->user?->name ?? 'محفظة' }} ({{ $entry->wallet->currency->value }})
                                    </a>
                                @else
                                    <span class="text-slate-400 font-medium">حساب منصة عام</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $entry->entry_type->badgeClass() }}">
                                    {{ $entry->entry_type->label() }}
                                </span>
                            </td>
                            <td class="p-4 font-mono font-bold text-emerald-700">
                                @if($entry->isDebit())
                                    {{ \App\Services\Math\MoneyMath::display($entry->amount, $transaction->currency) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-4 font-mono font-bold text-purple-700">
                                @if($entry->isCredit())
                                    {{ \App\Services\Math\MoneyMath::display($entry->amount, $transaction->currency) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-4 text-slate-500 text-[11px]">
                                {{ $entry->description }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                    <tr>
                        <td colspan="4" class="p-4 text-left font-black text-slate-800">
                            المجموع الإجمالي (Total):
                        </td>
                        <td class="p-4 font-mono font-black text-emerald-700">
                            {{ \App\Services\Math\MoneyMath::display($totalDebits, $transaction->currency) }} {{ $transaction->currency->value }}
                        </td>
                        <td class="p-4 font-mono font-black text-purple-700">
                            {{ \App\Services\Math\MoneyMath::display($totalCredits, $transaction->currency) }} {{ $transaction->currency->value }}
                        </td>
                        <td class="p-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Reversal Modal -->
    <div x-show="reversalModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100" @click.outside="reversalModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-rose-600">
                    <span class="text-2xl">⚠️</span>
                    <h3 class="text-base font-black text-slate-900">تأكيد إجراء قيد عكسي تصحيحي</h3>
                </div>
                <button @click="reversalModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                وفقاً لمبادئ المحاسبة المزدوجة، لا يمكن حذف المعاملات أو تعديلها. سيتم إنشاء معاملة قيد عكسي جديدة متوازنة تعكس المدين والدائن، وتعدل الأرصدة في محافظ الأطراف المعنية تلقائياً، مع توثيق العملية بسجل التدقيق.
            </p>

            <form method="POST" action="{{ route('admin.finance.transactions.reverse', $transaction) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">سبب القيد العكسي (إلزامي للتدقيق):</label>
                    <textarea name="reason" rows="3" required placeholder="اذكر سبب إجراء القيد العكسي (مثال: تسوية نزاع، خطأ في الحساب، استرداد معتمد)..."
                              class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="reversalModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                        إلغاء
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black shadow-md transition">
                        تأكيد إنشاء القيد العكسي
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
