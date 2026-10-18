<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>⚖️</span>
                <span>المطابقة المالية مع بوابات الدفع • Payment Reconciliation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">تقرير المطابقة المالية وفحص المزودين</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                رصد الفروقات، مراجعة العمليات العالقة أو غير المكتملة، وتأكيد سلامة القيود المحاسبية مع دفاتر المزودين.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.finance.deposits.index')); ?>" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                سجل الإيداعات
            </a>
            <a href="<?php echo e(route('admin.finance.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                المركز المالي
            </a>
        </div>
    </div>

    <!-- Navigation Bar -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="<?php echo e(route('admin.finance.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="<?php echo e(route('admin.finance.deposits.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">عمليات الإيداع والشحن</a>
        <a href="<?php echo e(route('admin.finance.refunds.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">الاستردادات المالية</a>
        <a href="<?php echo e(route('admin.finance.reconciliation.index')); ?>" class="text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-200 font-black">المطابقة المالية مع المزودين</a>
        <a href="<?php echo e(route('admin.finance.wallets')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المحافظ</a>
        <a href="<?php echo e(route('admin.finance.transactions')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">دفتر القيود</a>
    </div>

    <!-- Reconciliation Alerts -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unlinkedDeposits->isNotEmpty()): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-3xl p-6 text-xs text-rose-900 space-y-3">
            <div class="flex items-center gap-2 font-black text-sm text-rose-800">
                <span>⚠️</span>
                <span>تنبيه خطير: توجد عمليات مؤكدة بدون قيود محاسبية مرحلة!</span>
            </div>
            <p>تم رصد عمليات إيداع بحالة ناجحة (SUCCEEDED) لكنها تفتقر إلى معرف قيد محاسبي مرحل في دفتر الأستاذ. يجب فحصها فوراً:</p>
            <div class="space-y-1 font-mono">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $unlinkedDeposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div>• إيداع #<?php echo e($ud->deposit_number); ?> - مبلغ <?php echo e($ud->amount); ?> <?php echo e($ud->currency->value); ?> للمستخدم <?php echo e($ud->user->email); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-xs text-emerald-900 flex items-center gap-3">
            <span class="text-2xl">✓</span>
            <div>
                <span class="font-black text-sm block">كافة العمليات الناجحة مرتبطة بقيود محاسبية متطابقة:</span>
                <p class="text-emerald-700">لا توجد عمليات إيداع ناجحة تفتقر إلى قيود دفتر أستاذ متوازنة.</p>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Pending / Incomplete Deposits Older Than 30 mins -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">عمليات دفع معلقة تحتاج فحص المزود (Pending > 30 mins)</h3>
                <p class="text-xs text-slate-500 mt-0.5">عمليات بدأها العملاء ولم يرد إشعار تأكيد نهائي لها بعد</p>
            </div>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                <?php echo e($pendingDeposits->count()); ?> معلقة
            </span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingDeposits->isEmpty()): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                لا توجد عمليات إيداع معلقة متأخرة حالياً.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الإيداع</th>
                            <th class="p-4">العميل</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">المزود</th>
                            <th class="p-4">تاريخ الإنشاء</th>
                            <th class="p-4 text-center">إجراء المطابقة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $pendingDeposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900"><?php echo e($pd->deposit_number); ?></td>
                                <td class="p-4"><?php echo e($pd->user->email); ?></td>
                                <td class="p-4 font-bold"><?php echo e($pd->amount); ?> <?php echo e($pd->currency->value); ?></td>
                                <td class="p-4"><?php echo e($pd->provider->label()); ?></td>
                                <td class="p-4 font-mono text-slate-500"><?php echo e($pd->created_at->diffForHumans()); ?></td>
                                <td class="p-4 text-center">
                                    <form method="POST" action="<?php echo e(route('admin.finance.deposits.reconcile', $pd)); ?>" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-[11px] cursor-pointer">
                                            فحص لدى المزود
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Failed Webhooks Audit -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل إشعارات Webhook غير الناجحة أو المرفوضة أمنياً</h3>
                <p class="text-xs text-slate-500 mt-0.5">إشعارات تلقتها المنصة وواجهت أخطاء توقيع أو تعارض مبالغ</p>
            </div>
            <span class="text-xs font-bold text-slate-500">العدد: <?php echo e($failedWebhooks->count()); ?></span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($failedWebhooks->isEmpty()): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                لا توجد إشعارات Webhook فاشلة مسجلة. كافة الإشعارات تمت معالجتها بسلامة تامة.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">معرف الحدث</th>
                            <th class="p-4">المزود</th>
                            <th class="p-4">نوع الحدث</th>
                            <th class="p-4">رسالة الخطأ</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $failedWebhooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fw): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900"><?php echo e($fw->event_id); ?></td>
                                <td class="p-4 font-bold"><?php echo e($fw->provider->label()); ?></td>
                                <td class="p-4 font-mono text-slate-600"><?php echo e($fw->event_type); ?></td>
                                <td class="p-4 text-rose-600 font-medium"><?php echo e($fw->error_message ?? 'فشل المعالجة'); ?></td>
                                <td class="p-4 font-mono text-slate-500"><?php echo e($fw->created_at->format('Y-m-d H:i:s')); ?></td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/finance/deposits/reconciliation.blade.php ENDPATH**/ ?>