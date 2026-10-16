<?php $__env->startSection('content'); ?>
<div class="min-h-[calc(100vh-12rem)] flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 items-center justify-center text-amber-700 font-black text-2xl shadow-sm mb-3">
                🛡️
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                تغيير كلمة المرور الإلزامي
            </h1>
            <p class="mt-2 text-sm text-slate-600 font-medium">
                تم تعيين كلمة مرور أولية لحسابك الإداري. يجب تعيين كلمة مرور قوية وخاصة بك لمتابعة الاستخدام.
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80">
            <!-- Warning Alert -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('warning')): ?>
                <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 font-medium flex items-center gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">!</span>
                    <span><?php echo e(session('warning')); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Errors Alert -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-medium">
                    <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-900">
                        <span>⚠️</span>
                        <span>تعذر تحديث كلمة المرور:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pr-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <li><?php echo e($error); ?></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form method="POST" action="<?php echo e(route('password.change.update')); ?>" class="space-y-5">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        كلمة المرور الحالية (الأولية)
                    </label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        كلمة المرور الجديدة
                    </label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        placeholder="10 أحرف على الأقل (أحرف كبيرة وصغيرة وأرقام ورموز)"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        تأكيد كلمة المرور الجديدة
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        placeholder="أعد إدخال كلمة المرور الجديدة"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/40 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition duration-150">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-sm font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 cursor-pointer">
                        <span>تأكيد كلمة المرور والدخول إلى لوحة التحكم</span>
                        <span>←</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/auth/password-change.blade.php ENDPATH**/ ?>