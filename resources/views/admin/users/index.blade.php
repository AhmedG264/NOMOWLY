@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>👥</span>
                <span>إدارة المستخدمين والتحكم في الأدوار</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">إدارة مستخدمي المنصة والأدوار</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                استعراض حسابات المنصة، وإسناد وتعديل الأدوار الإدارية والتجارية، وتوثيق التغييرات في سجل التدقيق.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                إدارة الأدوار والصلاحيات
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
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-sm font-black bg-indigo-600 text-white shadow-xs">
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

    <!-- Filters & Search -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث بالبريد الإلكتروني، الاسم، أو المعرف..." class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            </div>

            <div class="w-full sm:w-64">
                <select name="role" class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">جميع الأدوار</option>
                    @foreach ($allRoles as $r)
                        <option value="{{ $r->name }}" @selected(request('role') === $r->name)>
                            {{ $r->label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full sm:w-auto px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition shadow-xs cursor-pointer whitespace-nowrap">
                تطبيق البحث
            </button>

            @if (request()->anyFilled(['q', 'role']))
                <a href="{{ route('admin.users.index') }}" class="w-full sm:w-auto px-4 py-3 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold text-center transition whitespace-nowrap">
                    إلغاء التصفية
                </a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">حسابات مستخدمي المنصة</h2>
            <div class="text-xs font-bold text-slate-500">
                إجمالي النتائج: {{ number_format($users->total()) }} مستخدم
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                        <th class="py-3.5 px-4">المستخدم</th>
                        <th class="py-3.5 px-4">الأدوار المسندة</th>
                        <th class="py-3.5 px-4">نوع الملف التجاري</th>
                        <th class="py-3.5 px-4">الحالة</th>
                        <th class="py-3.5 px-4">تاريخ التسجيل</th>
                        <th class="py-3.5 px-4 text-center">إسناد الأدوار</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- User info -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                    <span>{{ $user->name }}</span>
                                    @if ($user->isSuperAdmin())
                                        <span title="مدير عام">👑</span>
                                    @endif
                                </div>
                                <div class="font-mono text-[11px] text-slate-500 mt-0.5">{{ $user->email }}</div>
                            </td>

                            <!-- Assigned Roles -->
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black border {{ $role->isSuperAdmin() ? 'bg-purple-50 text-purple-700 border-purple-200' : ($role->is_system ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200') }}">
                                            {{ $role->label }}
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600">
                                            {{ $user->role->label() }}
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Profile type -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($user->brandProfile)
                                    <span class="font-bold text-slate-800">{{ $user->brandProfile->company_name }}</span>
                                    <div class="text-[10px] text-slate-400">علامة تجارية</div>
                                @elseif ($user->marketerProfile)
                                    <span class="font-bold text-slate-800">{{ $user->marketerProfile->full_name }}</span>
                                    <div class="text-[10px] text-slate-400">مسوق بالعمولة</div>
                                @else
                                    <span class="text-slate-500 font-medium">حساب إداري</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $user->isActive() ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    {{ $user->status->label() }}
                                </span>
                            </td>

                            <!-- Created At -->
                            <td class="py-4 px-4 whitespace-nowrap text-slate-500">
                                {{ $user->created_at->format('Y-m-d') }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('admin.users.roles.edit', $user) }}" class="inline-flex items-center justify-center px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold transition shadow-2xs">
                                    إسناد الأدوار
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">👥</div>
                                <div class="font-bold text-slate-800">لا يوجد مستخدمون يطابقون معايير البحث</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
