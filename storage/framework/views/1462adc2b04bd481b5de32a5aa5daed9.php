<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="<?php echo e(route('brand.campaigns.index')); ?>" class="text-xs font-bold text-slate-500 hover:text-emerald-700 transition">
                ← العودة إلى قائمة الحملات
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">إنشاء حملة تسويقية جديدة</h1>
            <p class="text-xs text-slate-500 mt-1">
                حدد نموذج العمولة، والشروط، والأسواق المستهدفة، ومتطلبات المحتوى لجذب صناع المحتوى المؤهلين.
            </p>
        </div>
    </div>

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

    <form method="POST" action="<?php echo e(route('brand.campaigns.store')); ?>" class="space-y-8">
        <?php echo csrf_field(); ?>

        <!-- 1. Basic Info -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">1. البيانات الأساسية للحملة</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان الحملة التسويقية *</label>
                    <input type="text" name="title" value="<?php echo e(old('title')); ?>" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: كوليكشن العبايات والأزياء الشرقية الفاخرة">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رابط المتجر أو صفحة الهبوط المباشرة (Destination URL)</label>
                    <input type="url" name="destination_url" value="<?php echo e(old('destination_url')); ?>" placeholder="https://yourstore.com/summer-collection" class="w-full text-sm font-mono rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-slate-400 mt-1">الرابط الذي سيتم توجيه العملاء إليه بعد تسجيل النقرة التتبعية وحفظ كوكيز الإسناد.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">تصنيف الحملة (Category) *</label>
                        <select name="category" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="أزياء وموضة" <?php echo e(old('category') === 'أزياء وموضة' ? 'selected' : ''); ?>>أزياء وموضة</option>
                            <option value="عطور وتجميل" <?php echo e(old('category') === 'عطور وتجميل' ? 'selected' : ''); ?>>عطور وتجميل</option>
                            <option value="إلكترونيات" <?php echo e(old('category') === 'إلكترونيات' ? 'selected' : ''); ?>>إلكترونيات</option>
                            <option value="صحة ورشاقة" <?php echo e(old('category') === 'صحة ورشاقة' ? 'selected' : ''); ?>>صحة ورشاقة</option>
                            <option value="مستلزمات منزل" <?php echo e(old('category') === 'مستلزمات منزل' ? 'selected' : ''); ?>>مستلزمات منزل</option>
                            <option value="أطفال وعائلة" <?php echo e(old('category') === 'أطفال وعائلة' ? 'selected' : ''); ?>>أطفال وعائلة</option>
                            <option value="خدمات وتطبيقات" <?php echo e(old('category') === 'خدمات وتطبيقات' ? 'selected' : ''); ?>>خدمات وتطبيقات</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شارة تسويقية (Badge / Optional)</label>
                        <input type="text" name="badge" value="<?php echo e(old('badge')); ?>" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: الأكثر طلباً أو عمولة حصرية">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الوصف المختصر (Summary) *</label>
                    <textarea name="description" rows="2" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="وصف موجز يظهر في بطاقة الحملة في سوق الحملات..."><?php echo e(old('description')); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ملخص الحملة الكامل والجمهور المستهدف (Campaign Brief) *</label>
                    <textarea name="brief" rows="4" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="اشرح قصة العلامة التجارية، ميزات المنتجات، الشريحة المستهدفة، ونقاط القوة التسويقية..."><?php echo e(old('brief')); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 2. Performance Terms & Compensation -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">2. هيكل العمولات وشروط الأداء</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">نوع نموذج التعويض (Compensation Model) *</label>
                        <select name="commission_type" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\CampaignCommissionType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($type->value); ?>" <?php echo e(old('commission_type') === $type->value ? 'selected' : ''); ?>>
                                    <?php echo e($type->label()); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">قيمة العمولة أو المقابل (Commission Value) *</label>
                        <input type="text" name="commission_value" value="<?php echo e(old('commission_value')); ?>" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: 15% من المبيعة أو 60 ر.س لكل طلب">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">مدة الكوكيز بالأيام *</label>
                        <input type="number" name="cookie_duration_days" value="<?php echo e(old('cookie_duration_days', 30)); ?>" min="1" max="365" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">سلة الشراء التقديرية (تقدير المتجر - اختياري)</label>
                        <input type="text" name="avg_basket" value="<?php echo e(old('avg_basket')); ?>" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: 450 ر.س (تقدير إرشادي)">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">معدل التحويل التقديري (تقدير المتجر - اختياري)</label>
                        <input type="text" name="conversion_rate" value="<?php echo e(old('conversion_rate')); ?>" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: 4.5% (تقدير إرشادي)">
                    </div>
                </div>

                <!-- Tracking Methods -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">طرق التتبع المعتمدة للحملة *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="كود خصم حصري" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">كود خصم حصري</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="رابط تتبع ذكي" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">رابط تتبع ذكي</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="إحالة مباشرة" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">إحالة مباشرة</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Target Markets & Currencies -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">3. النطاق الجغرافي والعملات</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">الأسواق المستهدفة *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['السعودية', 'مصر', 'الإمارات', 'الكويت', 'قطر', 'عمان', 'البحرين']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="target_markets[]" value="<?php echo e($country); ?>" <?php echo e(in_array($country, old('target_markets', ['السعودية', 'مصر'])) ? 'checked' : ''); ?> class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-slate-800"><?php echo e($country); ?></span>
                            </label>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">العملات المعتمدة *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['SAR', 'EGP', 'AED', 'KWD', 'QAR', 'OMR', 'BHD']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="currencies[]" value="<?php echo e($curr); ?>" <?php echo e(in_array($curr, old('currencies', ['SAR', 'EGP'])) ? 'checked' : ''); ?> class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-slate-800"><?php echo e($curr); ?></span>
                            </label>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Guidelines & Deliverables -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">4. متطلبات المحتوى وشروط الامتثال</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">متطلبات المحتوى والتسليمات (اختياري)</label>
                    <textarea name="requirements" rows="3" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: فيديو ريلز إنستغرام أو تيك توك، توضيح تجربة فتح الصندوق (Unboxing)، ذكر كود الخصم في أول 3 ثوانٍ..."><?php echo e(old('requirements')); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">إرشادات الامتثال والمحظورات (اختياري)</label>
                    <textarea name="brand_guidelines" rows="3" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: يمنع المزايدة على اسم العلامة التجارية في Google Ads، يمنع تقديم ادعاءات طبية غير معتمدة..."><?php echo e(old('brand_guidelines')); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="<?php echo e(route('brand.campaigns.index')); ?>" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                نشر الحملة في السوق الآن ←
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/brand/campaigns/create.blade.php ENDPATH**/ ?>