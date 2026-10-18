<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>👛</span>
                <span>كشف حساب المحفظة ومطابقة الأرصدة</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                محفظة: <?php echo e($wallet->user?->brandProfile?->company_name ?? $wallet->user?->name ?? 'محفظة وسيطة'); ?> (<?php echo e($wallet->currency->value); ?>)
            </h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                المعرف الفريد: <span class="font-mono text-slate-300"><?php echo e($wallet->id); ?></span> • البريد: <?php echo e($wallet->user?->email); ?>

            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.finance.wallets')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للمحافظ
            </a>
        </div>
    </div>

    <!-- Reconciliation Card -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <h3 class="text-base font-black text-slate-900">حالة المطابقة المحاسبية لكشف الحساب (Reconciliation)</h3>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reconciliation['is_matched']): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span>✓</span>
                    <span>الأرصدة متطابقة 100% مع القيود المحاسبية</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    <span>⚠️</span>
                    <span>تفاوت بين الرصيد المخزن ودفتر القيود</span>
                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">الرصيد المتاح (Available Balance)</span>
                <div class="text-xl font-black text-emerald-700 font-mono">
                    <?php echo e(\App\Services\Math\MoneyMath::display($wallet->available_balance, $wallet->currency)); ?>

                    <span class="text-xs font-bold text-slate-400"><?php echo e($wallet->currency->value); ?></span>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">الرصيد المحجوز كضمان (Escrow Reserved)</span>
                <div class="text-xl font-black text-amber-600 font-mono">
                    <?php echo e(\App\Services\Math\MoneyMath::display($wallet->reserved_balance, $wallet->currency)); ?>

                    <span class="text-xs font-bold text-slate-400"><?php echo e($wallet->currency->value); ?></span>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">إجمالي رصيد المحفظة (Total Balance)</span>
                <div class="text-xl font-black text-slate-900 font-mono">
                    <?php echo e(\App\Services\Math\MoneyMath::display($wallet->total_balance, $wallet->currency)); ?>

                    <span class="text-xs font-bold text-slate-400"><?php echo e($wallet->currency->value); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement / Transactions Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">كشف حساب الحركات والقيود (Account Statement)</h3>
                <p class="text-xs text-slate-500 mt-0.5">الحركات المالية الصادرة والواردة المسجلة لهذه المحفظة</p>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($transactions->isEmpty()): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد حركات مالية مسجلة لهذه المحفظة حتى الآن.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                        <tr>
                            <th class="p-4">التاريخ والوقت</th>
                            <th class="p-4">رقم المعاملة</th>
                            <th class="p-4">نوع القيد</th>
                            <th class="p-4">الحساب المقابل</th>
                            <th class="p-4">المبلغ</th>
                            <th class="p-4">الرصيد المتاح بعدها</th>
                            <th class="p-4">المحجوز بعدها</th>
                            <th class="p-4">البيان</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 text-slate-500 font-mono text-[11px]">
                                    <?php echo e($tx->created_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4 font-mono font-bold">
                                    <a href="<?php echo e(route('admin.finance.transactions.show', $tx->ledger_transaction_id)); ?>" class="text-indigo-600 hover:underline">
                                        <?php echo e($tx->ledgerTransaction?->transaction_number); ?>

                                    </a>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo e($tx->entry_type->badgeClass()); ?>">
                                        <?php echo e($tx->entry_type->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 text-slate-600 font-mono text-[11px]">
                                    <?php echo e($tx->ledgerEntry?->account?->name_ar ?? $tx->ledgerEntry?->account?->code); ?>

                                </td>
                                <td class="p-4 font-mono font-bold <?php echo e($tx->entry_type->value === 'CREDIT' ? 'text-emerald-700' : 'text-slate-800'); ?>">
                                    <?php echo e($tx->entry_type->value === 'CREDIT' ? '+' : '-'); ?> <?php echo e(\App\Services\Math\MoneyMath::display($tx->amount, $wallet->currency)); ?>

                                </td>
                                <td class="p-4 font-mono text-slate-700">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($tx->balance_after, $wallet->currency)); ?>

                                </td>
                                <td class="p-4 font-mono text-amber-600">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($tx->reserved_after, $wallet->currency)); ?>

                                </td>
                                <td class="p-4 text-slate-600 max-w-xs truncate">
                                    <?php echo e($tx->description); ?>

                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                <?php echo e($transactions->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/finance/wallet-show.blade.php ENDPATH**/ ?>