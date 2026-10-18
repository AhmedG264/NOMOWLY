<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>💸</span>
                <span>إدارة طلبات سحب الأرباح للمسوقين • Marketer Payout Requests</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">طلبات سحب الأرباح المصرفية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                مراجعة وتدقيق واعتماد طلبات سحب الأرباح المقدمة من المسوقين وتأكيد التحويلات المصرفية بالدفتر المحاسبي.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.finance.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← المركز المالي
            </a>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Sub-navigation links -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="<?php echo e(route('admin.finance.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="<?php echo e(route('admin.finance.wallets')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المحافظ</a>
        <a href="<?php echo e(route('admin.finance.transactions')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">سجل القيود</a>
        <a href="<?php echo e(route('admin.finance.trial-balance')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">ميزان المراجعة</a>
        <a href="<?php echo e(route('admin.finance.payouts.index')); ?>" class="text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">طلبات السحب (<?php echo e($stats['total']); ?>)</a>
        <a href="<?php echo e(route('admin.finance.settlements.index')); ?>" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">تسويات العمولات</a>
    </div>

    <!-- Status Alerts -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="flex items-center gap-2">
                <span class="text-rose-600 font-black">✕</span>
                <span>حدث خطأ:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-medium text-rose-700">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($err); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي الطلبات</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e(number_format($stats['total'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-amber-200/80 shadow-xs">
            <div class="text-xs text-amber-700 font-bold">بانتظار المراجعة</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e(number_format($stats['pending'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-blue-200/80 shadow-xs">
            <div class="text-xs text-blue-700 font-bold">معتمدة وبانتظار التحويل</div>
            <div class="text-2xl font-black text-blue-600 mt-1"><?php echo e(number_format($stats['approved'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-emerald-200/80 shadow-xs">
            <div class="text-xs text-emerald-700 font-bold">تم الصرف بنجاح</div>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo e(number_format($stats['paid'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-rose-200/80 shadow-xs">
            <div class="text-xs text-rose-700 font-bold">مرفوضة / مسترجعة</div>
            <div class="text-2xl font-black text-rose-600 mt-1"><?php echo e(number_format($stats['rejected'])); ?></div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="<?php echo e(route('admin.finance.payouts.index')); ?>" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="text" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="ابحث برقم الطلب، اسم المسوق، أو المرجع..."
                   class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs w-64 focus:ring-2 focus:ring-emerald-500 focus:outline-none">

            <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="">جميع الحالات</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($st->value); ?>" <?php echo e(($filters['status'] ?? '') === $st->value ? 'selected' : ''); ?>>
                        <?php echo e($st->label()); ?>

                    </option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>

            <select name="currency" class="px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="">جميع العملات</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($curr->value); ?>" <?php echo e(($filters['currency'] ?? '') === $curr->value ? 'selected' : ''); ?>>
                        <?php echo e($curr->value); ?>

                    </option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                تصفية
            </button>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($filters['q']) || !empty($filters['status']) || !empty($filters['currency'])): ?>
                <a href="<?php echo e(route('admin.finance.payouts.index')); ?>" class="text-xs text-slate-500 hover:text-slate-800 underline">
                    إعادة ضبط
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </form>
    </div>

    <!-- Payout Requests Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($payoutRequests->isEmpty()): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد طلبات سحب تطابق معايير البحث المحددة.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الطلب</th>
                            <th class="p-4">المسوق</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">البنك والحساب المظلل</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">مرجع التحويل</th>
                            <th class="p-4">تاريخ الطلب</th>
                            <th class="p-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $payoutRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    <?php echo e($req->request_number); ?>

                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900"><?php echo e($req->user->name); ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?php echo e($req->user->email); ?></div>
                                </td>
                                <td class="p-4 font-black text-slate-900">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($req->amount, $req->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($req->currency->value); ?></span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?php echo e($req->bank_name ?? '—'); ?></div>
                                    <div class="font-mono text-[11px] text-slate-500 dir-ltr text-right mt-0.5"><?php echo e($req->account_reference ?? '—'); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($req->status->badgeClass()); ?>">
                                        <?php echo e($req->status->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 font-mono text-slate-600">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($req->payment_reference): ?>
                                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            <?php echo e($req->payment_reference); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500">
                                    <?php echo e($req->created_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4 text-center">
                                    <a href="<?php echo e(route('admin.finance.payouts.show', $req)); ?>"
                                       class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-[11px] font-bold shadow-xs transition inline-block">
                                        معاينة وإجراء ←
                                    </a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                <?php echo e($payoutRequests->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/finance/payouts/index.blade.php ENDPATH**/ ?>