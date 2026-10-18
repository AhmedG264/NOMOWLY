<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ payoutModalOpen: false, selectedWalletId: '<?php echo e($activeWallet->id); ?>', selectedCurrency: '<?php echo e($selectedCurrency); ?>', maxAvailable: '<?php echo e(\App\Services\Math\MoneyMath::format($activeWallet->available_balance)); ?>', submittingPayout: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-teal-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold mb-3 border border-teal-400/30">
                <span>💳</span>
                <span>المحفظة المالية والأرباح المستحقة • Marketer Wallet & Payouts</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">محفظتي وأرباح التسويق</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                المسوق: <span class="text-white font-bold"><?php echo e(auth()->user()->marketerProfile?->full_name ?? auth()->user()->name); ?></span>
                • العملة النشطة: <span class="text-teal-400 font-bold"><?php echo e($selectedCurrency); ?></span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(\App\Services\Math\MoneyMath::isPositive($activeWallet->available_balance)): ?>
                <button type="button" @click="payoutModalOpen = true; selectedWalletId = '<?php echo e($activeWallet->id); ?>'; selectedCurrency = '<?php echo e($selectedCurrency); ?>'; maxAvailable = '<?php echo e(\App\Services\Math\MoneyMath::format($activeWallet->available_balance)); ?>'"
                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2 cursor-pointer">
                    <span>💸</span>
                    <span>طلب سحب أرباح</span>
                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <a href="<?php echo e(route('marketer.campaigns.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                حملاتي والأكواد
            </a>
            <a href="<?php echo e(route('marketer.dashboard')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-500 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="flex items-center gap-2">
                <span class="text-rose-600 font-black">✕</span>
                <span>حدث خطأ أثناء معالجة الطلب:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-medium text-rose-700">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($err); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Wallets Cards Grid -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">أرصدة المحافظ بالعملات المعتمدة</h2>
                <p class="text-xs text-slate-500">معزولة ومستقلة محاسبياً لكل عملة على حدة، ولا يتم خلط أسعار الصرف</p>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reconciliation): ?>
                <div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reconciliation['is_matched']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span>✓</span>
                            <span>مطابقة الرصيد: 100% متطابق مع دفتر الأستاذ</span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <span>⚠️</span>
                            <span>تفاوت بين الرصيد ودفتر الأستاذ قيد التدقيق الإداري</span>
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $wallet = $wallets[$curr->value] ?? null;
                    $avail = $wallet ? \App\Services\Math\MoneyMath::format($wallet->available_balance) : '0.0000';
                    $reserved = $wallet ? \App\Services\Math\MoneyMath::format($wallet->reserved_balance) : '0.0000';
                    $hasBalance = \App\Services\Math\MoneyMath::isPositive($avail);
                    $isSelected = $selectedCurrency === $curr->value;
                ?>
                <div class="bg-white rounded-3xl border <?php echo e($isSelected ? 'border-teal-400 ring-2 ring-teal-500/20' : ($hasBalance ? 'border-slate-300' : 'border-slate-200/80')); ?> p-5 shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <a href="<?php echo e(route('marketer.wallet.index', ['currency' => $curr->value])); ?>"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-black <?php echo e($isSelected ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-800 hover:bg-slate-200'); ?>">
                                <span><?php echo e($curr->symbol()); ?></span>
                                <span><?php echo e($curr->value); ?></span>
                            </a>
                            <span class="text-[11px] font-bold text-slate-400"><?php echo e($curr->label()); ?></span>
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs text-slate-500 font-medium">الرصيد المتاح للسحب</div>
                            <div class="text-2xl font-black text-slate-900 tracking-tight">
                                <?php echo e(\App\Services\Math\MoneyMath::display($avail, $curr->value)); ?>

                                <span class="text-xs font-bold text-slate-400"><?php echo e($curr->value); ?></span>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(\App\Services\Math\MoneyMath::isPositive($reserved)): ?>
                            <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-amber-600 font-medium flex items-center justify-between">
                                <span>معلق بطلب سحب:</span>
                                <span class="font-bold"><?php echo e(\App\Services\Math\MoneyMath::display($reserved, $curr->value)); ?></span>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wallet && $hasBalance): ?>
                            <button type="button"
                                    @click="payoutModalOpen = true; selectedWalletId = '<?php echo e($wallet->id); ?>'; selectedCurrency = '<?php echo e($curr->value); ?>'; maxAvailable = '<?php echo e($avail); ?>'"
                                    class="w-full py-2 px-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-black transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                                <span>💸</span>
                                <span>طلب سحب أرباح</span>
                            </button>
                        <?php else: ?>
                            <button type="button" disabled
                                    class="w-full py-2 px-3 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold cursor-not-allowed">
                                لا يوجد رصيد متاح
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <a href="<?php echo e(route('marketer.wallet.index', ['currency' => $curr->value])); ?>"
                           class="block text-center text-[11px] font-bold <?php echo e($isSelected ? 'text-teal-700 font-black' : 'text-slate-400 hover:text-slate-600'); ?>">
                            <?php echo e($isSelected ? '• العملة النشطة للكشف' : 'تصفية الكشف بهذه العملة ←'); ?>

                        </a>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>

    <!-- Financial Performance Metrics (Selected Currency) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">الرصيد المتاح (<?php echo e($selectedCurrency); ?>)</div>
            <div class="text-xl font-black text-teal-600 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($activeWallet->available_balance, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">جاهز للسحب الفوري</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">معلق بطلب سحب</div>
            <div class="text-xl font-black text-amber-600 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($activeWallet->reserved_balance, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-amber-600/80 mt-0.5">محجوز بانتظار التحويل</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات مرحلة بالمحفظة</div>
            <div class="text-xl font-black text-emerald-700 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($settledCommissionSum, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-emerald-600 font-medium">أضيفت للرصيد الدفتري</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات معتمدة ومستحقة</div>
            <div class="text-xl font-black text-slate-900 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($approvedCommissionSum, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-slate-500 font-medium">مبيعات معتمدة من المتاجر</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">عمولات قيد المراجعة</div>
            <div class="text-xl font-black text-slate-600 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($pendingCommissionSum, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">تنتظر فحص الدفع والشحن</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] text-slate-500 font-bold">أرباح تم استلامها بالفعل</div>
            <div class="text-xl font-black text-emerald-600 mt-1">
                <?php echo e(\App\Services\Math\MoneyMath::display($totalActuallyPaidOut, $selectedCurrency)); ?>

            </div>
            <div class="text-[10px] text-emerald-600/80 mt-0.5">حُولت لحسابك البنكي</div>
        </div>
    </div>

    <!-- Earnings Breakdown by Campaign (سجل الأرباح حسب الحملة) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل الأرباح والعمولات حسب الحملة التسويقية</h3>
                <p class="text-xs text-slate-500 mt-0.5">تفصيل العمولات المضافة لمحفظتك من كل حملة تسويقية بالعملة المحددة (<?php echo e($selectedCurrency); ?>)</p>
            </div>
            <span class="text-xs font-bold text-slate-400">إجمالي الحملات المنتجة: <?php echo e($campaignEarnings->count()); ?></span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaignEarnings->isEmpty()): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                لا توجد أرباح مسجلة بهذه العملة (<?php echo e($selectedCurrency); ?>) حتى الآن. عند اعتماد تحويلات مبيعاتك ستظهر هنا مصنفة حسب الحملة.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">العلامة التجارية</th>
                            <th class="p-4">عدد التحويلات المعتمدة</th>
                            <th class="p-4">إجمالي العمولات المستلمة</th>
                            <th class="p-4">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $campaignEarnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ce): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-bold text-slate-900">
                                    <?php echo e($ce['campaign_title']); ?>

                                </td>
                                <td class="p-4 text-slate-600 font-medium">
                                    <?php echo e($ce['company_name']); ?>

                                </td>
                                <td class="p-4 font-mono font-bold text-slate-800">
                                    <?php echo e($ce['settled_count']); ?> طلب
                                </td>
                                <td class="p-4 font-black text-emerald-600 text-sm">
                                    +<?php echo e(\App\Services\Math\MoneyMath::display($ce['total_earned'], $ce['currency'])); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($ce['currency']); ?></span>
                                </td>
                                <td class="p-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($ce['campaign_slug'])): ?>
                                        <a href="<?php echo e(route('marketplace.show', $ce['campaign_slug'])); ?>" class="text-teal-700 hover:text-teal-900 font-bold underline">
                                            تفاصيل الحملة ←
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo e(route('marketer.campaigns.index')); ?>" class="text-teal-700 hover:text-teal-900 font-bold underline">
                                            حملاتي ←
                                        </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Payout Request Modal -->
    <div x-show="payoutModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="payoutModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-right space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg font-black">💸</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900">طلب سحب أرباح مصرفي</h3>
                        <p class="text-xs text-slate-500">عملة السحب: <span class="font-bold text-teal-600" x-text="selectedCurrency"></span></p>
                    </div>
                </div>
                <button type="button" @click="payoutModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" action="<?php echo e(route('marketer.wallet.payout-request')); ?>" class="space-y-4" @submit="if(submittingPayout){ $event.preventDefault(); return false; } submittingPayout = true;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="wallet_id" :value="selectedWalletId">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        المبلغ المطلوب سحبه (<span x-text="selectedCurrency"></span>) *
                    </label>
                    <div class="relative">
                        <input type="number" step="any" name="amount" required min="1" :max="maxAvailable"
                               placeholder="أدخل المبلغ المطلوب"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        الحد الأقصى المتاح حالياً: <span class="font-bold text-slate-800" x-text="maxAvailable"></span> <span x-text="selectedCurrency"></span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم البنك / المصرف *</label>
                    <input type="text" name="bank_name" required placeholder="مثال: مصرف الراجحي / البنك الأهلي السعودي"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم صاحب الحساب (المستفيد) *</label>
                    <input type="text" name="account_holder_name" required placeholder="الاسم المطابق لبطاقة الحساب البنكي"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رقم الآيبان (IBAN) أو رقم الحساب *</label>
                    <input type="text" name="account_reference" required placeholder="SA0000000000000000000000"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-teal-500 focus:outline-none dir-ltr text-right">
                    <p class="text-[10px] text-slate-400 mt-1">يتم تشفير وتظليل رقم الحساب تلقائياً لحماية خصوصيتك المصرفية، ولا يظهر علناً لأي جهة.</p>
                </div>

                <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-3 text-[11px] text-amber-800 space-y-1">
                    <p class="font-bold">⚠️ تنبيه أمني وإجرائي:</p>
                    <p>عند تأكيد الطلب، سيتم حجز المبلغ فورياً من رصيدك المتاح لمنع ازدواجية الصرف، وستتم مراجعته والتحويل لحسابك خلال يومين عمل.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="payoutModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="submittingPayout"
                            class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-black shadow-md transition cursor-pointer flex items-center gap-2">
                        <span x-show="!submittingPayout">تأكيد وإرسال طلب السحب</span>
                        <span x-show="submittingPayout" style="display:none;">جارٍ المعالجة...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Statement (كشف الحساب التفصيلي للمسوق) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-900">كشف حساب الحركات والعمليات المالية</h3>
                <p class="text-xs text-slate-500 mt-0.5">سجل الحركات المصرفية والعمولات المرحلة في دفتر القيود المحاسبي</p>
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="<?php echo e(route('marketer.wallet.index')); ?>" class="flex flex-wrap items-center gap-2 text-xs w-full lg:w-auto">
                <input type="hidden" name="currency" value="<?php echo e($selectedCurrency); ?>">

                <select name="transaction_type" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <option value="">جميع أنواع الحركات</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\LedgerTransactionType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($type->value); ?>" <?php echo e(($filters['transaction_type'] ?? '') === $type->value ? 'selected' : ''); ?>>
                            <?php echo e($type->label()); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\LedgerTransactionStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($st->value); ?>" <?php echo e(($filters['status'] ?? '') === $st->value ? 'selected' : ''); ?>>
                            <?php echo e($st->label()); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <div class="flex items-center gap-1">
                    <input type="date" name="from_date" value="<?php echo e($filters['from_date'] ?? ''); ?>" placeholder="من تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <span class="text-slate-400">إلى</span>
                    <input type="date" name="to_date" value="<?php echo e($filters['to_date'] ?? ''); ?>" placeholder="إلى تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <input type="text" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="بحث برقم المعاملة..."
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs w-36 focus:ring-2 focus:ring-teal-500 focus:outline-none">

                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition cursor-pointer">
                    تصفية
                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($filters['transaction_type']) || !empty($filters['status']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['q'])): ?>
                    <a href="<?php echo e(route('marketer.wallet.index', ['currency' => $selectedCurrency])); ?>" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        إلغاء التصفية
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </form>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($transactions->isEmpty()): ?>
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد حركات مالية مسجلة بهذه العملة ومعايير التصفية المحددة.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم القيد المرجعي</th>
                            <th class="p-4">نوع الحركة</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">طبيعة القيد</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">الرصيد المتاح بعد</th>
                            <th class="p-4">المحجوز بعد</th>
                            <th class="p-4">البيان والتفاصيل</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    <?php echo e($tx->ledgerTransaction?->transaction_number ?? substr($tx->ledger_transaction_id, 0, 8)); ?>

                                </td>
                                <td class="p-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tx->ledgerTransaction): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo e($tx->ledgerTransaction->transaction_type->badgeClass()); ?>">
                                            <?php echo e($tx->ledgerTransaction->transaction_type->label()); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="p-4 font-black <?php echo e($tx->entry_type->value === 'CREDIT' ? 'text-emerald-700' : 'text-slate-900'); ?>">
                                    <?php echo e($tx->entry_type->value === 'CREDIT' ? '+' : '-'); ?><?php echo e(\App\Services\Math\MoneyMath::display($tx->amount, $tx->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($tx->currency->value); ?></span>
                                </td>
                                <td class="p-4 font-bold">
                                    <span class="text-[11px] <?php echo e($tx->entry_type->value === 'DEBIT' ? 'text-amber-700' : 'text-blue-700'); ?>">
                                        <?php echo e($tx->entry_type->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tx->ledgerTransaction): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo e($tx->ledgerTransaction->status->badgeClass()); ?>">
                                            <?php echo e($tx->ledgerTransaction->status->label()); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="p-4 font-mono font-bold text-slate-800">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($tx->balance_after, $tx->currency->value)); ?>

                                </td>
                                <td class="p-4 font-mono text-amber-700">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($tx->reserved_after, $tx->currency->value)); ?>

                                </td>
                                <td class="p-4 text-slate-600 max-w-xs truncate" title="<?php echo e($tx->description); ?>">
                                    <?php echo e($tx->description); ?>

                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    <?php echo e($tx->created_at->format('Y-m-d H:i')); ?>

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

    <!-- Payout Requests History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل طلبات سحب الأرباح (Payout Requests)</h3>
                <p class="text-xs text-slate-500 mt-0.5">متابعة دقيقة لحالات طلبات السحب المصرفي وتواريخ الموافقة والتحويل</p>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($payoutRequests->isEmpty()): ?>
            <div class="p-12 text-center text-slate-400 text-xs">
                لم تقم بتقديم أي طلبات سحب أرباح حتى الآن. عند تقديم أول طلب سحب سيظهر سجله هنا مباشرة.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الطلب</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">البنك / الحساب المظلل</th>
                            <th class="p-4">حالة الطلب</th>
                            <th class="p-4">تاريخ الموافقة</th>
                            <th class="p-4">حالة ومرجع الدفع الفعلي</th>
                            <th class="p-4">تاريخ التقديم</th>
                            <th class="p-4">ملاحظات وسبب الرفض</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $payoutRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    <?php echo e($req->request_number); ?>

                                </td>
                                <td class="p-4 font-black text-slate-900">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($req->amount, $req->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($req->currency->value); ?></span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?php echo e($req->bank_name ?? '—'); ?></div>
                                    <div class="font-mono text-[11px] text-slate-500 mt-0.5 dir-ltr text-right"><?php echo e($req->account_reference ?? '—'); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($req->status->badgeClass()); ?>">
                                        <?php echo e($req->status->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-600">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($req->reviewed_at): ?>
                                        <span class="text-emerald-700 font-bold"><?php echo e($req->reviewed_at->format('Y-m-d H:i')); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">قيد المراجعة</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="p-4 font-mono text-slate-600">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($req->isPaid() && $req->payment_reference): ?>
                                        <div class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-block">
                                            <?php echo e($req->payment_reference); ?>

                                        </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($req->paid_at): ?>
                                            <div class="text-[10px] text-emerald-600 mt-0.5"><?php echo e($req->paid_at->format('Y-m-d H:i')); ?></div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php elseif($req->isApproved()): ?>
                                        <span class="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            معتمد • بانتظار إشعار البنك
                                        </span>
                                    <?php elseif($req->isRejected()): ?>
                                        <span class="text-rose-600 font-bold">ملغي / مرفوض</span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">بانتظار الموافقة والتحويل</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    <?php echo e($req->created_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4 text-slate-500 max-w-xs truncate">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($req->rejection_reason): ?>
                                        <span class="text-rose-600 font-bold" title="<?php echo e($req->rejection_reason); ?>">
                                            سبب الرفض: <?php echo e($req->rejection_reason); ?>

                                        </span>
                                    <?php elseif($req->admin_notes): ?>
                                        <span><?php echo e($req->admin_notes); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    <!-- Commission Settlements History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">سجل استحقاقات العمولات (Settled Commissions)</h3>
                <p class="text-xs text-slate-500 mt-0.5">العمولات المرحلة في دفتر الأستاذ والمضافة لمحفظتك من تحويلات المبيعات المؤكدة</p>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($settlements->isEmpty()): ?>
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد تسويات عمولات مسجلة بعد. عند اعتماد أي تحويل من قبل العلامات التجارية تضاف أرباحك هنا فوراً.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم التحويل الخارجي</th>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">قيمة العمولة المستحقة</th>
                            <th class="p-4">تاريخ التسوية</th>
                            <th class="p-4">الحالة المحاسبية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $settlements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $settlement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    #<?php echo e($settlement->conversion?->external_order_id ?? '—'); ?>

                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    <?php echo e($settlement->conversion?->campaign?->title ?? '—'); ?>

                                </td>
                                <td class="p-4 font-black text-emerald-600">
                                    +<?php echo e(\App\Services\Math\MoneyMath::display($settlement->commission_amount, $settlement->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($settlement->currency->value); ?></span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500">
                                    <?php echo e($settlement->settled_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($settlement->isReversed()): ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            معكوس (طلب ملغي)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            مرحل في المحفظة ✓
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                <?php echo e($settlements->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/marketer/wallet/index.blade.php ENDPATH**/ ?>