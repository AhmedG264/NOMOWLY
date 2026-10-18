<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="<?php echo e(route('admin.campaigns.change-requests.index')); ?>" class="text-xs font-bold text-slate-500 hover:text-indigo-700 transition">
                ← العودة إلى قائمة طلبات التعديل
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">تدقيق ومراجعة طلب تعديل شروط الحملة</h1>
            <p class="text-xs text-slate-500 mt-1">الحملة: <?php echo e($changeRequest->campaign?->title); ?> • العلامة: <?php echo e($changeRequest->campaign?->brandProfile?->company_name); ?></p>
        </div>

        <span class="px-3 py-1.5 rounded-xl text-xs font-black border <?php echo e($changeRequest->status->badgeClasses()); ?>">
            <?php echo e($changeRequest->status->label()); ?>

        </span>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="font-black">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="list-disc list-inside text-xs font-normal">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($error); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Request Meta Info -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">بيانات الطلب وسياقه التجاري</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block mb-0.5">مقدم الطلب:</span>
                <span class="font-black text-slate-900"><?php echo e($changeRequest->requestedBy?->name); ?></span>
                <span class="text-slate-400 block text-[11px]">(<?php echo e($changeRequest->requestedBy?->email); ?>)</span>
            </div>

            <div>
                <span class="text-slate-500 block mb-0.5">نطاق تطبيق التعديل (Scope):</span>
                <span class="font-black text-indigo-700"><?php echo e($changeRequest->scope->label()); ?></span>
            </div>

            <div>
                <span class="text-slate-500 block mb-0.5">تاريخ التقديم:</span>
                <span class="font-bold text-slate-900"><?php echo e($changeRequest->created_at->format('Y-m-d H:i')); ?></span>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <span class="text-xs text-slate-500 font-bold block mb-1">سبب التعديل الموضح من العلامة:</span>
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-800 leading-relaxed font-medium">
                <?php echo e($changeRequest->reason); ?>

            </div>
        </div>
    </div>

    <!-- Visual Diff (Side-by-Side Comparison) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
            <span>مقارنة الفروقات بين الشروط السارية والشروط المقترحة (Visual Diff)</span>
            <span class="text-xs text-slate-500 font-bold">من الإصدار v<?php echo e($changeRequest->currentVersion?->version_number ?? 1); ?> إلى v<?php echo e(($changeRequest->currentVersion?->version_number ?? 1) + 1); ?></span>
        </h2>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($changeRequest->diff_summary): ?>
            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $changeRequest->diff_summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $diff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                        <div class="text-xs font-black text-slate-800">
                            <?php echo e($diff['label'] ?? $field); ?>

                        </div>

                        <!-- Old Value -->
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
                            <span class="text-[10px] text-rose-500 block font-bold mb-0.5">الشروط السارية الحالية:</span>
                            <span class="font-black line-through"><?php echo e($diff['old']); ?></span>
                        </div>

                        <!-- New Value -->
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800">
                            <span class="text-[10px] text-emerald-600 block font-bold mb-0.5">الشروط المقترحة الجديدة:</span>
                            <span class="font-black"><?php echo e($diff['new']); ?></span>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="p-4 bg-slate-50 rounded-2xl text-xs text-slate-500">
                لا توجد فروقات محددة في الملخص.
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Reviewer Actions or Outcome -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($changeRequest->isPending()): ?>
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">قرار المراجعة والتدقيق الإداري</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Approve Form -->
                <form method="POST" action="<?php echo e(route('admin.campaigns.change-requests.approve', $changeRequest)); ?>" class="p-5 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="flex items-center gap-2 text-emerald-900 font-black text-sm">
                        <span>✓</span>
                        <span>اعتماد التعديل وترقية إصدار الحملة</span>
                    </div>
                    <p class="text-xs text-emerald-800 leading-relaxed">
                        سيؤدي الاعتماد إلى إنشاء الإصدار v<?php echo e(($changeRequest->currentVersion?->version_number ?? 1) + 1); ?> وترقية الشروط السارية على الحملة فوراً دون التأثير على التحويلات السابقة.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات الاعتماد (اختياري)</label>
                        <textarea name="reviewer_notes" rows="2" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white" placeholder="أدخل أي ملاحظات إدارية ترغب بتسجيلها في سجل التدقيق..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-sm transition cursor-pointer">
                        اعتماد طلب التغيير وترقية الإصدار
                    </button>
                </form>

                <!-- Reject Form -->
                <form method="POST" action="<?php echo e(route('admin.campaigns.change-requests.reject', $changeRequest)); ?>" class="p-5 bg-rose-50/50 rounded-2xl border border-rose-200 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="flex items-center gap-2 text-rose-900 font-black text-sm">
                        <span>✕</span>
                        <span>رفض طلب التعديل</span>
                    </div>
                    <p class="text-xs text-rose-800 leading-relaxed">
                        سيتم إخطار العلامة التجارية برفض الطلب، والإبقاء على الشروط السارية الحالية دون أي تعديل.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">سبب الرفض الإداري (إلزامي) *</label>
                        <textarea name="reviewer_notes" required rows="2" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white" placeholder="وضح أسباب الرفض بالتفصيل لمشاركتها مع العلامة التجارية..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-sm transition cursor-pointer">
                        رفض طلب التعديل
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Completed Outcome Details -->
        <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200 text-xs space-y-3">
            <h3 class="font-black text-slate-900 text-sm">تفاصيل نتيجة المراجعة الإدارية</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <span class="text-slate-500 block">تمت المراجعة بواسطة:</span>
                    <span class="font-bold text-slate-900"><?php echo e($changeRequest->reviewedBy?->name ?? 'المدير العام'); ?></span>
                </div>
                <div>
                    <span class="text-slate-500 block">تاريخ القرار:</span>
                    <span class="font-bold text-slate-900"><?php echo e($changeRequest->reviewed_at?->format('Y-m-d H:i')); ?></span>
                </div>
                <div>
                    <span class="text-slate-500 block">الحالة النهائية:</span>
                    <span class="font-black <?php echo e($changeRequest->isApproved() ? 'text-emerald-700' : 'text-rose-700'); ?>">
                        <?php echo e($changeRequest->status->label()); ?>

                    </span>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($changeRequest->reviewer_notes): ?>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-500 block font-bold mb-1">ملاحظات المراجع:</span>
                    <p class="text-slate-800 font-medium"><?php echo e($changeRequest->reviewer_notes); ?></p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/campaigns/change-request-show.blade.php ENDPATH**/ ?>