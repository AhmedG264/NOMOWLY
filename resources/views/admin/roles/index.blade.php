@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>🛡️</span>
                <span>نظام الصلاحيات والتحكم في الوصول (Dynamic RBAC)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">إدارة الأدوار والصلاحيات الإدارية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تحديد الأدوار، وتوزيع الصلاحيات الحبيبية، وإسناد المسؤوليات بدقة مع الحفاظ على حماية المدير العام.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.create') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-xs transition flex items-center gap-1.5">
                <span>+</span>
                <span>إنشاء دور إداري جديد</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 rounded-xl text-xs sm:text-sm font-bold border border-rose-500/30 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>🛡️</span>
            <span>سجل العمليات والمراقبة</span>
        </a>
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl text-sm font-black bg-indigo-600 text-white shadow-xs">
            الأدوار والصلاحيات ({{ $stats['total_roles'] }})
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة المستخدمين
        </a>
    </div>

    <!-- Status Alerts -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold flex items-center gap-2">
            <span class="text-rose-600 font-black">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- System KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي الأدوار بالمنصة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total_roles'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الأدوار الأساسية المحمية</div>
            <div class="text-2xl font-black text-indigo-600 mt-1">{{ $stats['system_roles'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الأدوار الإدارية المخصصة</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['custom_roles'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي الصلاحيات الحبيبية</div>
            <div class="text-2xl font-black text-purple-600 mt-1">{{ $stats['total_permissions'] }}</div>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">قائمة الأدوار والصلاحيات المرتبطة</h2>
            <span class="text-xs text-slate-500 font-bold">يتم فرض الصلاحيات في Backend عبر Middleware وGates</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                        <th class="py-3 px-4">اسم وتسمية الدور</th>
                        <th class="py-3 px-4">نوع الدور</th>
                        <th class="py-3 px-4">المستخدمون المسندون</th>
                        <th class="py-3 px-4">الصلاحيات الممنوحة</th>
                        <th class="py-3 px-4">الوصف</th>
                        <th class="py-3 px-4 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Role Name & Label -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                    <span>{{ $role->label }}</span>
                                    @if ($role->isSuperAdmin())
                                        <span class="text-xs" title="دور المدير العام محمي">👑</span>
                                    @endif
                                </div>
                                <div class="font-mono text-[11px] text-slate-400 mt-0.5">{{ $role->name }}</div>
                            </td>

                            <!-- Type -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($role->is_system)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        أساسي محمي (System)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        إداري مخصص (Custom)
                                    </span>
                                @endif
                            </td>

                            <!-- Users count -->
                            <td class="py-4 px-4 whitespace-nowrap font-bold text-slate-700">
                                <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">
                                    {{ $role->users_count }} مستخدم
                                </span>
                            </td>

                            <!-- Permissions count -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($role->isSuperAdmin())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200">
                                        جميع الصلاحيات (مطلقة)
                                    </span>
                                @else
                                    <span class="font-bold text-slate-700">{{ $role->permissions_count }} صلاحية</span>
                                @endif
                            </td>

                            <!-- Description -->
                            <td class="py-4 px-4 text-slate-500 max-w-xs text-xs leading-relaxed">
                                {{ $role->description ?: '-' }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                                        {{ $role->isSuperAdmin() ? 'استعراض الصلاحيات' : 'تعديل الصلاحيات' }}
                                    </a>

                                    @if (!$role->isProtected() && $role->users_count === 0)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور الإداري نهائياً؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition border border-rose-200 cursor-pointer">
                                                حذف
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
