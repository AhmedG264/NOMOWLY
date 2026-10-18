@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
            <span aria-hidden="true">→</span>
            <span>العودة إلى إدارة المستخدمين</span>
        </a>
        <span class="text-xs font-mono text-slate-400">معرف المستخدم: {{ $user->id }}</span>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        إسناد الأدوار والصلاحيات
                    </span>
                    @if ($user->isSuperAdmin())
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-purple-50 text-purple-700 border border-purple-200">
                            👑 مدير عام
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    إسناد وتعديل أدوار: {{ $user->name }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    البريد الإلكتروني: <strong class="text-slate-800 font-mono">{{ $user->email }}</strong>
                </p>
            </div>
            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 text-center min-w-[130px]">
                <div class="text-[11px] font-bold text-slate-500">حالة الحساب</div>
                <div class="text-sm font-black text-slate-900 mt-0.5">{{ $user->status->label() }}</div>
            </div>
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

        @if (session('error'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800 font-bold flex items-center gap-2">
                <span class="text-rose-600 font-black">✕</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.roles.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Roles Selection -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">الأدوار المتاحة للإسناد</h2>
                        <p class="text-xs text-slate-500 mt-0.5">يمكن إسناد أكثر من دور إداري أو تجاري للمستخدم عند الحاجة.</p>
                    </div>
                </div>

                @php
                    $assignedRoleIds = old('roles', $user->roles->pluck('id')->toArray());
                @endphp

                <div class="space-y-2.5">
                    @foreach ($availableRoles as $role)
                        @php
                            $isSuperAdminRole = $role->isSuperAdmin();
                            $cannotAssignSuperAdmin = $isSuperAdminRole && !auth()->user()->isSuperAdmin();
                        @endphp
                        <label class="flex items-start gap-3 p-4 rounded-2xl border transition select-none {{ in_array($role->id, $assignedRoleIds) ? 'bg-indigo-50/40 border-indigo-300' : 'bg-white border-slate-200/80 hover:border-slate-300' }} {{ $cannotAssignSuperAdmin ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $assignedRoleIds)) @disabled($cannotAssignSuperAdmin) class="mt-1 rounded text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1 text-xs">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900 text-sm">{{ $role->label }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">({{ $role->name }})</span>
                                    @if ($role->isSuperAdmin())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200">حماية عليا</span>
                                    @elseif ($role->is_system)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">أساسي</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">إداري مخصص</span>
                                    @endif
                                </div>
                                <div class="text-slate-500 mt-1 leading-relaxed">
                                    {{ $role->description ?: 'لا يوجد وصف مخصص لهذا الدور.' }}
                                </div>
                                @if ($cannotAssignSuperAdmin)
                                    <div class="text-[11px] text-rose-600 font-bold mt-1">
                                        يتطلب صلاحية المدير العام لمنح هذا الدور.
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Reason Field for Audit Log -->
            <div class="pt-4 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    سبب التعديل أو مبرر الإسناد (توثيق التدقيق) <span class="text-slate-400 font-normal">(اختياري)</span>
                </label>
                <input type="text" name="reason" value="{{ old('reason') }}" placeholder="مثال: تعيين كمراجع مالي معتمد بعد اكتمال التدريب..." class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                <div class="text-[11px] text-slate-400 mt-1">سيتم توثيق هذا السبب مع بياناتك في سجل العمليات والمراقبة بشكل غير قابل للتعديل.</div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    إلغاء
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition shadow-xs cursor-pointer">
                    حفظ وإسناد الأدوار
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
