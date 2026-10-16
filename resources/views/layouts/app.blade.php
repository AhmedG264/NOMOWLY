<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'منصة نمولي | Nomowly — سوق التسويق بالأداء واقتصاد صناع المحتوى العربي' }}</title>
    <meta name="description" content="نمولي (Nomowly) - سوق التسويق بالأداء في العالم العربي. نربط العلامات التجارية والمتاجر الإلكترونية بنخبة صناع المحتوى والمسوقين ومشتري الإعلانات، مع دفع مقابل المبيعات المؤكدة فقط.">

    <!-- Open Graph / Social Meta -->
    <meta property="og:title" content="منصة نمولي | Nomowly — سوق التسويق بالأداء العربي">
    <meta property="og:description" content="اربط علامتك التجارية بنخبة صناع المحتوى والمسوقين.. وادفع فقط مقابل المبيعات المؤكدة (CPO/CPA).">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_AR">
    <meta name="theme-color" content="#059669">

    <!-- Google Fonts: Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">

    <!-- Styles & Scripts (Tailwind CSS + Alpine.js via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between selection:bg-emerald-500 selection:text-white" x-data="{ mobileMenuOpen: false }">
    <!-- Header -->
    <header class="border-b border-slate-200/80 bg-white/95 backdrop-blur-md sticky top-0 z-50 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-20 flex items-center justify-between gap-4">
                <!-- Brand Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0 group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-2xl shadow-md group-hover:shadow-emerald-500/25 transition">
                        ن
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-2xl font-black text-slate-900 tracking-tight">نمولي</span>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full">Nomowly</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-semibold block -mt-0.5">سوق التسويق بالأداء العربي</span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-1 xl:gap-2">
                    <a href="{{ route('home') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('home') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        الرئيسية
                    </a>
                    <a href="{{ route('marketplace') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('marketplace') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        سوق الحملات
                    </a>
                    <a href="{{ route('for-brands') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('for-brands') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        للعلامات التجارية
                    </a>
                    <a href="{{ route('for-marketers') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('for-marketers') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        للمسوقين والمبدعين
                    </a>
                    <a href="{{ route('how-it-works') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('how-it-works') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        كيف تعمل المنصة
                    </a>
                    <a href="{{ route('pricing') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('pricing') ? 'text-emerald-700 bg-emerald-50/80' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        الأسعار
                    </a>
                </nav>

                <!-- Auth / User CTA -->
                <div class="hidden sm:flex items-center gap-3">
                    @auth
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold px-3.5 py-2 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition whitespace-nowrap">
                                لوحة الإدارة العامة
                            </a>
                        @elseif(auth()->user()->isBrand())
                            <a href="{{ route('brand.dashboard') }}" class="text-xs font-bold px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition whitespace-nowrap">
                                لوحة العلامة التجارية
                            </a>
                        @else
                            <a href="{{ route('marketer.dashboard') }}" class="text-xs font-bold px-3.5 py-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 transition whitespace-nowrap">
                                لوحة المسوق والمبدع
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-slate-500 hover:text-rose-600 px-2.5 py-2 rounded-xl hover:bg-rose-50 transition cursor-pointer whitespace-nowrap">
                                تسجيل الخروج
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-slate-700 hover:text-emerald-700 px-3.5 py-2 rounded-xl hover:bg-slate-100 transition whitespace-nowrap">
                            تسجيل الدخول
                        </a>
                        <a href="{{ route('register') }}" class="text-sm font-black text-white bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 rounded-xl shadow-sm hover:shadow-emerald-600/20 transition whitespace-nowrap">
                            ابدأ الآن مجاناً
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="flex sm:hidden items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            type="button"
                            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-hidden"
                            aria-label="القائمة الرئيسية">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Dropdown -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="lg:hidden pb-4 pt-2 border-t border-slate-200/70 space-y-1">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">الرئيسية</a>
                <a href="{{ route('marketplace') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">سوق الحملات</a>
                <a href="{{ route('for-brands') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">للعلامات التجارية</a>
                <a href="{{ route('for-marketers') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">للمسوقين والمبدعين</a>
                <a href="{{ route('how-it-works') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">كيف تعمل المنصة</a>
                <a href="{{ route('pricing') }}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100">الأسعار</a>

                <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                    @auth
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-center text-xs font-bold py-2 rounded-xl bg-indigo-50 text-indigo-700">لوحة الإدارة</a>
                        @elseif(auth()->user()->isBrand())
                            <a href="{{ route('brand.dashboard') }}" class="text-center text-xs font-bold py-2 rounded-xl bg-emerald-50 text-emerald-700">لوحة العلامة التجارية</a>
                        @else
                            <a href="{{ route('marketer.dashboard') }}" class="text-center text-xs font-bold py-2 rounded-xl bg-teal-50 text-teal-700">لوحة المسوق</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-center text-xs font-bold py-2 text-rose-600 bg-rose-50 rounded-xl">تسجيل الخروج</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-center text-sm font-bold py-2 text-slate-700 hover:bg-slate-100 rounded-xl">تسجيل الدخول</a>
                        <a href="{{ route('register') }}" class="text-center text-sm font-black py-2.5 bg-emerald-600 text-white rounded-xl shadow-xs">ابدأ الآن مجاناً</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Slot -->
    <main class="flex-grow">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Professional Nomowly Footer -->
    <footer class="border-t border-slate-200 bg-white pt-16 pb-12 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                <!-- Brand Summary -->
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-xl shadow-md">
                            ن
                        </div>
                        <div>
                            <span class="text-2xl font-black text-slate-900 tracking-tight block">نمولي</span>
                            <span class="text-xs text-emerald-700 font-bold block -mt-1 uppercase tracking-wider">Nomowly</span>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6 font-normal max-w-md">
                        المنصة الإقليمية الرائدة للتسويق بالأداء واقتصاد صناع المحتوى في مصر ودول الخليج العربي. نربط المتاجر والعلامات التجارية الطموحة بنخبة المسوقين ومشتري الإعلانات لتحقيق مبيعات مؤكدة، بدفع حصري مقابل النتائج المحققة.
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                        <span class="font-bold text-slate-700">الأسواق النشطة:</span>
                        <span>مصر</span>
                        <span>·</span>
                        <span>السعودية</span>
                        <span>·</span>
                        <span>الإمارات</span>
                        <span>·</span>
                        <span>الكويت</span>
                        <span>·</span>
                        <span>قطر</span>
                        <span>·</span>
                        <span>عمان</span>
                        <span>·</span>
                        <span>البحرين</span>
                    </div>
                </div>

                <!-- Column: For Brands -->
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4">للعلامات التجارية</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600 font-medium">
                        <li><a href="{{ route('for-brands') }}" class="hover:text-emerald-700 transition">أطلق حملتك الأولى</a></li>
                        <li><a href="{{ route('marketplace') }}" class="hover:text-emerald-700 transition">تصفح سوق الحملات</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-emerald-700 transition">نموذج الدفع بالأداء (CPO)</a></li>
                        <li><a href="{{ route('register', ['role' => 'BRAND']) }}" class="text-emerald-700 font-bold hover:underline">سجل كعلامة تجارية</a></li>
                    </ul>
                </div>

                <!-- Column: For Marketers -->
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4">للمسوقين والمبدعين</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600 font-medium">
                        <li><a href="{{ route('for-marketers') }}" class="hover:text-emerald-700 transition">دليل صناع المحتوى</a></li>
                        <li><a href="{{ route('for-marketers') }}#media-buyers" class="hover:text-emerald-700 transition">برامج مشتري الإعلانات</a></li>
                        <li><a href="{{ route('marketplace') }}" class="hover:text-emerald-700 transition">تصفح فرص الحملات</a></li>
                        <li><a href="{{ route('register', ['role' => 'MARKETER']) }}" class="text-emerald-700 font-bold hover:underline">انضم كصانع محتوى / مسوق</a></li>
                    </ul>
                </div>

                <!-- Column: Platform & Help -->
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4">المنصة والمساندة</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600 font-medium">
                        <li><a href="{{ route('how-it-works') }}" class="hover:text-emerald-700 transition">كيف تعمل نمولي</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-emerald-700 transition">الأسعار والعمولات</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-emerald-700 transition">تسجيل الدخول</a></li>
                        <li class="pt-2 text-xs text-slate-500">
                            الدعم والاستفسارات:<br>
                            <a href="mailto:hello@nomowly.com" class="font-bold text-slate-700 hover:text-emerald-700">hello@nomowly.com</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Subfooter -->
            <div class="pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>© {{ date('Y') }} منصة نمولي (Nomowly). جميع الحقوق محفوظة — منصة الأداء المعتمدة للشرق الأوسط.</p>
                <div class="flex items-center gap-6">
                    <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>السوق متاح والفرص نشطة</span>
                    </span>
                    <span>العملات الصريحة: EGP · SAR · AED · KWD · QAR · OMR · BHD</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
