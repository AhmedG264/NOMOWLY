<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>🛡️</span>
                <span>الإدارة العليا (Super Admin) • مراجعة الشروط التعاقدية</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">طلبات تعديل شروط الحملات التسويقية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تدقيق طلبات العلامات التجارية لتعديل العمولات أو الكوكيز، والتأكد من صون حقوق المسوقين والامتثال التعاقدي.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للحملات
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="<?php echo e(route('admin.campaigns.change-requests.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-black bg-indigo-900 text-white shadow-xs">
            طلبات تعديل الشروط (<?php echo e($stats['pending']); ?> قيد المراجعة)
        </a>
        <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>🛡️</span>
            <span>سجل العمليات والمراقبة</span>
        </a>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي طلبات التعديل</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e($stats['total']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">طلبات قيد المراجعة</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e($stats['pending']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الطلبات المعتمدة</div>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo e($stats['approved']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الطلبات المرفوضة</div>
            <div class="text-2xl font-black text-rose-600 mt-1"><?php echo e($stats['rejected']); ?></div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-base font-black text-slate-900">سجل طلبات التعديل</h2>

            <div class="flex items-center gap-2 text-xs">
                <a href="<?php echo e(route('admin.campaigns.change-requests.index')); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(!request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'); ?>">الكل</a>
                <a href="<?php echo e(route('admin.campaigns.change-requests.index', ['status' => 'PENDING'])); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(request('status') === 'PENDING' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700'); ?>">قيد المراجعة</a>
                <a href="<?php echo e(route('admin.campaigns.change-requests.index', ['status' => 'APPROVED'])); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(request('status') === 'APPROVED' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'); ?>">المعتمدة</a>
                <a href="<?php echo e(route('admin.campaigns.change-requests.index', ['status' => 'REJECTED'])); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(request('status') === 'REJECTED' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700'); ?>">المرفوضة</a>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($changeRequests->isEmpty()): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد طلبات تعديل شروط مسجلة في هذا التبويب.
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $changeRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="p-6 hover:bg-slate-50/60 transition flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-3xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black border <?php echo e($cr->status->badgeClasses()); ?>">
                                    <?php echo e($cr->status->label()); ?>

                                </span>
                                <span class="text-xs font-bold text-slate-500">الحملة:</span>
                                <span class="text-xs font-black text-slate-900"><?php echo e($cr->campaign?->title); ?></span>
                                <span class="text-xs text-slate-400">(<?php echo e($cr->campaign?->brandProfile?->company_name); ?>)</span>
                            </div>

                            <p class="text-xs text-slate-700 font-medium">
                                <strong>السبب:</strong> <?php echo e($cr->reason); ?>

                            </p>

                            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500">
                                <span>نطاق التأثير: <strong><?php echo e($cr->scope->label()); ?></strong></span>
                                <span>•</span>
                                <span>تاريخ التقديم: <?php echo e($cr->created_at->format('Y-m-d H:i')); ?></span>
                                <span>•</span>
                                <span>بواسطة: <?php echo e($cr->requestedBy?->name); ?></span>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3">
                            <a href="<?php echo e(route('admin.campaigns.change-requests.show', $cr)); ?>" class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl text-xs font-black transition">
                                مراجعة وتدقيق الطلب ←
                            </a>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <div class="p-4 border-t border-slate-100">
                <?php echo e($changeRequests->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/campaigns/change-requests.blade.php ENDPATH**/ ?>