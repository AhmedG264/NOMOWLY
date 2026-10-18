<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>⚖️</span>
                <span>فحص وتدقيق ميزان المراجعة (Trial Balance Audit)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">ميزان المراجعة وتطابق القيود المحاسبية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تدقيق التوازن الرياضي والمحاسبي الصارم بين مجموع الحسابات المدينة والدائنة عبر جميع العملات المدعومة.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.finance.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                المركز المالي
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
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80">
        <a href="<?php echo e(route('admin.finance.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="<?php echo e(route('admin.finance.wallets')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">قائمة المحافظ</a>
        <a href="<?php echo e(route('admin.finance.transactions')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">سجل القيود والمعاملات</a>
        <a href="<?php echo e(route('admin.finance.trial-balance')); ?>" class="text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">ميزان المراجعة والتطابق المحاسبي</a>
    </div>

    <!-- Per-Currency Trial Balance Cards -->
    <div class="space-y-6">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $trialBalance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $currencyCode => $tb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xs font-mono">
                            <?php echo e($currencyCode); ?>

                        </span>
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                ميزان المراجعة لعملة: <?php echo e(\App\Enums\Currency::tryFrom($currencyCode)?->label() ?? $currencyCode); ?>

                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">عدد القيود المحاسبية المسجلة: <?php echo e($tb['entries_count']); ?> قيد</p>
                        </div>
                    </div>

                    <div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tb['is_balanced']): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span>✓</span>
                                <span>متوازن تماماً (الفرق = 0.0000)</span>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                                <span>⚠️</span>
                                <span>تفاوت: <?php echo e($tb['difference']); ?> <?php echo e($currencyCode); ?></span>
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($tb['accounts'])): ?>
                    <div class="p-8 text-center text-slate-400 text-xs">
                        لا توجد قيود مرحلة بهذه العملة حتى الآن.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                <tr>
                                    <th class="p-4">رمز الحساب</th>
                                    <th class="p-4">اسم الحساب</th>
                                    <th class="p-4">نوع الحساب</th>
                                    <th class="p-4 text-emerald-800">إجمالي المدين (Debit)</th>
                                    <th class="p-4 text-purple-800">إجمالي الدائن (Credit)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tb['accounts']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="p-4 font-mono font-bold text-slate-900">
                                            <?php echo e($acc['code']); ?>

                                        </td>
                                        <td class="p-4 font-bold text-slate-800">
                                            <?php echo e($acc['name_ar']); ?>

                                        </td>
                                        <td class="p-4 text-slate-500">
                                            <?php echo e($acc['type']); ?>

                                        </td>
                                        <td class="p-4 font-mono font-bold text-emerald-700">
                                            <?php echo e(\App\Services\Math\MoneyMath::display($acc['debit'], $currencyCode)); ?>

                                        </td>
                                        <td class="p-4 font-mono font-bold text-purple-700">
                                            <?php echo e(\App\Services\Math\MoneyMath::display($acc['credit'], $currencyCode)); ?>

                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                                <tr>
                                    <td colspan="3" class="p-4 text-left font-black text-slate-800">
                                        المجموع الإجمالي (Total):
                                    </td>
                                    <td class="p-4 font-mono font-black text-emerald-700">
                                        <?php echo e(\App\Services\Math\MoneyMath::display($tb['total_debit'], $currencyCode)); ?> <?php echo e($currencyCode); ?>

                                    </td>
                                    <td class="p-4 font-mono font-black text-purple-700">
                                        <?php echo e(\App\Services\Math\MoneyMath::display($tb['total_credit'], $currencyCode)); ?> <?php echo e($currencyCode); ?>

                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/finance/trial-balance.blade.php ENDPATH**/ ?>