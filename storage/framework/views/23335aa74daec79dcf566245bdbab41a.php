<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>🛡️</span>
                <span>سجل العمليات والمراقبة الإدارية والأمنية</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل العمليات والمراقبة (Audit Log)</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                سجل تاريخي تدقيقي غير قابل للتعديل يوثق جميع الأنشطة الحساسة، والتغييرات الإدارية، ومحاولات الوصول، والعمليات المالية والترويجية.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                إدارة الحملات
            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="px-4 py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 rounded-xl text-xs sm:text-sm font-bold border border-rose-500/30 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-black bg-indigo-600 text-white shadow-xs">
            سجل العمليات والمراقبة (<?php echo e($stats['total_logs']); ?>)
        </a>
        <a href="<?php echo e(route('admin.roles.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="<?php echo e(route('admin.users.index')); ?>" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة المستخدمين
        </a>
    </div>

    <!-- System KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي السجلات التدقيقية</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e(number_format($stats['total_logs'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">عمليات آخر 24 ساعة</div>
            <div class="text-2xl font-black text-indigo-600 mt-1"><?php echo e(number_format($stats['last_24h'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">عمليات مرفوضة / فاشلة</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e(number_format($stats['failed_or_rejected'])); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">تنبيهات أمنية ومحاولات غير مصرح بها</div>
            <div class="text-2xl font-black text-rose-600 mt-1"><?php echo e(number_format($stats['security_alerts'])); ?></div>
        </div>
    </div>

    <!-- Search & Multi-Dimensional Filters -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🔍</span>
                <span>تصفية وفحص السجل المتقدم</span>
            </h2>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->anyFilled(['q', 'action', 'status', 'entity_type', 'entity_id', 'ip_address', 'date_from', 'date_to'])): ?>
                <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="text-xs font-bold text-rose-600 hover:text-rose-700">
                    ✕ إلغاء جميع الفلاتر
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <form method="GET" action="<?php echo e(route('admin.audit-logs.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search text -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">بحث عام</label>
                    <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="البريد، المعرف، IP، السبب..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Action Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع العملية</label>
                    <select name="action" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع العمليات (<?php echo e(count($actions)); ?>)</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($action->value); ?>" <?php if(request('action') === $action->value): echo 'selected'; endif; ?>>
                                <?php echo e($action->label()); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>

                <!-- Status / Result Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نتيجة العملية</label>
                    <select name="status" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع الحالات</option>
                        <option value="SUCCESS" <?php if(request('status') === 'SUCCESS'): echo 'selected'; endif; ?>>ناجحة (SUCCESS)</option>
                        <option value="FAILED" <?php if(request('status') === 'FAILED'): echo 'selected'; endif; ?>>فاشلة (FAILED)</option>
                        <option value="APPROVED" <?php if(request('status') === 'APPROVED'): echo 'selected'; endif; ?>>معتمدة (APPROVED)</option>
                        <option value="REJECTED" <?php if(request('status') === 'REJECTED'): echo 'selected'; endif; ?>>مرفوضة (REJECTED)</option>
                        <option value="CANCELLED" <?php if(request('status') === 'CANCELLED'): echo 'selected'; endif; ?>>ملغاة (CANCELLED)</option>
                        <option value="UNAUTHORIZED" <?php if(request('status') === 'UNAUTHORIZED'): echo 'selected'; endif; ?>>محظورة أمنياً (UNAUTHORIZED)</option>
                    </select>
                </div>

                <!-- Entity Type Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع الكيان</label>
                    <select name="entity_type" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع الكيانات</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $availableEntityTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($type); ?>" <?php if(request('entity_type') === $type): echo 'selected'; endif; ?>>
                                <?php echo e($type); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>

                <!-- Entity ID -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">معرف الكيان (Entity ID)</label>
                    <input type="text" name="entity_id" value="<?php echo e(request('entity_id')); ?>" placeholder="معرف الحملة أو التحويل..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono">
                </div>

                <!-- IP Address -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان IP</label>
                    <input type="text" name="ip_address" value="<?php echo e(request('ip_address')); ?>" placeholder="e.g. 192.168.1.1" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono">
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition cursor-pointer shadow-xs">
                    تطبيق الفلاتر والبحث
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">سجل الأحداث والعمليات (مرتب من الأحدث للأقدم)</h2>
            <div class="text-xs font-bold text-slate-500">
                عرض <?php echo e($logs->firstItem() ?? 0); ?> إلى <?php echo e($logs->lastItem() ?? 0); ?> من أصل <?php echo e(number_format($logs->total())); ?> سجل
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                        <th class="py-3 px-4">التاريخ والوقت</th>
                        <th class="py-3 px-4">نوع العملية</th>
                        <th class="py-3 px-4">الحالة / النتيجة</th>
                        <th class="py-3 px-4">المستخدم المنفذ</th>
                        <th class="py-3 px-4">الكيان المستهدف</th>
                        <th class="py-3 px-4">عنوان IP</th>
                        <th class="py-3 px-4">السبب / الملاحظات</th>
                        <th class="py-3 px-4 text-center">التفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Timestamp -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800"><?php echo e($log->created_at->format('Y-m-d H:i:s')); ?></div>
                                <div class="text-[10px] text-slate-400 font-medium"><?php echo e($log->created_at->diffForHumans()); ?></div>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-black border <?php echo e($log->action->badgeClass()); ?>">
                                    <?php echo e($log->actionLabel()); ?>

                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black border <?php echo e($log->statusBadgeClass()); ?>">
                                    <?php echo e($log->statusLabel()); ?>

                                </span>
                            </td>

                            <!-- User -->
                            <td class="py-3.5 px-4">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->user): ?>
                                    <div class="font-bold text-slate-900 truncate max-w-[150px]"><?php echo e($log->user->email); ?></div>
                                    <div class="text-[10px] text-slate-500 font-medium"><?php echo e($log->user->role->label()); ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400 font-medium italic">نظام / زائر غير مسجل</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            <!-- Target Entity -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->entity_type): ?>
                                    <div class="font-bold text-slate-800"><?php echo e($log->entity_type); ?></div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[120px]"><?php echo e($log->entity_id ?: '-'); ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            <!-- IP Address -->
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                <?php echo e($log->ip_address ?: '-'); ?>

                            </td>

                            <!-- Reason / Notes -->
                            <td class="py-3.5 px-4 max-w-[200px]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->reason): ?>
                                    <span class="text-slate-700 truncate block font-medium" title="<?php echo e($log->reason); ?>"><?php echo e($log->reason); ?></span>
                                <?php elseif($log->payload_diff && isset($log->payload_diff['rejection_reason'])): ?>
                                    <span class="text-rose-600 truncate block font-medium"><?php echo e($log->payload_diff['rejection_reason']); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            <!-- Details Link -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="<?php echo e(route('admin.audit-logs.show', $log)); ?>" class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-[11px] font-bold transition shadow-2xs">
                                    عرض التفاصيل
                                </a>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">📜</div>
                                <div class="font-bold text-slate-800">لا توجد سجلات تطابق الفلاتر المحددة</div>
                                <div class="text-xs text-slate-400 mt-1">جرّب تغيير خيارات البحث أو إعادة ضبط الفلاتر.</div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logs->hasPages()): ?>
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <?php echo e($logs->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Security & Immutability Notice -->
    <div class="rounded-2xl bg-slate-100 border border-slate-200/80 p-4 text-xs text-slate-600 flex items-start gap-3">
        <span class="text-lg">🔒</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-800">ضمانات أمان وسرية سجل العمليات (Audit Trail Policy):</div>
            <p class="leading-relaxed">
                جميع السجلات المعروضة محمية برمجياً بنمط الإضافة فقط (Append-Only). يُحظر تعديل أي سجل أو حذفه من خلال واجهات أو وظائف التطبيق. يتم تشفير وتجريد أي كلمات مرور أو مفاتيح سرية فور تسجيل الحدث لضمان الامتثال التام لمعايير أمن البيانات.
            </p>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/audit-logs/index.blade.php ENDPATH**/ ?>