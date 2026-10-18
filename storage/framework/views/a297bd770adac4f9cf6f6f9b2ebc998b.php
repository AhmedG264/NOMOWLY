<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ depositModalOpen: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>🏬</span>
                <span>المحفظة المالية للعلامة التجارية • Brand Wallet & Escrow</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">محفظة المتجر وإدارة ميزانيات الحملات</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                الجهة: <span class="text-white font-bold"><?php echo e(auth()->user()->brandProfile?->company_name ?? auth()->user()->name); ?></span>
                • العملة المعتمدة: <span class="text-emerald-400 font-bold"><?php echo e($selectedCurrency); ?></span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="depositModalOpen = true"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2 cursor-pointer">
                <span>➕</span>
                <span>شحن المحفظة</span>
            </button>
            <a href="<?php echo e(route('brand.campaigns.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                الحملات
            </a>
            <a href="<?php echo e(route('brand.dashboard')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Multi-Currency Balances Cards -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">أرصدة المحفظة بالعملات المعتمدة</h2>
                <p class="text-xs text-slate-500">معزولة ومستقلة محاسبياً لكل عملة على حدة، ولا يتم خلط أسعار الصرف</p>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reconciliation): ?>
                <div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reconciliation['is_matched']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span>✓</span>
                            <span>مطابقة كشف الحساب: 100% متطابق</span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <span>⚠️</span>
                            <span>تفاوت بين الرصيد ودفتر الأستاذ قيد المراجعة</span>
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
                    $total = $wallet ? \App\Services\Math\MoneyMath::format($wallet->total_balance) : '0.0000';
                    $isSelected = $selectedCurrency === $curr->value;
                ?>
                <a href="<?php echo e(route('brand.wallet.index', ['currency' => $curr->value])); ?>"
                   class="bg-white rounded-3xl border <?php echo e($isSelected ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-slate-300'); ?> p-5 shadow-xs transition flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-black <?php echo e($isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800'); ?>">
                                <span><?php echo e($curr->symbol()); ?></span>
                                <span><?php echo e($curr->value); ?></span>
                            </span>
                            <span class="text-[11px] font-bold text-slate-400"><?php echo e($curr->label()); ?></span>
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs text-slate-500 font-medium">الرصيد المتاح (لتمويل الحملات)</div>
                            <div class="text-2xl font-black text-slate-900 tracking-tight">
                                <?php echo e(\App\Services\Math\MoneyMath::display($avail, $curr->value)); ?>

                                <span class="text-xs font-bold text-slate-400"><?php echo e($curr->value); ?></span>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-amber-700">
                                <span>المحجوز كضمان للحملات (Escrow):</span>
                                <span class="font-bold"><?php echo e(\App\Services\Math\MoneyMath::display($reserved, $curr->value)); ?></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[11px]">
                                <span>إجمالي الرصيد الفعلي:</span>
                                <span class="font-bold text-slate-700"><?php echo e(\App\Services\Math\MoneyMath::display($total, $curr->value)); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-[11px] font-bold <?php echo e($isSelected ? 'text-emerald-700' : 'text-slate-400'); ?>">
                        <?php echo e($isSelected ? '• العملة المحددة حالياً للكشف' : 'انقر لعرض الكشف بهذه العملة ←'); ?>

                    </div>
                </a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>

    <!-- Escrow Budget & Safety Notice -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 p-6 rounded-3xl flex items-start gap-4">
            <span class="text-3xl shrink-0">🔒</span>
            <div class="space-y-1.5 text-xs text-emerald-950">
                <h3 class="font-black text-sm text-emerald-900">آلية الضمان المالي لحملات الأداء (Escrow Safety Mechanism):</h3>
                <p class="leading-relaxed text-slate-600">
                    عند إطلاق أو تمويل أي حملة تسويقية، يتم نقل المبلغ المخصص من رصيدك المتاح إلى الرصيد المحجوز بالضمان (<span class="font-bold font-mono">Reserved Balance</span>).
                    تظل هذه الأموال ملكاً لمتجرك ومحجوزة كضمان لمكافأة المسوقين عند تحقيق مبيعات مؤكدة، ولا يتم خصمها نهائياً إلا فور اعتمادك المباشر لطلب المبيعات. يمكنك تحرير الفائض عند إيقاف الحملة.
                </p>
                <div class="pt-1 flex items-center gap-4 font-bold text-[11px] text-emerald-800">
                    <span>• الرصيد المتاح حالياً بالـ <?php echo e($selectedCurrency); ?>: <?php echo e(\App\Services\Math\MoneyMath::display($activeWallet->available_balance, $selectedCurrency)); ?></span>
                    <span>• الضمان المحجوز: <?php echo e(\App\Services\Math\MoneyMath::display($activeWallet->reserved_balance, $selectedCurrency)); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black">
                    📢
                </div>
                <h3 class="text-sm font-black text-slate-900">الحملات النشطة ذات الميزانيات</h3>
                <p class="text-xs text-slate-500">
                    عدد حملات متجرك المسجلة: <span class="font-bold text-slate-800"><?php echo e($campaigns->count()); ?></span> حملة
                </p>
            </div>
            <div class="pt-4">
                <a href="<?php echo e(route('brand.campaigns.index')); ?>" class="block w-full text-center py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                    إدارة الحملات والميزانيات ←
                </a>
            </div>
        </div>
    </div>

    <!-- Deposit Information / Payment Modal -->
    <div x-show="depositModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="depositModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-right space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black">💳</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900">شحن رصيد المحفظة المالية</h3>
                        <p class="text-xs text-slate-500">العملة المحددة: <span class="font-bold text-emerald-600"><?php echo e($selectedCurrency); ?></span></p>
                    </div>
                </div>
                <button type="button" @click="depositModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isPaymentGatewayAvailable): ?>
                <!-- Electronic Gateway Form -->
                <form method="POST" action="<?php echo e(route('brand.wallet.deposit')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="currency" value="<?php echo e($selectedCurrency); ?>">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">المبلغ المراد شحنه (<?php echo e($selectedCurrency); ?>) *</label>
                        <input type="number" step="any" min="1" name="amount" required placeholder="مثال: 500"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">بوابة الدفع الإلكتروني *</label>
                        <select name="provider" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $availableGateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $gw): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($key); ?>"><?php echo e($gw->getProvider()->label()); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">طريقة الدفع المفضلة</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="mada" checked class="sr-only">
                                <span class="text-sm font-black text-slate-800">مدى / بطاقة</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Mada / Card</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="applepay" class="sr-only">
                                <span class="text-sm font-black text-slate-800">أبل باي</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Apple Pay</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer bg-slate-50/50 text-center">
                                <input type="radio" name="payment_method" value="creditcard" class="sr-only">
                                <span class="text-sm font-black text-slate-800">فيزا / ماستر</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Visa/Master</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-[11px] text-emerald-800 space-y-1">
                        <p class="font-bold">🔒 حماية وتشفير مصرفي معتمد:</p>
                        <p>تتم معالجة عمليات الدفع عبر خوادم البوابة المشفرة. لا يتم إضافة الرصيد إلى محفظتك إلا بعد وصول تأكيد رسمي مشفر من مزود الدفع.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="depositModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                            إلغاء
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                            متابعة للدفع الآمن ←
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- No Live Gateway Configured Notice -->
                <div class="space-y-4 text-xs text-slate-700 leading-relaxed">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-amber-900 space-y-2">
                        <div class="flex items-center gap-2 font-black text-sm">
                            <span>ℹ️</span>
                            <span>بوابات الدفع الإلكتروني قيد الربط المؤسسي:</span>
                        </div>
                        <p>
                            شحن المحفظة اللحظي عبر البطاقات الائتمانية وبوابات الدفع الإقليمية (ميسر، تاب، مدى) قيد الإعداد المؤسسي وستتاح للمدفوعات المباشرة فور تفعيل الاعتمادات البنكية.
                        </p>
                    </div>

                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-2">
                        <h4 class="font-bold text-slate-900">الإيداع المصرفي المباشر عبر الحساب البنكي:</h4>
                        <p class="text-slate-600">
                            لشحن رصيد محفظتك حالياً وتفعيل ميزانيات الحملات، يُرجى إجراء تحويل بنكي إلى حساب منصة نمولي الرسمي وإرسال إشعار التحويل لفريق الحسابات المالية لإيداع الرصيد في محفظتك فوراً بموجب قيد مزدوج معتمد.
                        </p>
                        <div class="pt-2 border-t border-slate-200 font-mono text-[11px] text-slate-800 space-y-1">
                            <div>البنك: <span class="font-bold">مصرف الراجحي / البنك الأهلي السعودي</span></div>
                            <div>البريد المالي: <span class="font-bold text-emerald-700">finance@nomowly.com</span></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="depositModalOpen = false"
                                class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                            فهمت ذلك، إغلاق
                        </button>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- Payment Deposits History (سجل عمليات الشحن والإيداع) -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($deposits) && $deposits->isNotEmpty()): ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900">سجل عمليات شحن المحفظة (Payment Deposits)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">تتبع حالات عمليات الدفع الإلكتروني والإيداعات بالعملة المحددة (<?php echo e($selectedCurrency); ?>)</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم العملية</th>
                            <th class="p-4">مزود الدفع</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">الصافي المودع</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">تاريخ الإنشاء</th>
                            <th class="p-4">تاريخ التأكيد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    <?php echo e($dep->deposit_number); ?>

                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    <?php echo e($dep->provider->label()); ?>

                                </td>
                                <td class="p-4 font-bold text-slate-900">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($dep->amount, $dep->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($dep->currency->value); ?></span>
                                </td>
                                <td class="p-4 font-black text-emerald-700">
                                    <?php echo e(\App\Services\Math\MoneyMath::display($dep->net_amount, $dep->currency->value)); ?>

                                    <span class="text-[10px] font-normal text-slate-400"><?php echo e($dep->currency->value); ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($dep->status->badgeClass()); ?>">
                                        <?php echo e($dep->status->label()); ?>

                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500">
                                    <?php echo e($dep->created_at->format('Y-m-d H:i')); ?>

                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-600">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dep->paid_at): ?>
                                        <span class="text-emerald-700 font-bold"><?php echo e($dep->paid_at->format('Y-m-d H:i')); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Financial Statement (كشف الحساب التفصيلي) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-900">كشف حساب الحركات والعمليات المالية</h3>
                <p class="text-xs text-slate-500 mt-0.5">سجل الحركات المرحلة في دفتر القيود المحاسبي المزدوج</p>
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="<?php echo e(route('brand.wallet.index')); ?>" class="flex flex-wrap items-center gap-2 text-xs w-full lg:w-auto">
                <input type="hidden" name="currency" value="<?php echo e($selectedCurrency); ?>">

                <select name="transaction_type" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">جميع أنواع الحركات</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\LedgerTransactionType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($type->value); ?>" <?php echo e(($filters['transaction_type'] ?? '') === $type->value ? 'selected' : ''); ?>>
                            <?php echo e($type->label()); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\LedgerTransactionStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($st->value); ?>" <?php echo e(($filters['status'] ?? '') === $st->value ? 'selected' : ''); ?>>
                            <?php echo e($st->label()); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <div class="flex items-center gap-1">
                    <input type="date" name="from_date" value="<?php echo e($filters['from_date'] ?? ''); ?>" placeholder="من تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-slate-400">إلى</span>
                    <input type="date" name="to_date" value="<?php echo e($filters['to_date'] ?? ''); ?>" placeholder="إلى تاريخ"
                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <input type="text" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="بحث برقم المعاملة..."
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs w-36 focus:ring-2 focus:ring-emerald-500 focus:outline-none">

                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition cursor-pointer">
                    تصفية
                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($filters['transaction_type']) || !empty($filters['status']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['q'])): ?>
                    <a href="<?php echo e(route('brand.wallet.index', ['currency' => $selectedCurrency])); ?>" class="text-xs text-slate-500 hover:text-slate-800 underline">
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
                            <th class="p-4">الحملة المرتبطة</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">طبيعة القيد</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">الرصيد المتاح بعد</th>
                            <th class="p-4">الضمان المحجوز بعد</th>
                            <th class="p-4">البيان والتفاصيل</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $associatedCampaign = null;
                                if ($tx->ledgerTransaction) {
                                    if ($tx->ledgerTransaction->source_type === \App\Models\Campaign::class) {
                                        $associatedCampaign = $campaignsMap->get($tx->ledgerTransaction->source_id);
                                    } elseif ($tx->ledgerTransaction->source_type === \App\Models\Conversion::class) {
                                        $conv = $conversionsMap->get($tx->ledgerTransaction->source_id);
                                        $associatedCampaign = $conv?->campaign;
                                    }
                                }
                            ?>
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
                                <td class="p-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($associatedCampaign): ?>
                                        <a href="<?php echo e(route('brand.campaigns.edit', $associatedCampaign->id)); ?>" class="font-bold text-emerald-700 hover:text-emerald-900 underline truncate block max-w-[130px]" title="<?php echo e($associatedCampaign->title); ?>">
                                            <?php echo e($associatedCampaign->title); ?>

                                        </a>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/brand/wallet/index.blade.php ENDPATH**/ ?>