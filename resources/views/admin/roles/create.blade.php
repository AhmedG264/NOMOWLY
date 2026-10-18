@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
            <span aria-hidden="true">→</span>
            <span>العودة إلى قائمة الأدوار والصلاحيات</span>
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold mb-3 border border-indigo-200">
                <span>➕</span>
                <span>دور إداري مخصص</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">إنشاء دور إداري جديد</h1>
            <p class="text-xs text-slate-500 mt-1">
                قم بتحديد بيانات الدور الإداري واختيار الصلاحيات الحبيبية المسموح بها بدقة.
            </p>
        </div>

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

        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
            @csrf

            <!-- Role Core Data Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        الاسم الرمزي البرمجي (Role Code) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: SUPPORT_SUPERVISOR" class="w-full text-xs font-mono rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none uppercase" required>
                    <div class="text-[11px] text-slate-400 mt-1">حروف إنجليزية كبيرة وأرقام وشرطة سفلية فقط دون مسافات.</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        المسمى المعروض باللغة العربية (Display Label) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="label" value="{{ old('label') }}" placeholder="مثال: مشرف الدعم الفني" class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-bold" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">وصف الدور والمسؤوليات</label>
                <textarea name="description" rows="2" placeholder="وصف موجز للمهام والصلاحيات الموكلة لهذا الدور..." class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">{{ old('description') }}</textarea>
            </div>

            <!-- Permissions Selection by Category -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">تعيين الصلاحيات الحبيبية (Permissions)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">اختر الصلاحيات الممنوحة لهذا الدور حسب المجال الوظيفي.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach ($permissionsByCategory as $category => $permissions)
                        <div class="rounded-2xl border border-slate-200/80 p-4 bg-slate-50/50 space-y-3" x-data="{ checked: false }">
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
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', []))) class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
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

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.roles.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    إلغاء
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition shadow-xs cursor-pointer">
                    حفظ وإنشاء الدور
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
