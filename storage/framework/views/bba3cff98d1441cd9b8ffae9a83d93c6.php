<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="<?php echo e(route('marketer.subscriptions.index')); ?>" class="text-xs font-bold text-purple-700 hover:text-purple-900 transition flex items-center gap-1 mb-2">
                <span>←</span>
                <span>العودة لسجل الاشتراكات</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900">تفاصيل باقة: <?php echo e($subscription->plan_snapshot['name_ar'] ?? $subscription->plan?->name_ar); ?></h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?php echo e($subscription->status->badgeClass()); ?>">
                    <?php echo e($subscription->status->label()); ?>

                </span>
            </div>
            <p class="text-slate-500 text-xs sm:text-sm mt-0.5">رقم الاشتراك: <span class="font-mono font-bold text-purple-950"><?php echo e($subscription->subscription_number); ?></span></p>
        </div>

        <a href="<?php echo e(route('marketer.plans.index')); ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition">
            ترقية أو تغيير الباقة
        </a>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4 shadow-xs">
            <h2 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-2">فترة وسريان الاشتراك</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">حالة الاشتراك:</span>
                    <span class="font-bold <?php echo e($subscription->isActive() ? 'text-emerald-700' : 'text-slate-700'); ?>"><?php echo e($subscription->status->label()); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">تاريخ التفعيل:</span>
                    <span class="font-bold font-mono"><?php echo e($subscription->starts_at ? $subscription->starts_at->format('Y-m-d') : 'غير محدد'); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">تاريخ انتهاء الصلاحية:</span>
                    <span class="font-bold font-mono text-purple-900"><?php echo e($subscription->ends_at ? $subscription->ends_at->format('Y-m-d') : 'غير محدد'); ?></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">الأيام المتبقية:</span>
                    <span class="font-bold font-mono <?php echo e($subscription->daysRemaining() > 5 ? 'text-emerald-600' : 'text-amber-600'); ?>">
                        <?php echo e($subscription->daysRemaining()); ?> يوماً
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4 shadow-xs">
            <h2 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-2">معلومات الدفع والتسوية</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">المبلغ المدفوع:</span>
                    <span class="font-mono font-black text-slate-900"><?php echo e(number_format((float)$subscription->price_paid, 2)); ?> <?php echo e($subscription->currency->value); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">طريقة السداد:</span>
                    <span class="font-bold"><?php echo e($subscription->payment_method === 'WALLET' ? 'خصم من رصيد المحفظة' : 'بوابة الدفع الإلكتروني'); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">المرجع المالي:</span>
                    <span class="font-mono font-bold"><?php echo e($subscription->payment_reference ?: 'مباشر'); ?></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">رقم القيد المحاسبي:</span>
                    <span class="font-mono text-purple-700 font-bold"><?php echo e($subscription->ledgerTransaction?->transaction_number ?: 'TX-AUTO'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Entitlements Checklist -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-xs">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">المزايا والصلاحيات الممنوحة في باقتك</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-1">
                <div class="flex items-center gap-2 font-black text-sm">
                    <span>✓</span>
                    <span>المشاركة بالحملات الحصرية والمميزة</span>
                </div>
                <p class="text-xs text-emerald-700">تتيح لك هذه الباقة التقديم على كافة الحملات المميزة المنشورة في سوق نمولي.</p>
            </div>

            <div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 space-y-1">
                <div class="flex items-center gap-2 font-black text-sm">
                    <span>★</span>
                    <span>الحد الشهري للحملات المميزة:</span>
                </div>
                <p class="text-xs text-purple-700 font-bold">
                    <?php echo e($subscription->entitlement('max_premium_campaigns_per_month') ? $subscription->entitlement('max_premium_campaigns_per_month') . ' حملات شهرياً' : 'عدد غير محدود من الحملات'); ?>

                </p>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($subscription->plan_snapshot['features'])): ?>
            <div class="pt-3 border-t border-slate-100 space-y-2">
                <span class="text-xs font-bold text-slate-600 block">تفاصيل المزايا التعاقدية وقت الشراء:</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $subscription->plan_snapshot['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="flex items-center gap-2 text-xs text-slate-700">
                            <span class="text-purple-600 font-bold">✓</span>
                            <span><?php echo e($f); ?></span>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Rights Protection Card -->
    <div class="p-5 rounded-3xl bg-slate-900 text-white border border-slate-800 space-y-2">
        <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs">
            <span>🛡️</span>
            <span>حماية الحقوق التعاقدية والمالية:</span>
        </div>
        <p class="text-xs text-slate-300 leading-relaxed">
            وفقاً لسياسة نمولي الصارمة، انتهاء صلاحية باقتك في أي وقت لا يؤدي بأي حال إلى إلغاء مشاركاتك القائمة أو مساس بأرباحك المحققة أو منعك من سحب عمولاتك المستحقة من محفظتك.
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/marketer/subscriptions/show.blade.php ENDPATH**/ ?>