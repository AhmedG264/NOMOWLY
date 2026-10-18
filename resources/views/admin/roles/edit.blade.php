@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
            <span aria-hidden="true">→</span>
            <span>العودة إلى قائمة الأدوار والصلاحيات</span>
        </a>
        <span class="text-xs font-mono text-slate-400">معرف الدور: {{ $role->id }}</span>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold border {{ $role->is_system ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                        {{ $role->is_system ? 'أساسي محمي للنظام' : 'دور إداري مخصص' }}
                    </span>
                    @if ($role->isSuperAdmin())
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-purple-50 text-purple-700 border border-purple-200">
                            👑 المدير العام
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $role->isSuperAdmin() ? 'استعراض صلاحيات المدير العام' : "تعديل الدور: {$role->label}" }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $role->isSuperAdmin() ? 'دور المدير العام محمي برمجياً بنمط الحماية العليا ولا يمكن تقييده أو حذف أي من صلاحياته.' : 'قم بتعديل المسمى والوصف والصلاحيات الممنوحة لهذا الدور.' }}
                </p>
            </div>
            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 text-center min-w-[130px]">
                <div class="text-[11px] font-bold text-slate-500">المستخدمون المسندون</div>
                <div class="text-base font-black text-slate-900 mt-0.5">{{ $role->users()->count() }} مستخدم</div>
            </div>
        </div>

        @if ($role->isSuperAdmin())
            <!-- Super Admin Immutable Notice -->
            <div class="rounded-2xl bg-purple-50 border border-purple-200 p-5 space-y-3">
                <div class="flex items-center gap-2 text-purple-900 font-bold text-sm">
                    <span class="text-lg">🔒</span>
                    <span>ضمانات الحماية العليا لدور المدير العام (Super Admin Policy):</span>
                </div>
                <p class="text-xs text-purple-800 leading-relaxed font-medium">
                    يمتلك المدير العام بطبيعة تكوينه البرمجي كافة الصلاحيات الحالية والمستقبلية في النظام (Universal Authorization). لا يحتاج هذا الدور إلى تحديد صلاحيات يدوية، ولا يمكن تقليص أو تعديل أو حذف صلاحياته أو إعادة تسميته من واجهة لوحة الإدارة حمايةً لاستقرار النظام.
                </p>
            </div>

            <!-- Read-only Permissions Overview -->
            <div class="space-y-4 pt-2">
                <h2 class="text-sm font-black text-slate-900">الصلاحيات الشاملة للمدير العام:</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    @foreach ($role->permissions as $perm)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="font-bold text-slate-800">{{ $perm->label }}</div>
                            <div class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $perm->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <!-- Non-Super Admin Edit Form -->
            @if ($errors->any())
                <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800 space-y-1">
                    <div class="font-bold">يرجى تصحيح الأخطاء التالية:</div>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            الاسم الرمزي البرمجي (Role Code)
                        </label>
                        <input type="text" name="name" value="{{ old('name', $role->name) }}" class="w-full text-xs font-mono rounded-xl border border-slate-300 p-3 outline-none uppercase {{ $role->is_system ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500' }}" {{ $role->is_system ? 'readonly' : 'required' }}>
                        @if ($role->is_system)
                            <div class="text-[11px] text-indigo-600 mt-1">دور أساسي محمي: لا يمكن تغيير الاسم الرمزي البرمجي.</div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            المسمى المعروض باللغة العربية (Display Label) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="label" value="{{ old('label', $role->label) }}" class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-bold" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">وصف الدور</label>
                    <textarea name="description" rows="2" class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">{{ old('description', $role->description) }}</textarea>
                </div>

                <!-- Permissions Selection by Category -->
                @if (!in_array($role->name, [\App\Enums\UserRole::BRAND->value, \App\Enums\UserRole::MARKETER->value], true))
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div>
                            <h2 class="text-sm font-black text-slate-900">تعديل الصلاحيات الحبيبية (Permissions)</h2>
                            <p class="text-xs text-slate-500 mt-0.5">حدد الصلاحيات الممنوحة لهذا الدور.</p>
                        </div>

                        @php
                            $assignedPermissionIds = old('permissions', $role->permissions->pluck('id')->toArray());
                        @endphp

                        <div class="space-y-4">
                            @foreach ($permissionsByCategory as $category => $permissions)
                                <div class="rounded-2xl border border-slate-200/80 p-4 bg-slate-50/50 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                                        <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                                            <span>📁</span>
                                            <span>{{ $category }}</span>
                                            <span class="text-[10px] text-slate-400 font-normal">({{ count($permissions) }} صلاحيات)</span>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                        @foreach ($permissions as $permission)
                                            <label class="flex items-start gap-2 p-2.5 rounded-xl bg-white border border-slate-200/60 hover:border-indigo-300 transition cursor-pointer select-none">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assignedPermissionIds)) class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                                                <div class="text-[11px] leading-tight">
                                                    <div class="font-bold text-slate-800">{{ $permission->label }}</div>
                                                    <div class="font-mono text-[9px] text-slate-400 mt-0.5">{{ $permission->name }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.roles.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        إلغاء
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition shadow-xs cursor-pointer">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
