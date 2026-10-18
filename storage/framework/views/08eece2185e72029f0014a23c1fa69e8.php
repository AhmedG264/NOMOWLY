<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>💰</span>
                <span>النظام المالي والمحفظة ودفتر الأستاذ (Double-Entry Ledger)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">المركز المالي والمحافظ المحاسبية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                نظام دفتر القيود المحاسبي المزدوج، إدارة المحافظ متعددة العملات، وحجز وضمان ميزانيات الحملات التسويقية.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.finance.trial-balance')); ?>" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-1.5">
                <span>⚖️</span>
                <span>فحص ميزان المراجعة</span>
            </a>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                الرئيسية
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            سجل العمليات والمراقبة
        </a>
        <a href="<?php echo e(route('admin.roles.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="<?php echo e(route('admin.users.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة المستخدمين
        </a>
        <a href="<?php echo e(route('admin.finance.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-black bg-emerald-600 text-white shadow-xs">
            المالية والمحافظ المحاسبية
        </a>
    </div>

    <!-- Sub-navigation links -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="<?php echo e(route('admin.finance.index')); ?>" class="text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">نظرة عامة</a>
        <a href="<?php echo e(route('admin.finance.deposits.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50 font-black text-indigo-700">عمليات الإيداع والشحن 💳</a>
        <a href="<?php echo e(route('admin.finance.refunds.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">الاستردادات المالية</a>
        <a href="<?php echo e(route('admin.finance.reconciliation.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">تقرير المطابقة المالية ⚖️</a>
        <a href="<?php echo e(route('admin.finance.wallets')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">قائمة المحافظ (<?php echo e($stats['total_wallets']); ?>)</a>
        <a href="<?php echo e(route('admin.finance.transactions')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">سجل القيود والمعاملات (<?php echo e($stats['total_transactions']); ?>)</a>
        <a href="<?php echo e(route('admin.finance.trial-balance')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">ميزان المراجعة والتطابق المحاسبي</a>
        <a href="<?php echo e(route('admin.finance.payouts.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">طلبات السحب المصرفية</a>
        <a href="<?php echo e(route('admin.finance.settlements.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">تسويات العمولات</a>
    </div>

    <!-- Currency Selection Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">اختر عملة العرض:</span>
            <div class="flex items-center gap-1.5 flex-wrap">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('admin.finance.index', ['currency' => $curr->value])); ?>"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?php echo e($selectedCurrency === $curr->value ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                        <?php echo e($curr->value); ?>

                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['is_system_balanced']): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span>✓</span>
                    <span>النظام المحاسبي متوازن 100% (مدين = دائن)</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    <span>⚠️</span>
                    <span>يوجد تفاوت محاسبي يحتاج للمراجعة</span>
                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- Financial Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">إجمالي الأرصدة المتاحة (<?php echo e($selectedCurrency); ?>)</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black">💵</span>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                <?php echo e(\App\Services\Math\MoneyMath::display($stats['total_available'], $selectedCurrency)); ?>

                <span class="text-xs font-bold text-slate-400"><?php echo e($selectedCurrency); ?></span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">أرصدة قابلة للصرف أو سحب الأرباح</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">أموال ميزانيات محجوزة (Escrow)</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-black">🔒</span>
            </div>
            <div class="text-2xl font-black text-amber-600 tracking-tight">
                <?php echo e(\App\Services\Math\MoneyMath::display($stats['total_reserved'], $selectedCurrency)); ?>

                <span class="text-xs font-bold text-slate-400"><?php echo e($selectedCurrency); ?></span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">ميزانيات حملات معلقة كضمان للمسوقين</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">إجمالي الأرصدة الإجمالية</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-black">🏦</span>
            </div>
            <div class="text-2xl font-black text-indigo-900 tracking-tight">
                <?php echo e(\App\Services\Math\MoneyMath::display($stats['total_balance'], $selectedCurrency)); ?>

                <span class="text-xs font-bold text-slate-400"><?php echo e($selectedCurrency); ?></span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">مجموع المتاح والمحجوز بالعملة المحددة</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">إجمالي المحافظ والمعاملات</span>
                <span class="w-8 h-8 rounded-xl bg-slate-50 text-slate-700 flex items-center justify-center text-sm font-black">📊</span>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                <?php echo e(number_format($stats['total_wallets'])); ?>

                <span class="text-xs font-bold text-slate-400">محفظة</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium"><?php echo e(number_format($stats['total_transactions'])); ?> معاملة مرحلة في دفتر الأستاذ</p>
        </div>
    </div>

    <!-- Quick Accounting Invariant Notice -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/70 p-5 rounded-3xl text-xs text-emerald-900 flex items-start gap-4">
        <span class="text-2xl shrink-0">🏛️</span>
        <div class="space-y-1">
            <p class="font-black text-sm">قاعدة القيد المزدوج الإلزامية الصارمة (Double-Entry Invariant):</p>
            <p class="text-slate-600 leading-relaxed">
                كل حركة مالية مرحلة في منصة نمولي تحتوي على قيدين محاسبيين أو أكثر، بحيث يتطابق مجموع المدين تماماً مع مجموع الدائن ضمن نفس العملة (<span class="font-mono font-bold">Sum(Debits) === Sum(Credits)</span>).
                القيود لا تحذف ولا تعدل، ويتم التراجع حصراً بواسطة قيد عكسي تصحيحي (Storno Reversal) موثق بسجل تدقيق كامل.
            </p>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">أحدث المعاملات في دفتر الأستاذ (General Journal)</h3>
                <p class="text-xs text-slate-500 mt-0.5">آخر القيود المالية المرحلة والمسجلة بنظام القيد المزدوج</p>
            </div>
            <a href="<?php echo e(route('admin.finance.transactions')); ?>" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
                عرض كامل السجل ←
            </a>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($recentTransactions->isEmpty()): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد معاملات مالية مسجلة بعد. عند بدء تداول الأرصدة أو حجز الميزانيات ستظهر المعاملات هنا فوراً.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                        <tr>
                            <th class="p-4">رقم المعاملة</th>
                            <th class="p-4">النوع</th>
                            <th class="p-4">العملة</th>
                            <th class="p-4">البيان المحاسبي</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">التاريخ والوقت</th>
                            <th class="p-4 text-center">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $recentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    <?php echo e($tx->transaction_number); ?>

                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold border <?php echo e($tx->transaction_type->badgeClass()); ?>">
                                        <?php echo e($tx->transaction_type->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 font-black text-slate-700">
                                    <?php echo e($tx->currency->value); ?>

                                </td>
                                <td class="p-4 text-slate-600 max-w-xs truncate">
                                    <?php echo e($tx->description); ?>

                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo e($tx->status->badgeClass()); ?>">
                                        <?php echo e($tx->status->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 font-mono text-[11px]">
                                    <?php echo e($tx->posted_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4 text-center">
                                    <a href="<?php echo e(route('admin.finance.transactions.show', $tx)); ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                                        عرض القيود
                                    </a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/finance/index.blade.php ENDPATH**/ ?>