<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>📜</span>
                <span>إدارة الشروط التعاقدية وإصدارات الحملة (Campaign Terms & Versioning)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight"><?php echo e($campaign->title); ?></h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تاريخ وتتبع جميع الإصدارات التعاقدية، وتثبيت الشروط، وطلبات التعديل المعتمدة لحماية حقوق الأطراف.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('brand.campaigns.edit', $campaign)); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للتعديل
            </a>
            <a href="<?php echo e(route('brand.campaigns.change-requests.create', $campaign)); ?>" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>📝</span>
                <span>تقديم طلب تغيير شروط</span>
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Lock State & Version Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">حالة التثبيت التعاقدي (Lock Status)</div>
            <div class="flex items-center gap-2 mt-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->isTermsLocked()): ?>
                    <span class="px-3 py-1 rounded-xl bg-amber-50 text-amber-800 border border-amber-300 text-xs font-black flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>شروط مثبتة ومحمية</span>
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-black flex items-center gap-1.5">
                        <span>🔓</span>
                        <span>شروط قابلة للتعديل المباشر (قبل انضمام المسوقين)</span>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                <?php echo e($campaign->terms_lock_reason ?? 'يتم تثبيت الشروط تلقائياً فور انضمام أول مسوق أو تسجيل أول نقرة إسناد.'); ?>

            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">الإصدار الساري المعتمد حالياً</div>
            <div class="text-2xl font-black text-indigo-700 mt-1">
                v<?php echo e($campaign->currentTermsVersion?->version_number ?? 1); ?>

                <span class="text-xs font-normal text-slate-500">(<?php echo e($campaign->currentTermsVersion?->status->label() ?? 'سارٍ'); ?>)</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                العمولة: <strong><?php echo e($campaign->currentTermsVersion?->commission_value ?? $campaign->commission_value); ?></strong>
                (<?php echo e($campaign->currentTermsVersion?->commission_type?->label() ?? $campaign->commission_type->label()); ?>)
            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">إجمالي طلبات التعديل المقدمة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">
                <?php echo e($changeRequests->count()); ?>

            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                قيد المراجعة: <?php echo e($changeRequests->where('status', \App\Enums\CampaignTermsChangeRequestStatus::PENDING)->count()); ?> |
                معتمد: <?php echo e($changeRequests->where('status', \App\Enums\CampaignTermsChangeRequestStatus::APPROVED)->count()); ?>

            </p>
        </div>
    </div>

    <!-- Terms Versions History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>📑</span>
                <span>سجل الإصدارات التعاقدية للحملة (Terms Version History)</span>
            </h2>
            <span class="text-xs text-slate-500 font-bold">إجمالي الإصدارات: <?php echo e($versions->count()); ?></span>
        </div>

        <div class="divide-y divide-slate-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="p-6 hover:bg-slate-50/60 transition space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                الإصدار v<?php echo e($v->version_number); ?>

                            </span>
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border <?php echo e($v->status->badgeClasses()); ?>">
                                <?php echo e($v->status->label()); ?>

                            </span>
                            <span class="text-xs text-slate-500">
                                تاريخ السريان: <?php echo e($v->effective_from?->format('Y-m-d H:i') ?? '-'); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->effective_until): ?>
                                    — حتى: <?php echo e($v->effective_until->format('Y-m-d H:i')); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                        </div>

                        <div class="text-xs text-slate-400">
                            أنشئ في: <?php echo e($v->created_at?->format('Y-m-d')); ?>

                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/70 text-xs">
                        <div>
                            <span class="text-slate-500 block">نموذج التعويض:</span>
                            <span class="font-bold text-slate-900"><?php echo e($v->commission_type?->label()); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">قيمة العمولة:</span>
                            <span class="font-black text-emerald-700"><?php echo e($v->commission_value); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">مدة الكوكيز:</span>
                            <span class="font-bold text-slate-900"><?php echo e($v->cookie_duration_days); ?> يوماً</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">العملة الأساسية:</span>
                            <span class="font-bold text-slate-900"><?php echo e($v->currency); ?></span>
                        </div>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->conversion_qualification_rules || $v->approval_rules || $v->cancellation_refund_rules): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-1">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->conversion_qualification_rules): ?>
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">شروط احتساب التحويل:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed"><?php echo e($v->conversion_qualification_rules); ?></p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->approval_rules): ?>
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">شروط الموافقة والاعتماد:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed"><?php echo e($v->approval_rules); ?></p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($v->cancellation_refund_rules): ?>
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">قواعد الإلغاء والاسترداد:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed"><?php echo e($v->cancellation_refund_rules); ?></p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>

    <!-- Change Requests History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>🔄</span>
                <span>سجل طلبات تغيير الشروط (Change Requests History)</span>
            </h2>
            <a href="<?php echo e(route('brand.campaigns.change-requests.create', $campaign)); ?>" class="text-xs font-bold text-indigo-700 hover:underline">
                + طلب تعديل جديد
            </a>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($changeRequests->isEmpty()): ?>
            <div class="p-8 text-center text-slate-500 text-xs">
                لم يتم تقديم أي طلبات لتعديل شروط الحملة حتى الآن.
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $changeRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="p-6 hover:bg-slate-50/60 transition space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border <?php echo e($cr->status->badgeClasses()); ?>">
                                    <?php echo e($cr->status->label()); ?>

                                </span>
                                <span class="text-xs font-bold text-slate-700">
                                    نطاق التأثير: <?php echo e($cr->scope->label()); ?>

                                </span>
                            </div>

                            <span class="text-xs text-slate-400">
                                قُدم بتاريخ: <?php echo e($cr->created_at->format('Y-m-d H:i')); ?>

                            </span>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-2">
                            <div>
                                <span class="text-slate-500 font-bold">سبب طلب التعديل:</span>
                                <p class="text-slate-800 mt-0.5 font-medium"><?php echo e($cr->reason); ?></p>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cr->diff_summary): ?>
                                <div class="pt-2 border-t border-slate-200">
                                    <span class="text-slate-500 font-bold block mb-1.5">مقارنة التعديلات المقترحة (Visual Diff):</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cr->diff_summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $diff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <div class="bg-white p-2.5 rounded-xl border border-slate-200 text-[11px]">
                                                <div class="font-bold text-slate-700 mb-1"><?php echo e($diff['label'] ?? $field); ?></div>
                                                <div class="flex items-center gap-2">
                                                    <span class="line-through text-rose-600"><?php echo e($diff['old']); ?></span>
                                                    <span>←</span>
                                                    <span class="font-bold text-emerald-700"><?php echo e($diff['new']); ?></span>
                                                </div>
                                            </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cr->reviewer_notes): ?>
                                <div class="pt-2 border-t border-slate-200">
                                    <span class="text-slate-500 font-bold">ملاحظات مراجعة الإدارة:</span>
                                    <p class="text-slate-800 mt-0.5"><?php echo e($cr->reviewer_notes); ?></p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/brand/campaigns/terms.blade.php ENDPATH**/ ?>