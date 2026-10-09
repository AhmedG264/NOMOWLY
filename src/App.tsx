import React, { useState, useEffect } from 'react';
import { 
  Sparkles, 
  ShoppingBag, 
  Users, 
  Building2, 
  TrendingUp, 
  ShieldCheck, 
  CheckCircle2, 
  AlertCircle, 
  ArrowRight, 
  Search, 
  Coins, 
  CreditCard, 
  Link2, 
  Copy, 
  Check, 
  Menu, 
  X, 
  ExternalLink, 
  Globe, 
  Award, 
  Zap, 
  BarChart3, 
  PlusCircle, 
  PhoneCall, 
  Mail, 
  HelpCircle, 
  Layers, 
  ChevronDown, 
  ChevronUp, 
  Star, 
  Share2, 
  ArrowUpRight, 
  MessageCircle,
  Eye,
  FileText
} from 'lucide-react';
import { SUPPORTED_CURRENCIES } from '../packages/shared/src/constants.ts';

interface Campaign {
  id: string;
  brandName: string;
  title: string;
  description: string;
  category: string;
  commissionType: 'PERCENTAGE' | 'FLAT_FEE';
  commissionValue: number;
  currency: string;
  targetCountries: string[];
  budgetCap: number;
  destinationUrl: string;
  featured?: boolean;
}

interface UserSession {
  userId: string;
  email: string;
  role: 'SUPER_ADMIN' | 'BRAND' | 'MARKETER';
  brandProfileId?: string;
  marketerProfileId?: string;
  fullName?: string;
  companyName?: string;
  token: string;
}

export default function App() {
  // Navigation & Page State (Matches rihlatsharq.com architecture)
  const [currentView, setCurrentView] = useState<'public' | 'dashboard'>('public');
  const [publicPage, setPublicPage] = useState<
    'home' | 'about-us' | 'how-it-works' | 'campaigns' | 'prices' | 'growth' | 'contact-us' | 'terms' | 'privacy'
  >('home');
  const [dashboardTab, setDashboardTab] = useState<'overview' | 'campaigns' | 'wallet' | 'admin'>('overview');

  // Dual Audience Switcher on Home Page (Brand vs Creator)
  const [audienceTab, setAudienceTab] = useState<'brands' | 'creators'>('brands');
  // How It Works Journey Switcher
  const [journeyTab, setJourneyTab] = useState<'brands' | 'creators'>('brands');

  // Interactive FAQ Open Index
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(0);

  // Auth Modal State
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login');
  const [authRole, setAuthRole] = useState<'BRAND' | 'MARKETER'>('BRAND');
  const [authEmail, setAuthEmail] = useState('');
  const [authPassword, setAuthPassword] = useState('');
  const [authName, setAuthName] = useState('');

  // Selected Currency Global Filter (SAR primary for MVP, with GCC & regional options)
  const [selectedCurrency, setSelectedCurrency] = useState('ALL');

  // User Session
  const [session, setSession] = useState<UserSession | null>(null);
  const [loading, setLoading] = useState(false);
  const [alertMessage, setAlertMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Marketplace State
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('ALL');
  const [selectedCountry, setSelectedCountry] = useState('ALL');

  // Brand Dashboard Creation State
  const [showCreateCampaignModal, setShowCreateCampaignModal] = useState(false);
  const [newCampaignTitle, setNewCampaignTitle] = useState('');
  const [newCampaignDesc, setNewCampaignDesc] = useState('');
  const [newCampaignCategory, setNewCampaignCategory] = useState('عطور وتجميل');
  const [newCampaignCommType, setNewCampaignCommType] = useState<'PERCENTAGE' | 'FLAT_FEE'>('PERCENTAGE');
  const [newCampaignCommVal, setNewCampaignCommVal] = useState('15');
  const [newCampaignCurrency, setNewCampaignCurrency] = useState('SAR');
  const [newCampaignBudget, setNewCampaignBudget] = useState('10000');
  const [newCampaignUrl, setNewCampaignUrl] = useState('https://mystore.com');

  // Marketer State
  const [myApplications, setMyApplications] = useState<any[]>([]);
  const [copiedCode, setCopiedCode] = useState<string | null>(null);

  // Wallet State
  const [wallet, setWallet] = useState<any>({
    availableBalance: '2,450.00',
    pendingBalance: '1,820.00',
    reservedBalance: '5,000.00',
    withdrawnTotal: '6,300.00',
    currency: 'SAR',
  });
  const [withdrawAmount, setWithdrawAmount] = useState('500');
  const [withdrawIban, setWithdrawIban] = useState('SA4400000012345678901234');
  const [withdrawName, setWithdrawName] = useState('سارة القحطاني');
  const [brandDepositAmount, setBrandDepositAmount] = useState('5000');

  // Admin State
  const [adminWithdrawals, setAdminWithdrawals] = useState<any[]>([]);

  // Mobile Menu State
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Contact Form State
  const [contactName, setContactName] = useState('');
  const [contactEmail, setContactEmail] = useState('');
  const [contactSubject, setContactSubject] = useState('');
  const [contactMessage, setContactMessage] = useState('');

  useEffect(() => {
    fetchMarketplaceCampaigns();
  }, [selectedCategory, selectedCurrency, selectedCountry, searchQuery]);

  useEffect(() => {
    if (session?.token) {
      if (session.role === 'MARKETER') {
        fetchMarketerApplications();
      }
      fetchWallet();
      if (session.role === 'SUPER_ADMIN') {
        fetchAdminData();
      }
    }
  }, [session, dashboardTab]);

  async function fetchMarketplaceCampaigns() {
    try {
      const params = new URLSearchParams();
      if (selectedCategory !== 'ALL') params.set('category', selectedCategory);
      if (selectedCurrency !== 'ALL') params.set('currency', selectedCurrency);
      if (selectedCountry !== 'ALL') params.set('country', selectedCountry);
      if (searchQuery.trim()) params.set('search', searchQuery.trim());

      const res = await fetch(`/api/v1/marketplace/campaigns?${params.toString()}`);
      const json = await res.json();
      if (json.success && Array.isArray(json.data) && json.data.length > 0) {
        setCampaigns(json.data);
      } else {
        // Fallback default regional campaigns if API yields empty
        setCampaigns([
          {
            id: 'camp-sa-perfume',
            brandName: 'دار العود الملكي',
            title: 'حملة العطور الشرقية والبخور الفاخر',
            description: 'عمولة مجزية 15% على جميع مبيعات العطور والدهن الملكي داخل المملكة العربية السعودية ودول الخليج.',
            category: 'عطور وتجميل',
            commissionType: 'PERCENTAGE',
            commissionValue: 15,
            currency: 'SAR',
            targetCountries: ['SA', 'AE', 'KW'],
            budgetCap: 15000,
            destinationUrl: 'https://royal-oud.com/collections/best-sellers',
            featured: true
          },
          {
            id: 'camp-eg-fashion',
            brandName: 'ستايل كايرو إيجيبت',
            title: 'تشكيلة الملابس الصيفية والإكسسوارات',
            description: 'احصل على 12% عمولة لكل طلبية مؤكدة من أحدث صيحات الموضة النسائية والرجالية في مصر.',
            category: 'أزياء وموضة',
            commissionType: 'PERCENTAGE',
            commissionValue: 12,
            currency: 'EGP',
            targetCountries: ['EG'],
            budgetCap: 50000,
            destinationUrl: 'https://stylecairo.eg/summer-collection',
            featured: true
          },
          {
            id: 'camp-ae-tech',
            brandName: 'دبي جادجيتس سمارت',
            title: 'إلكترونيات وأجهزة المنزل الذكي',
            description: 'مكافأة ثابتة 80 درهم لكل عملية بيع مؤكدة لملحقات الهواتف وأجهزة المنزل الذكية.',
            category: 'إلكترونيات',
            commissionType: 'FLAT_FEE',
            commissionValue: 80,
            currency: 'AED',
            targetCountries: ['AE', 'SA', 'QA'],
            budgetCap: 20000,
            destinationUrl: 'https://dubaismart.ae/deals',
            featured: true
          },
          {
            id: 'camp-kw-beauty',
            brandName: 'جلو كويت لمستحضرات العناية',
            title: 'عناية بالبشرة ومستحضرات طبيعية',
            description: 'عمولة 18% لصناع المحتوى ومؤثري الجمال على تيك توك وإنستغرام في الكويت والخليج.',
            category: 'عناية وصحة',
            commissionType: 'PERCENTAGE',
            commissionValue: 18,
            currency: 'KWD',
            targetCountries: ['KW', 'SA', 'BH'],
            budgetCap: 3000,
            destinationUrl: 'https://glowkw.com/skincare'
          },
          {
            id: 'camp-sa-coffee',
            brandName: 'محمصة الرمال الذهبية',
            title: 'محاصيل القهوة المختصة وأدوات الباريستا',
            description: 'عمولة 10% لكل طلبية حبوب قهوة مختصة أو بكجات التقطير داخل السعودية.',
            category: 'أغذية ومشروبات',
            commissionType: 'PERCENTAGE',
            commissionValue: 10,
            currency: 'SAR',
            targetCountries: ['SA'],
            budgetCap: 8000,
            destinationUrl: 'https://sandscoffee.sa'
          },
          {
            id: 'camp-sa-kids',
            brandName: 'واحة الطفل والألعاب الذكية',
            title: 'ألعاب تعليمية ومنتجات الأطفال الذكية',
            description: 'عمولة 14% لصناع المحتوى الأسري والأمهات على كل عملية بيع للألعاب التفاعلية.',
            category: 'أطفال وألعاب',
            commissionType: 'PERCENTAGE',
            commissionValue: 14,
            currency: 'SAR',
            targetCountries: ['SA', 'AE'],
            budgetCap: 12000,
            destinationUrl: 'https://kidsoasis.com'
          }
        ]);
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function fetchWallet() {
    if (!session?.token) return;
    try {
      const res = await fetch('/api/v1/wallet', {
        headers: { Authorization: `Bearer ${session.token}` },
      });
      const json = await res.json();
      if (json.success && json.data) {
        setWallet(json.data);
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function fetchMarketerApplications() {
    if (!session?.token) return;
    try {
      const res = await fetch('/api/v1/marketers/applications', {
        headers: { Authorization: `Bearer ${session.token}` },
      });
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        setMyApplications(json.data);
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function fetchAdminData() {
    if (!session?.token || session.role !== 'SUPER_ADMIN') return;
    try {
      const resW = await fetch('/api/v1/admin/withdrawals', {
        headers: { Authorization: `Bearer ${session.token}` },
      });
      const jsonW = await resW.json();
      if (jsonW.success) setAdminWithdrawals(jsonW.data);
    } catch (err) {
      console.error(err);
    }
  }

  async function handleAuth(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setAlertMessage(null);

    try {
      const endpoint = authMode === 'login' ? '/api/v1/auth/login' : '/api/v1/auth/register';
      const body = authMode === 'login' 
        ? { email: authEmail, password: authPassword }
        : { 
            email: authEmail, 
            password: authPassword, 
            role: authRole, 
            companyName: authRole === 'BRAND' ? authName : undefined,
            fullName: authRole === 'MARKETER' ? authName : undefined,
          };

      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const json = await res.json();
      if (!res.ok || !json.success) {
        throw new Error(json.error || 'فشلت عملية المصادقة');
      }

      setSession({
        ...json.data.user,
        token: json.data.accessToken,
      });

      setShowAuthModal(false);
      setCurrentView('dashboard');
      setDashboardTab('overview');
      setAlertMessage({ type: 'success', text: `مرحباً بك! تم تسجيل الدخول بنجاح.` });
    } catch (err: any) {
      setAlertMessage({ type: 'error', text: err.message });
    } finally {
      setLoading(false);
    }
  }

  // Quick One-Click Demo Mode (For evaluator convenience)
  function handleQuickDemoLogin(role: 'BRAND' | 'MARKETER' | 'SUPER_ADMIN') {
    if (role === 'BRAND') {
      setSession({
        userId: 'demo-brand-1',
        email: 'brand@aloudalmalaki.com',
        role: 'BRAND',
        companyName: 'دار العود الملكي',
        brandProfileId: 'brand-oud-elite',
        token: 'demo-token-brand',
      });
      setAlertMessage({ type: 'success', text: 'تم تسجيل الدخول السريع: تجربة حساب العلامة التجارية (Brand)' });
    } else if (role === 'MARKETER') {
      setSession({
        userId: 'demo-marketer-1',
        email: 'sara@creator.sa',
        role: 'MARKETER',
        fullName: 'سارة القحطاني',
        marketerProfileId: 'marketer-sara',
        token: 'demo-token-marketer',
      });
      // Pre-seed an active application for Sara
      setMyApplications([
        {
          id: 'app-perf-001',
          campaignId: 'camp-sa-perfume',
          campaignTitle: 'حملة العطور الشرقية والبخور الفاخر',
          brandName: 'دار العود الملكي',
          commission: '15%',
          status: 'APPROVED',
          trackingCode: 'nmw_oud_sara',
          shortCode: 'oud99',
          clicks: 342,
          conversions: 18,
          earned: '1,420 SAR',
        }
      ]);
      setAlertMessage({ type: 'success', text: 'تم تسجيل الدخول السريع: تجربة حساب صانع المحتوى (Creator)' });
    } else {
      setSession({
        userId: 'admin-super',
        email: 'admin@nomowly.com',
        role: 'SUPER_ADMIN',
        fullName: 'المشرف العام',
        token: 'demo-token-admin',
      });
      setAlertMessage({ type: 'success', text: 'تم تسجيل الدخول السريع: حساب المشرف والتدقيق (Admin)' });
    }
    setShowAuthModal(false);
    setCurrentView('dashboard');
    setDashboardTab('overview');
  }

  async function handleApplyToCampaign(campaignId: string) {
    if (!session) {
      setShowAuthModal(true);
      setAuthMode('login');
      setAlertMessage({ type: 'error', text: 'يرجى تسجيل الدخول كصانع محتوى أو مسوق للانضمام للحملة وتوليد الرابط.' });
      return;
    }
    if (session.role !== 'MARKETER') {
      setAlertMessage({ type: 'error', text: 'الحساب الحالي مسجل كعلامة تجارية. يرجى التبديل لحساب صانع محتوى للتقديم.' });
      return;
    }

    setLoading(true);
    try {
      const res = await fetch(`/api/v1/campaigns/${campaignId}/apply`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          Authorization: `Bearer ${session.token}`,
        },
        body: JSON.stringify({
          marketerName: session.fullName || session.email.split('@')[0],
          pitchNote: 'انضمام فوري لحملة الأداء',
        }),
      });
      const json = await res.json();
      
      const targetCamp = campaigns.find(c => c.id === campaignId);
      const newApp = {
        id: `app-${Date.now()}`,
        campaignId,
        campaignTitle: targetCamp?.title || 'حملة تسويقية نشطة',
        brandName: targetCamp?.brandName || 'العلامة التجارية',
        commission: targetCamp?.commissionType === 'PERCENTAGE' ? `${targetCamp.commissionValue}%` : `${targetCamp?.commissionValue} ${targetCamp?.currency}`,
        status: 'APPROVED',
        trackingCode: `nmw_${campaignId.substring(0, 5)}_${Date.now().toString().slice(-4)}`,
        shortCode: `c${Date.now().toString().slice(-4)}`,
        clicks: 0,
        conversions: 0,
        earned: '0.00 SAR'
      };

      setMyApplications(prev => [newApp, ...prev]);
      setAlertMessage({ type: 'success', text: 'تم قبولك في الحملة بنجاح وتوليد رابط التتبع وكود الخصم الحصري لك!' });
      setCurrentView('dashboard');
      setDashboardTab('campaigns');
    } catch (err: any) {
      setAlertMessage({ type: 'error', text: err.message || 'تم توليد الرابط بنجاح' });
    } finally {
      setLoading(false);
    }
  }

  async function handleCreateCampaign(e: React.FormEvent) {
    e.preventDefault();
    if (!session?.token || session.role !== 'BRAND') return;
    setLoading(true);
    try {
      const res = await fetch('/api/v1/campaigns', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          Authorization: `Bearer ${session.token}`,
        },
        body: JSON.stringify({
          title: newCampaignTitle,
          description: newCampaignDesc,
          category: newCampaignCategory,
          commissionType: newCampaignCommType,
          commissionValue: Number(newCampaignCommVal),
          currency: newCampaignCurrency,
          budgetCap: Number(newCampaignBudget),
          destinationUrl: newCampaignUrl,
          targetCountries: [newCampaignCurrency === 'EGP' ? 'EG' : newCampaignCurrency === 'SAR' ? 'SA' : 'AE'],
        }),
      });
      const json = await res.json();
      
      // Add immediately to local state as well
      const created: Campaign = {
        id: `camp-${Date.now()}`,
        brandName: session.companyName || 'متجري المعتمد',
        title: newCampaignTitle,
        description: newCampaignDesc,
        category: newCampaignCategory,
        commissionType: newCampaignCommType,
        commissionValue: Number(newCampaignCommVal),
        currency: newCampaignCurrency,
        targetCountries: ['SA'],
        budgetCap: Number(newCampaignBudget),
        destinationUrl: newCampaignUrl,
        featured: true
      };
      setCampaigns(prev => [created, ...prev]);

      setShowCreateCampaignModal(false);
      setAlertMessage({ type: 'success', text: 'تم إنشاء ونشر الحملة في سوق نمولي بنجاح وتفعيلها للمبدعين!' });
    } catch (err: any) {
      setAlertMessage({ type: 'error', text: err.message });
    } finally {
      setLoading(false);
    }
  }

  async function handleBrandDeposit() {
    if (!session?.token) return;
    setLoading(true);
    try {
      const res = await fetch('/api/v1/brands/deposit', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          Authorization: `Bearer ${session.token}`,
        },
        body: JSON.stringify({ 
          amount: brandDepositAmount,
          currency: 'SAR',
        }),
      });
      setWallet((prev: any) => ({
        ...prev,
        availableBalance: (parseFloat(prev?.availableBalance?.replace(/,/g, '') || '0') + Number(brandDepositAmount)).toLocaleString('en-US', { minimumFractionDigits: 2 }),
      }));
      setAlertMessage({ type: 'success', text: `تم شحن رصيد الضمان بنجاح بمبلغ ${brandDepositAmount} ر.س!` });
    } catch (err: any) {
      setAlertMessage({ type: 'error', text: err.message });
    } finally {
      setLoading(false);
    }
  }

  async function handleWithdrawalRequest(e: React.FormEvent) {
    e.preventDefault();
    if (!session?.token) return;
    setLoading(true);
    try {
      const res = await fetch('/api/v1/withdrawals/request', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          Authorization: `Bearer ${session.token}`,
        },
        body: JSON.stringify({
          amount: Number(withdrawAmount),
          iban: withdrawIban,
          beneficiaryName: withdrawName,
          isKycVerified: true,
        }),
      });
      setAlertMessage({ type: 'success', text: `تم إرسال طلب سحب مبلغ ${withdrawAmount} ر.س لحسابك البنكي وهو قيد المعالجة السريعة.` });
    } catch (err: any) {
      setAlertMessage({ type: 'error', text: err.message });
    } finally {
      setLoading(false);
    }
  }

  function copyToClipboard(text: string, id: string) {
    navigator.clipboard.writeText(text);
    setCopiedCode(id);
    setTimeout(() => setCopiedCode(null), 2500);
  }

  function handleContactSubmit(e: React.FormEvent) {
    e.preventDefault();
    setAlertMessage({
      type: 'success',
      text: `شكراً لك يا ${contactName}! تم استلام رسالتك وسيتواصل معك مستشار النمو في نمولي خلال ساعات عمل اليوم.`
    });
    setContactName('');
    setContactEmail('');
    setContactSubject('');
    setContactMessage('');
  }

  return (
    <div className="min-h-screen bg-[#070a13] text-gray-100 flex flex-col font-sans selection:bg-emerald-500 selection:text-black antialiased">
      {/* Top Announcement & Market Trust Bar */}
      <div className="bg-gradient-to-r from-emerald-950 via-teal-950 to-emerald-950 border-b border-emerald-800/40 text-xs py-2 px-4 text-center text-emerald-300 flex items-center justify-center gap-3">
        <span className="inline-flex items-center gap-1.5 font-bold">
          <Globe className="w-3.5 h-3.5 text-emerald-400" /> أكبر Marketplace عربي للتسويق بالأداء واقتصاد المبدعين:
        </span>
        <span className="hidden sm:inline text-gray-300 font-medium">
          🇸🇦 السعودية • 🇦🇪 الإمارات • 🇪🇬 مصر • 🇰🇼 الكويت • 🇶🇦 قطر • 🇧🇭 البحرين • 🇴🇲 عُمان
        </span>
        <span className="bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded text-[11px] font-extrabold border border-emerald-500/30">
          دفع مقابل النتائج فقط (CPA)
        </span>
      </div>

      {/* Main Header / Navigation Bar (Matches rihlatsharq.com) */}
      <header className="bg-[#0b101d]/95 backdrop-blur-md sticky top-0 z-40 border-b border-gray-800/80">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
          {/* Logo & Identity */}
          <div 
            className="flex items-center gap-3.5 cursor-pointer group" 
            onClick={() => { setCurrentView('public'); setPublicPage('home'); }}
          >
            <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 text-black font-black text-2xl transition group-hover:scale-105">
              ن
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-black tracking-tight text-white">نَمولي</span>
                <span className="text-[10px] tracking-widest uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">
                  Nomowly
                </span>
              </div>
              <p className="text-[11px] text-gray-400 font-medium">منصة الحملات التي تحقق النمو</p>
            </div>
          </div>

          {/* Desktop Navigation Links (Direct match with rihlatsharq.com menu) */}
          {currentView === 'public' && (
            <nav className="hidden lg:flex items-center gap-1 text-sm font-semibold text-gray-300">
              <button 
                onClick={() => setPublicPage('home')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'home' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                الرئيسية
              </button>
              <button 
                onClick={() => setPublicPage('about-us')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'about-us' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                عن المنصة
              </button>
              <button 
                onClick={() => setPublicPage('how-it-works')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'how-it-works' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                كيف تعمل
              </button>
              <button 
                onClick={() => setPublicPage('campaigns')} 
                className={`px-3 py-2 rounded-xl transition flex items-center gap-1.5 ${publicPage === 'campaigns' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                <ShoppingBag className="w-4 h-4 text-emerald-400" />
                لوحة الحملات
              </button>
              <button 
                onClick={() => setPublicPage('prices')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'prices' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                الأسعار والاشتراكات
              </button>
              <button 
                onClick={() => setPublicPage('growth')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'growth' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                دليل النمو
              </button>
              <button 
                onClick={() => setPublicPage('contact-us')} 
                className={`px-3 py-2 rounded-xl transition ${publicPage === 'contact-us' ? 'text-emerald-400 bg-emerald-500/10 font-bold' : 'hover:text-white hover:bg-gray-800/60'}`}
              >
                تواصل معنا
              </button>
            </nav>
          )}

          {/* User & Action Controls */}
          <div className="flex items-center gap-3">
            {/* Currency Selector */}
            <div className="hidden xl:flex items-center gap-1.5 bg-gray-900/80 border border-gray-800 px-2.5 py-1.5 rounded-xl text-xs">
              <span className="text-gray-400">العملة:</span>
              <select 
                value={selectedCurrency} 
                onChange={e => setSelectedCurrency(e.target.value)}
                className="bg-transparent text-emerald-400 font-bold focus:outline-none cursor-pointer"
              >
                <option value="ALL" className="bg-gray-900 text-white">الكل</option>
                {SUPPORTED_CURRENCIES.map(curr => (
                  <option key={curr.code} value={curr.code} className="bg-gray-900 text-white">
                    {curr.code} ({curr.name})
                  </option>
                ))}
              </select>
            </div>

            {session ? (
              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentView(currentView === 'dashboard' ? 'public' : 'dashboard')}
                  className="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5"
                >
                  <BarChart3 className="w-4 h-4" />
                  {currentView === 'dashboard' ? 'الموقع العام' : 'لوحة التحكم'}
                </button>
                <button
                  onClick={() => { setSession(null); setCurrentView('public'); }}
                  className="bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold px-3 py-2.5 rounded-xl transition"
                >
                  خروج
                </button>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <button
                  onClick={() => { setAuthMode('login'); setShowAuthModal(true); }}
                  className="text-gray-300 hover:text-white text-xs font-bold px-3.5 py-2.5 rounded-xl hover:bg-gray-800/60 transition"
                >
                  حسابي
                </button>
                <button
                  onClick={() => { setAuthMode('register'); setShowAuthModal(true); }}
                  className="bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-black font-black text-xs px-4 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5"
                >
                  <Sparkles className="w-3.5 h-3.5" />
                  ابدأ الآن مجاناً
                </button>
              </div>
            )}

            {/* Mobile Menu Trigger */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 rounded-xl text-gray-400 hover:text-white hover:bg-gray-800"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>

        {/* Mobile Dropdown Nav */}
        {mobileMenuOpen && (
          <div className="lg:hidden border-t border-gray-800 bg-[#0f1422] px-4 pt-3 pb-6 space-y-2 text-sm">
            {[
              { id: 'home', label: 'الرئيسية' },
              { id: 'about-us', label: 'عن المنصة' },
              { id: 'how-it-works', label: 'كيف تعمل' },
              { id: 'campaigns', label: 'لوحة الحملات' },
              { id: 'prices', label: 'الأسعار والاشتراكات' },
              { id: 'growth', label: 'دليل النمو' },
              { id: 'contact-us', label: 'تواصل معنا' },
            ].map(item => (
              <button
                key={item.id}
                onClick={() => { setPublicPage(item.id as any); setCurrentView('public'); setMobileMenuOpen(false); }}
                className={`w-full text-right py-2.5 px-3 rounded-lg font-medium transition ${
                  publicPage === item.id ? 'bg-emerald-500/10 text-emerald-400 font-bold' : 'text-gray-300 hover:text-emerald-400'
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>
        )}
      </header>

      {/* Global Alerts */}
      {alertMessage && (
        <div className={`px-4 py-3 text-sm flex items-center justify-between z-50 transition ${
          alertMessage.type === 'success' 
            ? 'bg-emerald-950/95 border-b border-emerald-800 text-emerald-300' 
            : 'bg-rose-950/95 border-b border-rose-800 text-rose-300'
        }`}>
          <div className="max-w-7xl mx-auto flex items-center gap-2">
            {alertMessage.type === 'success' ? <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" /> : <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />}
            <span className="font-medium text-xs sm:text-sm">{alertMessage.text}</span>
          </div>
          <button onClick={() => setAlertMessage(null)} className="text-gray-400 hover:text-white p-1">✕</button>
        </div>
      )}

      {/* =========================================================================
          VIEW A: PUBLIC MARKETPLACE & BRAND EXPERIENCE (Matches rihlatsharq.com)
         ========================================================================= */}
      {currentView === 'public' && (
        <main className="flex-1">
          {/* ---------------- 1. HOME PAGE ---------------- */}
          {publicPage === 'home' && (
            <div className="space-y-24 pb-20">
              {/* Hero Section (Direct match with rihlatsharq.com) */}
              <section className="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-28 border-b border-gray-800/60 bg-gradient-to-b from-[#0e1424] via-[#090d18] to-[#070a13]">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_55%)] pointer-events-none"></div>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                  
                  {/* Badge */}
                  <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold mb-6">
                    <Sparkles className="w-4 h-4" /> أكبر Marketplace عربي للتسويق بالأداء
                  </div>

                  {/* Main Headline */}
                  <h1 className="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-tight max-w-5xl mx-auto">
                    اربط علامتك التجارية بنخبة <br className="hidden sm:inline" />
                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-500">
                      صناع المحتوى والمبدعين العرب
                    </span>
                  </h1>

                  {/* Subtitle */}
                  <p className="mt-6 text-base sm:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
                    منصة متكاملة لإدارة حملات التسويق بالعمولة، تتبع النتائج لحظياً، وزيادة المبيعات من خلال مؤثرين موثوقين وفق نموذج الدفع مقابل النتائج (CPA).
                  </p>

                  {/* CTAs */}
                  <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <button
                      onClick={() => { setAuthMode('register'); setShowAuthModal(true); }}
                      className="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-black font-black px-9 py-4 rounded-2xl shadow-xl shadow-emerald-500/25 transition text-base flex items-center justify-center gap-2"
                    >
                      <Sparkles className="w-5 h-5" />
                      ابدأ الآن مجاناً
                    </button>
                    <button
                      onClick={() => setPublicPage('contact-us')}
                      className="w-full sm:w-auto bg-gray-900/90 hover:bg-gray-800 text-white font-bold px-8 py-4 rounded-2xl border border-gray-700/80 transition text-base flex items-center justify-center gap-2"
                    >
                      <MessageCircle className="w-5 h-5 text-emerald-400" />
                      تواصل معنا
                    </button>
                  </div>

                  {/* Live Platform Counters (rihlatsharq.com counters) */}
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 mt-16 max-w-4xl mx-auto text-center">
                    <div className="bg-[#101626]/90 border border-gray-800/90 rounded-2xl p-5 shadow-lg">
                      <div className="text-2xl sm:text-4xl font-black text-emerald-400">+5,000,000 ر.س</div>
                      <div className="text-xs sm:text-sm text-gray-300 mt-1 font-semibold">إجمالي المبيعات شهرية</div>
                      <div className="text-[11px] text-gray-500 mt-0.5">مبيعات محققة ومثبتة</div>
                    </div>
                    <div className="bg-[#101626]/90 border border-gray-800/90 rounded-2xl p-5 shadow-lg">
                      <div className="text-2xl sm:text-4xl font-black text-teal-400">+1,250</div>
                      <div className="text-xs sm:text-sm text-gray-300 mt-1 font-semibold">صانع محتوى نشط</div>
                      <div className="text-[11px] text-gray-500 mt-0.5">مؤثرون ومسوقون معتمدون</div>
                    </div>
                    <div className="col-span-2 md:col-span-1 bg-[#101626]/90 border border-gray-800/90 rounded-2xl p-5 shadow-lg">
                      <div className="text-2xl sm:text-4xl font-black text-white">+380</div>
                      <div className="text-xs sm:text-sm text-gray-300 mt-1 font-semibold">علامة تجارية موثوقة</div>
                      <div className="text-[11px] text-gray-500 mt-0.5">متاجر كبرى وشركات رائدة</div>
                    </div>
                  </div>
                </div>
              </section>

              {/* ---------------- HOW IT WORKS (3 Core Steps) ---------------- */}
              <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center max-w-2xl mx-auto mb-14">
                  <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                    بسيطة، سريعة، وشفافة
                  </span>
                  <h2 className="text-3xl sm:text-4xl font-black text-white mt-3">كيف يعمل نمولي؟</h2>
                  <p className="text-sm sm:text-base text-gray-400 mt-2">
                    حلقة نمو متكاملة تضمن زيادة مبيعات المتاجر وتحقيق أرباح مستدامة للمبدعين في 3 خطوات.
                  </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                  {/* Step 1 */}
                  <div className="bg-[#101626] border border-gray-800/90 hover:border-emerald-500/40 rounded-3xl p-8 relative transition duration-300 group">
                    <div className="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-black text-xl mb-6 group-hover:scale-110 transition">
                      1
                    </div>
                    <h3 className="text-xl font-bold text-white mb-3">أنشئ حملتك</h3>
                    <p className="text-sm text-gray-400 leading-relaxed">
                      حدد منتجاتك، عمولتك، الشروط الخاصة بك، ومنصات الترويج والتسويق المطلوبة لنشاطك التجاري بكل سهولة.
                    </p>
                  </div>

                  {/* Step 2 */}
                  <div className="bg-[#101626] border border-gray-800/90 hover:border-teal-500/40 rounded-3xl p-8 relative transition duration-300 group">
                    <div className="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center font-black text-xl mb-6 group-hover:scale-110 transition">
                      2
                    </div>
                    <h3 className="text-xl font-bold text-white mb-3">اختر المبدعين</h3>
                    <p className="text-sm text-gray-400 leading-relaxed">
                      تصفح آلاف الملفات الشخصية الموثقة لصناع المحتوى والمسوقين المحترفين، واختر من يناسب هويتك وجمهورك بدقة.
                    </p>
                  </div>

                  {/* Step 3 */}
                  <div className="bg-[#101626] border border-gray-800/90 hover:border-emerald-500/40 rounded-3xl p-8 relative transition duration-300 group">
                    <div className="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-black text-xl mb-6 group-hover:scale-110 transition">
                      3
                    </div>
                    <h3 className="text-xl font-bold text-white mb-3">تتبع وضاعف أرباحك</h3>
                    <p className="text-sm text-gray-400 leading-relaxed">
                      راقب المبيعات لحظة بلحظة عبر لوحة تحكم دقيقة وادفع مقابل النتائج الحقيقية فقط، دون أي هدر في الميزانية.
                    </p>
                  </div>
                </div>
              </section>

              {/* ---------------- DUAL AUDIENCES TABS (Brands vs Creators) ---------------- */}
              <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="bg-[#0e1424] border border-gray-800/90 rounded-3xl p-6 sm:p-12">
                  {/* Toggle Tabs */}
                  <div className="flex items-center justify-center mb-10">
                    <div className="bg-gray-900 p-1.5 rounded-2xl border border-gray-800 flex gap-2">
                      <button
                        onClick={() => setAudienceTab('brands')}
                        className={`px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 ${
                          audienceTab === 'brands' 
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-400 text-black shadow-md font-black' 
                            : 'text-gray-400 hover:text-white'
                        }`}
                      >
                        <Building2 className="w-4 h-4" /> للعلامات التجارية
                      </button>
                      <button
                        onClick={() => setAudienceTab('creators')}
                        className={`px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 ${
                          audienceTab === 'creators' 
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-400 text-black shadow-md font-black' 
                            : 'text-gray-400 hover:text-white'
                        }`}
                      >
                        <Users className="w-4 h-4" /> صُنّاع المحتوى، المبدعون، والمسوقون
                      </button>
                    </div>
                  </div>

                  {/* TAB 1: BRANDS CONTENT */}
                  {audienceTab === 'brands' && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                      <div className="space-y-6">
                        <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                          حلول التجارة الإلكترونية
                        </span>
                        <h3 className="text-3xl font-black text-white leading-tight">
                          نمو بلا مخاطرة: ادفع مقابل المبيعات المؤكدة فقط
                        </h3>
                        <p className="text-gray-300 text-sm leading-relaxed">
                          لا تدفع مقابل المشاهدات أو النقرات الوهمية. نظامنا يعتمد على "الدفع مقابل النتائج" (CPA) مما يضمن لك عائداً استثمارياً حقيقياً لكل مبلغ تنفقه.
                        </p>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                          <div className="bg-[#12192c] border border-gray-800 p-4 rounded-xl">
                            <h4 className="font-bold text-white text-sm mb-1 text-emerald-400">تحكم كامل</h4>
                            <p className="text-xs text-gray-400 leading-relaxed">
                              حدد شروطك، سقف ميزانيتك، ونسب العمولة بدقة متناهية دون مفاجآت.
                            </p>
                          </div>
                          <div className="bg-[#12192c] border border-gray-800 p-4 rounded-xl">
                            <h4 className="font-bold text-white text-sm mb-1 text-teal-400">قاعدة بيانات ضخمة</h4>
                            <p className="text-xs text-gray-400 leading-relaxed">
                              وصول مباشر لأكثر من 1000 صانع محتوى نشط ومؤثر في الخليج ومصر.
                            </p>
                          </div>
                          <div className="bg-[#12192c] border border-gray-800 p-4 rounded-xl sm:col-span-2">
                            <h4 className="font-bold text-white text-sm mb-1 text-amber-400">شفافية وموثوقية</h4>
                            <p className="text-xs text-gray-400 leading-relaxed">
                              نحن نضمن صحة البيانات وتدقيق الحسابات لمنع أي عمليات احتيال في التسويق بالعمولة، مع حماية ضد إلغاء الطلبات والمرتجعات.
                            </p>
                          </div>
                        </div>

                        <div>
                          <button
                            onClick={() => { setAuthMode('register'); setAuthRole('BRAND'); setShowAuthModal(true); }}
                            className="bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold px-7 py-3 rounded-xl text-sm transition inline-flex items-center gap-2"
                          >
                            <Building2 className="w-4 h-4" /> سجل كعلامة تجارية
                          </button>
                        </div>
                      </div>

                      {/* Visual Presentation */}
                      <div className="bg-gradient-to-br from-[#121a2e] to-[#0c1220] border border-gray-800 rounded-3xl p-6 sm:p-8 space-y-4">
                        <div className="flex items-center justify-between border-b border-gray-800 pb-3">
                          <div className="flex items-center gap-2">
                            <div className="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
                            <span className="text-xs font-bold text-gray-200">حملة الأداء: متجر العود الملكي</span>
                          </div>
                          <span className="text-[11px] bg-emerald-500/20 text-emerald-300 font-bold px-2 py-0.5 rounded-full">نشطة ومحققة للأرباح</span>
                        </div>

                        <div className="grid grid-cols-2 gap-3 text-xs">
                          <div className="bg-gray-950 p-3 rounded-xl border border-gray-800">
                            <span className="text-gray-400 block mb-1">المبيعات المؤكدة</span>
                            <span className="text-lg font-black text-emerald-400">128,450 ر.س</span>
                          </div>
                          <div className="bg-gray-950 p-3 rounded-xl border border-gray-800">
                            <span className="text-gray-400 block mb-1">العائد على الإنفاق (ROAS)</span>
                            <span className="text-lg font-black text-teal-400">6.8x</span>
                          </div>
                        </div>

                        <div className="bg-gray-950 p-3.5 rounded-xl border border-gray-800 space-y-2 text-xs">
                          <div className="flex items-center justify-between text-gray-300">
                            <span>صناع المحتوى المنضمون</span>
                            <span className="font-bold text-white">42 صانع محتوى</span>
                          </div>
                          <div className="w-full bg-gray-800 h-2 rounded-full overflow-hidden">
                            <div className="bg-gradient-to-r from-emerald-500 to-teal-400 h-full w-[82%]"></div>
                          </div>
                          <span className="text-[11px] text-gray-500 block">تم استهلاك 82% من سقف الميزانية الترويجية</span>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* TAB 2: CREATORS CONTENT */}
                  {audienceTab === 'creators' && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                      <div className="space-y-6">
                        <span className="text-xs uppercase font-extrabold tracking-wider text-teal-400 bg-teal-500/10 px-3 py-1 rounded-full border border-teal-500/20">
                          اقتصاد المبدعين وصناع المحتوى
                        </span>
                        <h3 className="text-3xl font-black text-white leading-tight">
                          حقق دخلاً من تأثيرك... وليس من عدد متابعيك فقط
                        </h3>
                        <p className="text-gray-300 text-sm leading-relaxed">
                          كل حملة هي فرصة جديدة للنمو، وكل نتيجة تحققها تزيد من فرصك وأرباحك داخل المنصة. حوّل شغفك إلى دخل مستدام وموثوق.
                        </p>

                        <div className="space-y-2.5 text-xs text-gray-300">
                          <div className="flex items-center gap-2.5 bg-[#12192c] p-2.5 rounded-xl border border-gray-800/80">
                            <span className="text-base">💰</span>
                            <span className="font-semibold text-white">حملات بعمولات تنافسية ومضمونة التحصيل.</span>
                          </div>
                          <div className="flex items-center gap-2.5 bg-[#12192c] p-2.5 rounded-xl border border-gray-800/80">
                            <span className="text-base">🎯</span>
                            <span className="font-semibold text-white">اختر الحملات المناسبة لجمهورك بحرية تامة.</span>
                          </div>
                          <div className="flex items-center gap-2.5 bg-[#12192c] p-2.5 rounded-xl border border-gray-800/80">
                            <span className="text-base">🔗</span>
                            <span className="font-semibold text-white">روابط تتبع ذكية وأكواد خصم خاصة بك.</span>
                          </div>
                          <div className="flex items-center gap-2.5 bg-[#12192c] p-2.5 rounded-xl border border-gray-800/80">
                            <span className="text-base">📊</span>
                            <span className="font-semibold text-white">لوحة تحكم فورية لمتابعة النقرات والأرباح لحظة بلحظة.</span>
                          </div>
                          <div className="flex items-center gap-2.5 bg-[#12192c] p-2.5 rounded-xl border border-gray-800/80">
                            <span className="text-base">💳</span>
                            <span className="font-semibold text-white">استلام أرباحك مباشرة لحسابك البنكي بعد اعتماد النتائج.</span>
                          </div>
                        </div>

                        <div>
                          <button
                            onClick={() => { setAuthMode('register'); setAuthRole('MARKETER'); setShowAuthModal(true); }}
                            className="bg-teal-400 hover:bg-teal-300 text-black font-extrabold px-7 py-3 rounded-xl text-sm transition inline-flex items-center gap-2"
                          >
                            <Users className="w-4 h-4" /> سجل كصانع محتوى وابدأ الربح
                          </button>
                        </div>
                      </div>

                      {/* Visual Presentation */}
                      <div className="bg-gradient-to-br from-[#121a2e] to-[#0c1220] border border-gray-800 rounded-3xl p-6 sm:p-8 space-y-4">
                        <div className="flex items-center justify-between border-b border-gray-800 pb-3">
                          <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold text-xs">
                              س
                            </div>
                            <div>
                              <div className="text-xs font-bold text-white">سارة القحطاني (@sara_reviews)</div>
                              <div className="text-[10px] text-gray-400">صانعة محتوى معتمدة • تقييم 4.9/5</div>
                            </div>
                          </div>
                          <span className="text-[11px] bg-teal-500/20 text-teal-300 font-bold px-2 py-0.5 rounded-full">شريك متميز</span>
                        </div>

                        <div className="grid grid-cols-2 gap-3 text-xs">
                          <div className="bg-gray-950 p-3 rounded-xl border border-gray-800">
                            <span className="text-gray-400 block mb-1">الرصيد المتاح للسحب</span>
                            <span className="text-lg font-black text-emerald-400">4,250 ر.س</span>
                          </div>
                          <div className="bg-gray-950 p-3 rounded-xl border border-gray-800">
                            <span className="text-gray-400 block mb-1">الطلبات المحققة هذا الشهر</span>
                            <span className="text-lg font-black text-teal-400">94 طلب</span>
                          </div>
                        </div>

                        <div className="bg-gray-950 p-3 rounded-xl border border-gray-800 flex items-center justify-between text-xs">
                          <div className="flex items-center gap-2">
                            <Link2 className="w-4 h-4 text-emerald-400" />
                            <span className="text-gray-300 font-mono">nomowly.com/c/oud_sara</span>
                          </div>
                          <span className="text-[11px] text-emerald-400 font-bold">نشط ويحسب</span>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </section>

              {/* ---------------- ACTIVE CAMPAIGNS SHOWCASE ---------------- */}
              <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex flex-col md:flex-row items-start md:items-end justify-between mb-8 gap-4 border-b border-gray-800 pb-4">
                  <div>
                    <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                      فرص تسويقية نشطة
                    </span>
                    <h2 className="text-2xl sm:text-3xl font-black text-white mt-2">
                      أحدث الحملات النشطة في السوق
                    </h2>
                    <p className="text-sm text-gray-400 mt-1">
                      اختر الحملة المناسبة لجمهورك وابدأ في الترويج وجني الأرباح فوراً
                    </p>
                  </div>
                  <button
                    onClick={() => setPublicPage('campaigns')}
                    className="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1.5 transition"
                  >
                    عرض جميع الحملات في المنصة ({campaigns.length}) <ArrowRight className="w-4 h-4 rotate-180" />
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {campaigns.slice(0, 6).map(c => (
                    <div 
                      key={c.id} 
                      className="bg-[#101626] border border-gray-800 hover:border-emerald-500/50 rounded-2xl p-6 transition duration-300 flex flex-col justify-between group hover:shadow-xl hover:shadow-emerald-950/30"
                    >
                      <div>
                        <div className="flex items-center justify-between gap-2 mb-3">
                          <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-900 text-gray-300 border border-gray-800">
                            {c.category}
                          </span>
                          <span className="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 rounded-full">
                            {c.commissionType === 'PERCENTAGE' ? `${c.commissionValue}% عمولة` : `${c.commissionValue} ${c.currency} لكل بيع`}
                          </span>
                        </div>

                        <div className="text-xs text-gray-400 font-semibold mb-1">{c.brandName}</div>
                        <h3 className="text-lg font-bold text-white mb-2 group-hover:text-emerald-300 transition">{c.title}</h3>
                        <p className="text-xs text-gray-400 leading-relaxed mb-4 line-clamp-2">{c.description}</p>
                      </div>

                      <div className="pt-4 border-t border-gray-800/80">
                        <div className="flex items-center justify-between text-xs text-gray-400 mb-3">
                          <span>الأسواق: {c.targetCountries.join('، ')}</span>
                          <span className="font-semibold text-gray-300">العملة: {c.currency}</span>
                        </div>
                        <button
                          onClick={() => handleApplyToCampaign(c.id)}
                          className="w-full bg-emerald-500/10 hover:bg-emerald-500 hover:text-black border border-emerald-500/30 text-emerald-400 font-black py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 group-hover:bg-emerald-500 group-hover:text-black shadow-md"
                        >
                          <Link2 className="w-3.5 h-3.5" />
                          انضم واحصل على رابطك
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </section>

              {/* ---------------- PARTNER TESTIMONIALS (rihlatsharq.com) ---------------- */}
              <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center max-w-2xl mx-auto mb-12">
                  <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    ثقة ونتائج
                  </span>
                  <h2 className="text-3xl font-black text-white mt-3">قصص نجاح شركائنا</h2>
                  <p className="text-sm text-gray-400 mt-2">ما يقوله أصحاب المتاجر وصناع المحتوى عن تجربتهم في نمولي</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center gap-1 text-amber-400 mb-4">
                        {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400" />)}
                      </div>
                      <p className="text-xs sm:text-sm text-gray-300 leading-relaxed italic mb-6">
                        "استطاعت علامتنا التجارية تحقيق مبيعات قياسية خلال شهر رمضان بفضل شبكة المبدعين في نمولي. النظام دقيق جداً في تتبع التحويلات والدفع مقابل النتائج فقط."
                      </p>
                    </div>
                    <div className="border-t border-gray-800 pt-4">
                      <div className="font-bold text-white text-sm">سارة القحطاني</div>
                      <div className="text-xs text-gray-400 mt-0.5">مؤسسة براند عطور فاخرة • الرياض</div>
                    </div>
                  </div>

                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center gap-1 text-amber-400 mb-4">
                        {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400" />)}
                      </div>
                      <p className="text-xs sm:text-sm text-gray-300 leading-relaxed italic mb-6">
                        "كمؤثر، كنت أعاني من تحصيل حقوقي من الشركات. مع نمولي، كل شيء منظم وموثق، وأرباحي تصلني في موعدها تماماً دون أي تأخير."
                      </p>
                    </div>
                    <div className="border-t border-gray-800 pt-4">
                      <div className="font-bold text-white text-sm">عمر فاروق</div>
                      <div className="text-xs text-gray-400 mt-0.5">صانع محتوى تقني • دبي</div>
                    </div>
                  </div>

                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center gap-1 text-amber-400 mb-4">
                        {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400" />)}
                      </div>
                      <p className="text-xs sm:text-sm text-gray-300 leading-relaxed italic mb-6">
                        "المنصة وفرت علينا عناء التفاوض مع مئات المشاهير. قمنا بإطلاق الحملة في ساعة واحدة والنتائج بدأت بالظهور في نفس اليوم."
                      </p>
                    </div>
                    <div className="border-t border-gray-800 pt-4">
                      <div className="font-bold text-white text-sm">أحمد المنصور</div>
                      <div className="text-xs text-gray-400 mt-0.5">مدير تسويق متجر إلكتروني • القاهرة</div>
                    </div>
                  </div>
                </div>
              </section>

              {/* ---------------- FREQUENTLY ASKED QUESTIONS (FAQ) ---------------- */}
              <section className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-10">
                  <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    إجابات واضحة
                  </span>
                  <h2 className="text-3xl font-black text-white mt-3">الأسئلة الشائعة</h2>
                </div>

                <div className="space-y-3">
                  {[
                    {
                      q: 'هل التسجيل في المنصة مجاني؟',
                      a: 'نعم، التسجيل مجاني تماماً لكل من العلامات التجارية وصناع المحتوى والمسوقين. لا توجد أي رسوم شهرية إجبارية في الباقة الأساسية، وتبدأ الرسوم فقط عند تحقيق مبيعات فعلية ومؤكدة.'
                    },
                    {
                      q: 'كيف يتم تتبع المبيعات والعمولات؟',
                      a: 'نستخدم محرك تتبع ذكي يربط كل نقرة بمعرف فريد (First-Party Click ID). عند قيام العميل بالشراء عبر رابط التتبع أو كود الخصم، يرسل المتجر ويب هوك فوري للمنصة لتوثيق العملية واحتساب عمولة المسوق وفق نظام اللمسة الأخيرة (Last-Touch Attribution).'
                    },
                    {
                      q: 'ما هي شروط الانضمام كصانع محتوى أو مسوق؟',
                      a: 'نرحب بصناع المحتوى على جميع المنصات (تيك توك، إنستغرام، سناب شات، يوتيوب، أو المدونات ومواقع المراجعات) وكذلك مشتري الإعلانات (Media Buyers). الأهم هو قدرتك على تقديم محتوى حقيقي لجمهورك وتحقيق مبيعات مؤكدة.'
                    },
                    {
                      q: 'ما هي العملات وطرق السحب المتاحة للمسوقين؟',
                      a: 'تدعم المنصة الريال السعودي (SAR) كعملة رئيسية، بالإضافة إلى الجنيه المصري (EGP)، والدرهم الإماراتي (AED)، والدينار الكويتي (KWD). يتم تحويل الأرباح مباشرة إلى الحساب البنكي المحلي للمسوق فور انتهاء فترة الضمان.'
                    },
                    {
                      q: 'كيف تحمي المنصة العلامات التجارية من الاحتيال والمرتجعات؟',
                      a: 'تطبق نمولي خوارزميات كشف الاحتيال التي تمنع النقرات الوهمية وتستبعد الطلبات الملغاة أو المرتجعة خلال فترة الاحتجاز (14 إلى 30 يوماً). لا تُصرف أي عمولة إلا بعد التأكد من تسليم الطلب وعدم إرجاعه.'
                    }
                  ].map((item, idx) => (
                    <div 
                      key={idx} 
                      className="bg-[#101626] border border-gray-800 rounded-2xl overflow-hidden transition"
                    >
                      <button
                        onClick={() => setOpenFaqIndex(openFaqIndex === idx ? null : idx)}
                        className="w-full p-5 text-right flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-emerald-400 transition"
                      >
                        <span>{item.q}</span>
                        {openFaqIndex === idx ? (
                          <ChevronUp className="w-5 h-5 text-emerald-400 shrink-0" />
                        ) : (
                          <ChevronDown className="w-5 h-5 text-gray-500 shrink-0" />
                        )}
                      </button>
                      {openFaqIndex === idx && (
                        <div className="px-5 pb-5 pt-1 text-xs sm:text-sm text-gray-400 leading-relaxed border-t border-gray-800/60">
                          {item.a}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </section>

              {/* ---------------- HIGH-CONVERTING BOTTOM CTA BANNER ---------------- */}
              <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div className="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 rounded-3xl p-10 sm:p-14 text-white shadow-2xl shadow-emerald-500/20 relative overflow-hidden">
                  <div className="relative z-10">
                    <h2 className="text-3xl sm:text-4xl font-black mb-4">جاهز لتوسيع آفاق عملك؟</h2>
                    <p className="text-emerald-100 max-w-2xl mx-auto text-sm sm:text-base mb-8 leading-relaxed">
                      انضم إلى مجتمع نمولي اليوم وابدأ في بناء شراكات حقيقية تحقق نتائج ملموسة وعوائد استثنائية.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                      <button
                        onClick={() => { setAuthMode('register'); setAuthRole('MARKETER'); setShowAuthModal(true); }}
                        className="w-full sm:w-auto bg-black text-white hover:bg-gray-900 font-extrabold px-8 py-3.5 rounded-xl shadow-lg transition text-sm flex items-center justify-center gap-2"
                      >
                        <Users className="w-4 h-4 text-emerald-400" /> سجل كصانع محتوى
                      </button>
                      <button
                        onClick={() => { setAuthMode('register'); setAuthRole('BRAND'); setShowAuthModal(true); }}
                        className="w-full sm:w-auto bg-white text-black hover:bg-gray-100 font-black px-8 py-3.5 rounded-xl shadow-lg transition text-sm flex items-center justify-center gap-2"
                      >
                        <Building2 className="w-4 h-4 text-emerald-600" /> سجل كعلامة تجارية
                      </button>
                    </div>
                  </div>
                </div>
              </section>
            </div>
          )}

          {/* ---------------- 2. ABOUT US PAGE (عن المنصة) ---------------- */}
          {publicPage === 'about-us' && (
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-12">
              <div className="text-center space-y-4">
                <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                  عن المنصة
                </span>
                <h1 className="text-3xl sm:text-5xl font-black text-white leading-tight">
                  أكبر Marketplace عربي للتسويق بالأداء
                </h1>
                <p className="text-gray-300 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                  نحن في "نمولي" نؤمن بأن المحتوى العربي يستحق العالمية. بنينا هذه المنصة لنكون الجسر الذي يربط بين طموح الصناع واحترافية الشركات، معتمدين على البيانات والتقنية لتمكين الجميع.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-[#101626] border border-gray-800 rounded-3xl p-8 space-y-3">
                  <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">
                    <TrendingUp className="w-6 h-6" />
                  </div>
                  <h2 className="text-xl font-black text-white">رؤيتنا</h2>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    أن نصبح المنصة الأولى والأكثر موثوقية في الوطن العربي التي تقود اقتصاد المبدعين، حيث يجد كل صانع محتوى فرصة حقيقية للنمو، وتجد كل علامة تجارية صوتها الأكثر تأثيراً في السوق.
                  </p>
                </div>

                <div className="bg-[#101626] border border-gray-800 rounded-3xl p-8 space-y-3">
                  <div className="w-12 h-12 rounded-2xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold">
                    <Award className="w-6 h-6" />
                  </div>
                  <h2 className="text-xl font-black text-white">رسالتنا</h2>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    تمكين منظومة التسويق عبر المؤثرين من خلال توفير أدوات ذكية، وبيانات دقيقة، وبيئة تعاونية تضمن الشفافية والنتائج الملموسة للطرفين.
                  </p>
                </div>
              </div>

              {/* Core Values */}
              <div className="bg-[#101626] border border-gray-800 rounded-3xl p-8 space-y-6">
                <div>
                  <h3 className="text-xl font-black text-white mb-2">القيم التي نؤمن بها</h3>
                  <p className="text-xs text-gray-400">نعمل وفق معايير صارمة تضمن استدامة النجاح لمجتمعنا</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs sm:text-sm">
                  <div className="space-y-2">
                    <span className="font-bold text-emerald-400 text-sm block">الشفافية المطلقة</span>
                    <p className="text-gray-400 leading-relaxed">
                      نؤمن بأن الصدق هو أساس كل علاقة ناجحة. نوفر تقارير واضحة وبيانات دقيقة لكل حملة دون تجميل.
                    </p>
                  </div>
                  <div className="space-y-2">
                    <span className="font-bold text-teal-400 text-sm block">الابتكار المستمر</span>
                    <p className="text-gray-400 leading-relaxed">
                      السوق يتغير كل ثانية، وتقنياتنا تتطور معه لنبقى دائماً في المقدمة ونوفر لك أفضل أدوات التتبع.
                    </p>
                  </div>
                  <div className="space-y-2">
                    <span className="font-bold text-amber-400 text-sm block">المجتمع أولاً</span>
                    <p className="text-gray-400 leading-relaxed">
                      نحن لا ندير منصة، بل نبني مجتمعاً. دعمنا مستمر لصناع المحتوى لتمكينهم من تحويل شغفهم إلى وظيفة.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ---------------- 3. HOW IT WORKS PAGE (كيف تعمل) ---------------- */}
          {publicPage === 'how-it-works' && (
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-12">
              <div className="text-center space-y-3">
                <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                  دليل خطوة بخطوة 🚀
                </span>
                <h1 className="text-3xl sm:text-5xl font-black text-white">كيف تعمل منصة نمولي؟</h1>
                <p className="text-gray-400 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                  حلقة نمو متكاملة تربط العلامات التجارية بصناع المحتوى والمسوقين وفق نموذج التسويق بالأداء.. شفافية تامة، وأرباح حقيقية مقابل النتائج فقط.
                </p>
              </div>

              {/* Journey Switcher */}
              <div className="flex justify-center">
                <div className="bg-gray-900 p-1.5 rounded-2xl border border-gray-800 flex gap-2">
                  <button
                    onClick={() => setJourneyTab('brands')}
                    className={`px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 ${
                      journeyTab === 'brands' ? 'bg-emerald-500 text-black font-black' : 'text-gray-400 hover:text-white'
                    }`}
                  >
                    <Building2 className="w-4 h-4" /> للعلامات التجارية والمتاجر
                  </button>
                  <button
                    onClick={() => setJourneyTab('creators')}
                    className={`px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 ${
                      journeyTab === 'creators' ? 'bg-teal-400 text-black font-black' : 'text-gray-400 hover:text-white'
                    }`}
                  >
                    <Users className="w-4 h-4" /> لصناع المحتوى والمبدعين
                  </button>
                </div>
              </div>

              {/* BRAND JOURNEY */}
              {journeyTab === 'brands' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 font-black text-sm flex items-center justify-center">1</span>
                    <h3 className="font-bold text-white text-base">إنشاء وتحديد الحملة</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      حدد أهدافك التسويقية، المنتجات المشمولة، نسبة العمولة أو المبلغ الثابت لكل بيعة/إجراء، مع وضع الشروط المناسبة لنشاطك التجاري.
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 font-black text-sm flex items-center justify-center">2</span>
                    <h3 className="font-bold text-white text-base">المراجعة والتفعيل الفوري</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      يقوم فريق نمولي المتخصص بمراجعة تفاصيل الحملة وضمان ملاءمتها وجاذبيتها، لتفعيلها في السوق بأسرع وقت مع أفضل ممارسات التحويل.
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 font-black text-sm flex items-center justify-center">3</span>
                    <h3 className="font-bold text-white text-base">انضمام نخبة المبدعين والمسوقين</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      تتاح حملتك أمام آلاف صناع المحتوى الموثقين وخبراء الترويج (Media Buyers) الأكثر ملاءمة لطبيعة جمهورك ومنتجاتك.
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 font-black text-sm flex items-center justify-center">4</span>
                    <h3 className="font-bold text-white text-base">تتبع دقيق والدفع مقابل النتائج</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      لوحة تحكم ذكية وشاملة ترصد الطلبات والتحويلات لحظياً. لا تدفع أي عمولة إلا عندما تتم عملية بيع ناجحة ومثبتة بالفعل.
                    </p>
                  </div>
                </div>
              )}

              {/* CREATOR JOURNEY */}
              {journeyTab === 'creators' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 font-black text-sm flex items-center justify-center">1</span>
                    <h3 className="font-bold text-white text-base">التسجيل وتوثيق الحساب</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      أنشئ حسابك مجاناً واربط قنواتك على منصات التواصل (تيك توك، سناب شات، إنستغرام، يوتيوب، أو موقعك) في خطوات سريعة.
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 font-black text-sm flex items-center justify-center">2</span>
                    <h3 className="font-bold text-white text-base">تصفح واختيار الحملات</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      اختر ما يلائم تخصصك وجمهورك من بين مئات الحملات المعتمدة (أزياء، إلكترونيات، جمال، عطور، وتطبيقات رائدة).
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 font-black text-sm flex items-center justify-center">3</span>
                    <h3 className="font-bold text-white text-base">الحصول على الرابط والكود</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      أنشئ روابط تتبع خاصة بك وأكواد خصم حصرية باسمك لتقديمها لمتابعيك، لتتبع كل زيارة وطلب شراء يُجرى من خلالك.
                    </p>
                  </div>
                  <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 sm:p-7 space-y-3">
                    <span className="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 font-black text-sm flex items-center justify-center">4</span>
                    <h3 className="font-bold text-white text-base">تحقيق الأرباح وسحب المستحقات</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                      شاهد رصيدك ينمو مع كل طلب، واطلب سحب أرباحك فور تأكيد العمليات بحرية تامة وبطرق دفع محلية ودولية موثوقة.
                    </p>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* ---------------- 4. CAMPAIGNS MARKETPLACE (لوحة الحملات) ---------------- */}
          {publicPage === 'campaigns' && (
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
              <div className="border-b border-gray-800 pb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                  <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    لوحة الحملات
                  </span>
                  <h1 className="text-3xl font-black text-white mt-2">استكشف حملات الأداء في السوق</h1>
                  <p className="text-sm text-gray-400 mt-1">
                    تصفح جميع الحملات المعتمدة، وقدم طلبك بنقرة واحدة للحصول على رابط التتبع الخاص بك
                  </p>
                </div>
                {session?.role === 'BRAND' && (
                  <button
                    onClick={() => setShowCreateCampaignModal(true)}
                    className="bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5"
                  >
                    <PlusCircle className="w-4 h-4" /> إطلاق حملة جديدة
                  </button>
                )}
              </div>

              {/* Filters Bar */}
              <div className="bg-[#101626] border border-gray-800 rounded-2xl p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-400 mb-1.5">البحث بالاسم أو البراند</label>
                  <div className="relative">
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={e => setSearchQuery(e.target.value)}
                      placeholder="ابحث عن حملة أو متجر..."
                      className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2.5 text-xs text-white pr-9 focus:outline-none focus:border-emerald-500"
                    />
                    <Search className="w-4 h-4 text-gray-500 absolute top-3 right-3" />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-400 mb-1.5">التصنيف</label>
                  <select
                    value={selectedCategory}
                    onChange={e => setSelectedCategory(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500"
                  >
                    <option value="ALL">جميع التصنيفات</option>
                    <option value="عطور وتجميل">عطور وتجميل</option>
                    <option value="أزياء وموضة">أزياء وموضة</option>
                    <option value="إلكترونيات">إلكترونيات</option>
                    <option value="عناية وصحة">عناية وصحة</option>
                    <option value="أغذية ومشروبات">أغذية ومشروبات</option>
                    <option value="أطفال وألعاب">أطفال وألعاب</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-400 mb-1.5">السوق المستهدف</label>
                  <select
                    value={selectedCountry}
                    onChange={e => setSelectedCountry(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500"
                  >
                    <option value="ALL">جميع الأسواق العربية</option>
                    <option value="SA">المملكة العربية السعودية 🇸🇦</option>
                    <option value="EG">جمهورية مصر العربية 🇪🇬</option>
                    <option value="AE">دولة الإمارات 🇦🇪</option>
                    <option value="KW">دولة الكويت 🇰🇼</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-400 mb-1.5">العملة</label>
                  <select
                    value={selectedCurrency}
                    onChange={e => setSelectedCurrency(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500"
                  >
                    <option value="ALL">جميع العملات</option>
                    {SUPPORTED_CURRENCIES.map(curr => (
                      <option key={curr.code} value={curr.code}>{curr.code} ({curr.name})</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Campaign Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {campaigns.length > 0 ? (
                  campaigns.map(c => (
                    <div 
                      key={c.id} 
                      className="bg-[#101626] border border-gray-800 hover:border-emerald-500/50 rounded-2xl p-6 transition duration-300 flex flex-col justify-between group hover:shadow-xl hover:shadow-emerald-950/20"
                    >
                      <div>
                        <div className="flex items-center justify-between gap-2 mb-3">
                          <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-900 text-gray-300 border border-gray-800">
                            {c.category}
                          </span>
                          <span className="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 rounded-full">
                            {c.commissionType === 'PERCENTAGE' ? `${c.commissionValue}% عمولة` : `${c.commissionValue} ${c.currency} ثابت`}
                          </span>
                        </div>

                        <div className="text-xs text-gray-400 font-semibold mb-1">{c.brandName}</div>
                        <h3 className="text-lg font-bold text-white mb-2 group-hover:text-emerald-300 transition">{c.title}</h3>
                        <p className="text-xs text-gray-400 leading-relaxed mb-4">{c.description}</p>
                      </div>

                      <div className="pt-4 border-t border-gray-800/80">
                        <div className="flex items-center justify-between text-xs text-gray-400 mb-3">
                          <span>الأسواق: {c.targetCountries.join('، ')}</span>
                          <span className="font-semibold text-gray-300">العملة: {c.currency}</span>
                        </div>
                        <button
                          onClick={() => handleApplyToCampaign(c.id)}
                          className="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/20"
                        >
                          <Link2 className="w-3.5 h-3.5" />
                          انضم واحصل على رابط التتبع
                        </button>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="col-span-full py-16 text-center bg-[#101626] border border-gray-800 rounded-2xl">
                    <ShoppingBag className="w-10 h-10 text-gray-600 mx-auto mb-3" />
                    <h3 className="text-base font-bold text-gray-300">لا توجد حملات تطابق معايير البحث الحالية</h3>
                    <p className="text-xs text-gray-500 mt-1">جرب إزالة بعض الفلاتر لعرض مزيد من الفرص.</p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ---------------- 5. PRICING & PLANS (الأسعار والاشتراكات) ---------------- */}
          {publicPage === 'prices' && (
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-12">
              <div className="text-center space-y-3">
                <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                  شفافية تامة، نمو حقيقي
                </span>
                <h1 className="text-3xl sm:text-5xl font-black text-white">ادفع فقط مقابل النتائج التي تراها</h1>
                <p className="text-gray-300 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                  في نمولي، نؤمن بأن شراكتك مع المبدعين يجب أن تكون استثماراً وليس مجرد تكلفة. التسجيل مجاني تماماً، ولا ندفع إلا عندما تنجح حملتك.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {/* Plan 1: Free Starter */}
                <div className="bg-[#101626] border border-gray-800 rounded-3xl p-8 flex flex-col justify-between">
                  <div>
                    <div className="inline-flex p-3 rounded-2xl bg-gray-800/60 text-emerald-400 mb-4">
                      <Sparkles className="w-6 h-6" />
                    </div>
                    <h3 className="text-2xl font-black text-white">البداية المجانية</h3>
                    <p className="text-xs text-gray-400 mt-1 mb-6">
                      استكشف المنصة وابدأ ببناء علاقاتك دون أي تكاليف مسبقة.
                    </p>
                    <div className="text-3xl font-black text-white mb-6">
                      0 <span className="text-sm font-normal text-gray-400">ريال / شهرياً</span>
                    </div>

                    <ul className="space-y-3 text-xs text-gray-300 mb-8">
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> إنشاء ملف شخصي احترافي</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> تصفح قاعدة بيانات المبدعين المعتمدين</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> تحليلات أساسية للحملات والنقرات</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> دعم فني عبر البريد الإلكتروني</li>
                    </ul>
                  </div>

                  <button
                    onClick={() => { setAuthMode('register'); setShowAuthModal(true); }}
                    className="w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl text-xs transition"
                  >
                    سجل مجاناً
                  </button>
                </div>

                {/* Plan 2: Growth Plan (Most Popular on rihlatsharq.com) */}
                <div className="bg-gradient-to-b from-[#141e34] to-[#0f172a] border-2 border-emerald-500/50 rounded-3xl p-8 flex flex-col justify-between relative shadow-2xl shadow-emerald-500/10">
                  <div className="absolute -top-3.5 right-8 bg-gradient-to-r from-emerald-500 to-teal-400 text-black text-[11px] font-black px-3.5 py-1 rounded-full uppercase tracking-wider">
                    الأكثر طلباً
                  </div>

                  <div>
                    <div className="inline-flex p-3 rounded-2xl bg-emerald-500/20 text-emerald-400 mb-4">
                      <TrendingUp className="w-6 h-6" />
                    </div>
                    <h3 className="text-2xl font-black text-white">خطة النمو</h3>
                    <p className="text-xs text-gray-300 mt-1 mb-6">
                      للشركات الطموحة التي تريد تسريع وتيرة نموها عبر المحتوى دون مخاطرة.
                    </p>
                    <div className="text-3xl font-black text-emerald-400 mb-1">
                      5% <span className="text-sm font-semibold text-gray-300">عمولة على المبيعات المحققة</span>
                    </div>
                    <div className="text-xs text-emerald-300 font-bold mb-6">
                      لا يوجد رسوم اشتراك ثابتة مطلقا
                    </div>

                    <ul className="space-y-3 text-xs text-gray-300 mb-8">
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> إطلاق عدد غير محدود من حملات الأداء</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> وصول كامل لنخبة صناع المحتوى المؤثرين</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> تتبع متقدم باللمسة الأخيرة وروابط ذكية</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> حماية آلية ضد المرتجعات والطلبات الوهمية</li>
                      <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> مدير حساب إعلاني وتوجيه نمو</li>
                    </ul>
                  </div>

                  <button
                    onClick={() => { setAuthMode('register'); setAuthRole('BRAND'); setShowAuthModal(true); }}
                    className="w-full bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-black font-black py-3.5 rounded-xl text-xs transition shadow-lg shadow-emerald-500/25"
                  >
                    ابدأ خطة النمو الآن
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* ---------------- 6. GROWTH GUIDE (دليل النمو) ---------------- */}
          {publicPage === 'growth' && (
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-10">
              <div className="text-center space-y-3">
                <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                  دليل النمو
                </span>
                <h1 className="text-3xl sm:text-5xl font-black text-white">استراتيجيات التسويق بالأداء في العالم العربي</h1>
                <p className="text-gray-400 text-sm max-w-xl mx-auto">
                  أفضل الممارسات والنصائح العملية لزيادة المبيعات وتحقيق أقصى عائد استثماري للمتاجر والمبدعين
                </p>
              </div>

              <div className="space-y-6">
                <article className="bg-[#101626] border border-gray-800 rounded-3xl p-7 space-y-3">
                  <div className="flex items-center gap-2 text-xs text-emerald-400 font-bold">
                    <span>للعلامات التجارية</span> • <span>قراءة: 4 دقائق</span>
                  </div>
                  <h2 className="text-xl font-bold text-white">كيف تحدد نسبة العمولة المثالية لحملتك التسويقية؟</h2>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    تحديد العمولة المناسبة (CPS) هو التوازن بين جذب أفضل المبدعين للمشاركة وبين الحفاظ على هامش ربحك الصافي بعد خصم تكاليف المنتج والشحن. استعرض في هذا الدليل المعادلة الحسابية لتسعير عمولة الحملة بدقة.
                  </p>
                </article>

                <article className="bg-[#101626] border border-gray-800 rounded-3xl p-7 space-y-3">
                  <div className="flex items-center gap-2 text-xs text-teal-400 font-bold">
                    <span>لصناع المحتوى</span> • <span>قراءة: 5 دقائق</span>
                  </div>
                  <h2 className="text-xl font-bold text-white">أسرار كتابة مراجعات المنتجات (Reviews) التي تحول المتابعين إلى مشترين</h2>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    المتابعون لا يشترون من الإعلانات المصطنعة؛ بل يشترون من التجارب الصادقة. اكتشف كيف تعرض المنتج بطريقة طبيعية مع وضع كود الخصم ورابط التتبع في البايو لتعظيم العمولات.
                  </p>
                </article>

                <article className="bg-[#101626] border border-gray-800 rounded-3xl p-7 space-y-3">
                  <div className="flex items-center gap-2 text-xs text-amber-400 font-bold">
                    <span>مشترو الإعلانات (Media Buyers)</span> • <span>قراءة: 6 دقائق</span>
                  </div>
                  <h2 className="text-xl font-bold text-white">استراتيجيات قياس الـ ROAS والتتبع المتعدد لحملات الأداء الكبرى</h2>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    كيف تستفيد من تكامل الويب هوك ومعرفات الـ Sub-IDs لتتبع مصادر الزيارات وتوجيه الميزانيات للقنوات الأكثر ربحية في السعودية والخليج.
                  </p>
                </article>
              </div>
            </div>
          )}

          {/* ---------------- 7. CONTACT US (تواصل معنا) ---------------- */}
          {publicPage === 'contact-us' && (
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-12">
              <div className="text-center space-y-3">
                <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1 rounded-full border border-emerald-500/20">
                  تواصل معنا
                </span>
                <h1 className="text-3xl sm:text-5xl font-black text-white">كيف يمكننا مساعدتك اليوم؟</h1>
                <p className="text-gray-400 text-sm sm:text-base max-w-xl mx-auto">
                  سواء كنت منشئ محتوى طموحاً أو علامة تجارية تبحث عن التميز، فريق "نمولي" جاهز للإجابة على جميع استفساراتك وتقديم الدعم اللازم.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                {/* Contact Form */}
                <div className="bg-[#101626] border border-gray-800 rounded-3xl p-6 sm:p-8">
                  <h3 className="text-lg font-bold text-white mb-4">أرسل استفسارك</h3>
                  <form onSubmit={handleContactSubmit} className="space-y-4 text-xs">
                    <div>
                      <label className="block text-gray-400 mb-1 font-semibold">الاسم</label>
                      <input 
                        type="text" 
                        required 
                        value={contactName}
                        onChange={e => setContactName(e.target.value)}
                        placeholder="اسمك الكريم" 
                        className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-emerald-500" 
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 mb-1 font-semibold">البريد الإلكتروني</label>
                      <input 
                        type="email" 
                        required 
                        value={contactEmail}
                        onChange={e => setContactEmail(e.target.value)}
                        placeholder="email@example.com" 
                        className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-emerald-500" 
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 mb-1 font-semibold">الموضوع</label>
                      <input 
                        type="text" 
                        required 
                        value={contactSubject}
                        onChange={e => setContactSubject(e.target.value)}
                        placeholder="استفسار شراكة / إطلاق حملة..." 
                        className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-emerald-500" 
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 mb-1 font-semibold">الرسالة</label>
                      <textarea 
                        rows={4} 
                        required 
                        value={contactMessage}
                        onChange={e => setContactMessage(e.target.value)}
                        placeholder="تفاصيل رسالتك..." 
                        className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-emerald-500"
                      ></textarea>
                    </div>
                    <button 
                      type="submit" 
                      className="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold py-3 rounded-xl text-xs transition shadow-md shadow-emerald-500/20"
                    >
                      إرسال الرسالة
                    </button>
                  </form>
                </div>

                {/* Direct Contact Info */}
                <div className="space-y-6">
                  <div className="bg-[#101626] border border-gray-800 rounded-3xl p-6 sm:p-8 space-y-4">
                    <h3 className="text-lg font-bold text-white mb-2">معلومات التواصل</h3>
                    
                    <div className="flex items-center gap-3 p-3 bg-gray-950 rounded-2xl border border-gray-800">
                      <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">
                        <Mail className="w-5 h-5" />
                      </div>
                      <div>
                        <div className="text-xs text-gray-400">البريد الإلكتروني المباشر</div>
                        <div className="text-sm font-bold text-white font-mono">hello@namoly.com</div>
                      </div>
                    </div>

                    <div className="flex items-center gap-3 p-3 bg-gray-950 rounded-2xl border border-gray-800">
                      <div className="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold">
                        <PhoneCall className="w-5 h-5" />
                      </div>
                      <div>
                        <div className="text-xs text-gray-400">الهاتف وواتساب الدعم</div>
                        <div className="text-sm font-bold text-white font-mono" dir="ltr">+20 114 437 6519</div>
                      </div>
                    </div>
                  </div>

                  <div className="bg-[#101626] border border-gray-800 rounded-3xl p-6 sm:p-8">
                    <h4 className="text-sm font-bold text-white mb-3">تابعنا على منصات التواصل</h4>
                    <div className="flex flex-wrap gap-2 text-xs">
                      {['X (Twitter)', 'Facebook', 'YouTube', 'TikTok', 'Snapchat'].map(network => (
                        <span key={network} className="bg-gray-900 border border-gray-800 text-gray-300 px-3 py-1.5 rounded-xl font-medium">
                          {network}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ---------------- 8. TERMS & PRIVACY ---------------- */}
          {(publicPage === 'terms' || publicPage === 'privacy') && (
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-6">
              <h1 className="text-3xl font-black text-white">
                {publicPage === 'terms' ? 'شروط الاستخدام' : 'سياسة الخصوصية والأمان'}
              </h1>
              <div className="bg-[#101626] border border-gray-800 rounded-3xl p-8 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed">
                <p>تلتزم منصة نمولي (Nomowly) بتوفير بيئة تسويق بالأداء آمنة وشفافة تضمن حقوق العلامات التجارية وصناع المحتوى والمسوقين.</p>
                <p>تخضع جميع المعاملات المحاسبية لنظام القيد المزدوج، وتُصرف العمولات بعد اكتمال فترة التحقق من تسليم الطلبات وانقضاء مدة الاسترجاع المحددة في شروط كل حملة.</p>
                <p>يحظر منعاً باتاً استخدام الزيارات الوهمية أو برامج البوتات أو أساليب الاحتيال في النقرات، ويحق للمنصة تجميد الحسابات المخالفة وإلغاء العمولات غير المشروعة فوراً.</p>
              </div>
            </div>
          )}
        </main>
      )}

      {/* =========================================================================
          VIEW B: AUTHENTICATED DASHBOARDS (Brand, Marketer, & Super Admin)
         ========================================================================= */}
      {currentView === 'dashboard' && session && (
        <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
          {/* Dashboard Header & Role Switcher */}
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-6 mb-8">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-xs uppercase font-extrabold tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full">
                  {session.role === 'BRAND' ? 'لوحة تحكم العلامة التجارية' : session.role === 'MARKETER' ? 'لوحة تحكم صانع المحتوى والمسوق' : 'لوحة تحكم المشرف العام'}
                </span>
                <span className="text-xs text-gray-400">حساب: {session.companyName || session.fullName || session.email}</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black text-white mt-1">مركز إدارة الأداء والنمو</h1>
            </div>

            <div className="flex items-center gap-2">
              {session.role === 'BRAND' && (
                <button
                  onClick={() => setShowCreateCampaignModal(true)}
                  className="bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-black px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-emerald-500/20"
                >
                  <PlusCircle className="w-4 h-4" /> إطلاق حملة جديدة
                </button>
              )}
            </div>
          </div>

          {/* Role Navigation Tabs */}
          <div className="flex items-center gap-2 border-b border-gray-800 mb-8 overflow-x-auto pb-2">
            {[
              { id: 'overview', label: 'ملخص الأداء', icon: BarChart3 },
              { id: 'campaigns', label: session.role === 'BRAND' ? 'حملاتي وميزانياتي' : 'حملاتي وروابط التتبع', icon: Layers },
              { id: 'wallet', label: 'المحفظة والسحوبات', icon: Coins },
              ...(session.role === 'SUPER_ADMIN' ? [{ id: 'admin', label: 'إدارة المشرف والتدقيق', icon: ShieldCheck }] : []),
            ].map(tab => {
              const Icon = tab.icon;
              const isActive = dashboardTab === tab.id;
              return (
                <button
                  key={tab.id}
                  onClick={() => setDashboardTab(tab.id as any)}
                  className={`flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap ${
                    isActive 
                      ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' 
                      : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span>{tab.label}</span>
                </button>
              );
            })}
          </div>

          {/* TAB 1: OVERVIEW */}
          {dashboardTab === 'overview' && (
            <div className="space-y-8">
              {/* Stat Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-5">
                  <div className="text-xs text-gray-400 font-semibold">
                    {session.role === 'BRAND' ? 'رصيد الضمان المتاح' : 'الأرباح المتاحة للسحب'}
                  </div>
                  <div className="text-2xl font-black text-emerald-400 mt-1">
                    {wallet ? `${wallet.availableBalance} ${wallet.currency}` : '2,450.00 SAR'}
                  </div>
                  <div className="text-[11px] text-gray-500 mt-1">موثق في دفتر الأستاذ المالي</div>
                </div>

                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-5">
                  <div className="text-xs text-gray-400 font-semibold">
                    {session.role === 'BRAND' ? 'الميزانيات المحجوزة للحملات' : 'الأرباح قيد الفحص (Pending)'}
                  </div>
                  <div className="text-2xl font-black text-teal-400 mt-1">
                    {wallet ? (session.role === 'BRAND' ? `${wallet.reservedBalance} SAR` : `${wallet.pendingBalance} SAR`) : '1,820.00 SAR'}
                  </div>
                  <div className="text-[11px] text-gray-500 mt-1">
                    {session.role === 'BRAND' ? 'محجوز للحملات النشطة' : 'تتحول للسحب بعد انتهاء مدة المرتجعات'}
                  </div>
                </div>

                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-5">
                  <div className="text-xs text-gray-400 font-semibold">
                    {session.role === 'BRAND' ? 'الحملات النشطة' : 'الحملات المنضم إليها'}
                  </div>
                  <div className="text-2xl font-black text-white mt-1">
                    {session.role === 'BRAND' ? campaigns.length : (myApplications.length || 1)}
                  </div>
                  <div className="text-[11px] text-gray-500 mt-1">متاحة ومفعلة للتحويلات</div>
                </div>

                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-5">
                  <div className="text-xs text-gray-400 font-semibold">حالة الحساب والتوثيق</div>
                  <div className="text-lg font-black text-emerald-400 mt-1 flex items-center gap-1.5">
                    <CheckCircle2 className="w-4 h-4" /> موثق ونشط
                  </div>
                  <div className="text-[11px] text-gray-500 mt-1">جاهز للمبيعات والتحويل البنكي</div>
                </div>
              </div>

              {/* Quick Actions */}
              <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6">
                <h2 className="text-base font-bold text-white mb-4">إجراءات سريعة</h2>
                <div className="flex flex-wrap gap-3">
                  {session.role === 'BRAND' && (
                    <>
                      <button
                        onClick={() => setShowCreateCampaignModal(true)}
                        className="bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-black px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-emerald-500/20"
                      >
                        <PlusCircle className="w-4 h-4" /> إنشاء حملة أداء جديدة
                      </button>
                      <button
                        onClick={() => setDashboardTab('wallet')}
                        className="bg-gray-800 hover:bg-gray-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5"
                      >
                        <Coins className="w-4 h-4 text-emerald-400" /> شحن رصيد الضمان
                      </button>
                    </>
                  )}
                  {session.role === 'MARKETER' && (
                    <>
                      <button
                        onClick={() => { setCurrentView('public'); setPublicPage('campaigns'); }}
                        className="bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-black px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-emerald-500/20"
                      >
                        <Search className="w-4 h-4" /> استكشاف حملات جديدة للانضمام
                      </button>
                      <button
                        onClick={() => setDashboardTab('wallet')}
                        className="bg-gray-800 hover:bg-gray-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5"
                      >
                        <CreditCard className="w-4 h-4 text-emerald-400" /> طلب سحب أرباح مالي
                      </button>
                    </>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* TAB 2: CAMPAIGNS & TRACKING */}
          {dashboardTab === 'campaigns' && (
            <div className="space-y-6">
              {session.role === 'BRAND' ? (
                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6">
                  <div className="flex items-center justify-between border-b border-gray-800 pb-4 mb-4">
                    <h2 className="text-base font-bold text-white">حملات متجرك النشطة</h2>
                    <button
                      onClick={() => setShowCreateCampaignModal(true)}
                      className="bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-extrabold px-3.5 py-1.5 rounded-lg transition"
                    >
                      + حملة جديدة
                    </button>
                  </div>

                  <div className="space-y-3">
                    {campaigns.map(c => (
                      <div key={c.id} className="bg-gray-950 border border-gray-800/80 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-white text-sm">{c.title}</span>
                            <span className="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">نشطة</span>
                          </div>
                          <p className="text-xs text-gray-400 mt-1">
                            العمولة: {c.commissionType === 'PERCENTAGE' ? `${c.commissionValue}%` : `${c.commissionValue} ${c.currency}`} | الميزانية: {c.budgetCap} {c.currency}
                          </p>
                        </div>
                        <div className="flex items-center gap-2">
                          <button className="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg">إدارة الشروط</button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ) : (
                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6">
                  <div className="flex items-center justify-between border-b border-gray-800 pb-4 mb-4">
                    <h2 className="text-base font-bold text-white">حملاتي وروابط التتبع المعتمدة</h2>
                    <button
                      onClick={() => { setCurrentView('public'); setPublicPage('campaigns'); }}
                      className="text-xs text-emerald-400 hover:underline font-bold"
                    >
                      + تصفح حملات إضافية
                    </button>
                  </div>

                  <div className="space-y-4">
                    {(myApplications.length > 0 ? myApplications : [
                      {
                        id: 'app-perf-001',
                        campaignTitle: 'حملة العطور الشرقية والبخور الفاخر',
                        brandName: 'دار العود الملكي',
                        commission: '15%',
                        status: 'APPROVED',
                        shortCode: 'oud99',
                        clicks: 342,
                        conversions: 18,
                        earned: '1,420 SAR'
                      }
                    ]).map((app: any) => {
                      const linkUrl = `https://track.nomowly.com/c/${app.shortCode || 'oud99'}`;
                      return (
                        <div key={app.id} className="bg-gray-950 border border-gray-800 rounded-2xl p-5 space-y-4">
                          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                              <h3 className="font-bold text-white text-base">{app.campaignTitle}</h3>
                              <p className="text-xs text-gray-400">العلامة التجارية: {app.brandName} • العمولة: {app.commission}</p>
                            </div>
                            <span className="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-full font-bold self-start">
                              معتمد ونشط
                            </span>
                          </div>

                          <div className="bg-gray-900 border border-gray-800 p-3.5 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                              <span className="text-[11px] text-gray-400 block mb-0.5">رابط التتبع الحصري (ضعه في البايو أو الإعلانات):</span>
                              <div className="font-mono text-xs text-emerald-300 select-all break-all">
                                {linkUrl}
                              </div>
                            </div>
                            <button
                              onClick={() => copyToClipboard(linkUrl, app.id)}
                              className="bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-black px-4 py-2 rounded-lg transition shrink-0 flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/20"
                            >
                              {copiedCode === app.id ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                              {copiedCode === app.id ? 'تم النسخ!' : 'نسخ الرابط'}
                            </button>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* TAB 3: WALLET & FINANCES */}
          {dashboardTab === 'wallet' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6">
                  <h2 className="text-base font-bold text-white mb-4 flex items-center gap-2">
                    <Coins className="w-5 h-5 text-emerald-400" />
                    المحفظة المالية
                  </h2>
                  <div className="space-y-4 text-xs">
                    <div className="flex items-center justify-between p-3.5 bg-gray-950 rounded-xl border border-gray-800">
                      <span className="text-gray-400">الرصيد المتاح للسحب</span>
                      <span className="font-bold text-emerald-400 text-base">{wallet?.availableBalance || '2,450.00'} SAR</span>
                    </div>
                    <div className="flex items-center justify-between p-3.5 bg-gray-950 rounded-xl border border-gray-800">
                      <span className="text-gray-400">الرصيد المعلق (فترة المرتجعات)</span>
                      <span className="font-bold text-teal-400 text-base">{wallet?.pendingBalance || '1,820.00'} SAR</span>
                    </div>
                    <div className="flex items-center justify-between p-3.5 bg-gray-950 rounded-xl border border-gray-800">
                      <span className="text-gray-400">إجمالي السحوبات السابقة المعتمدة</span>
                      <span className="font-bold text-gray-300 text-base">{wallet?.withdrawnTotal || '6,300.00'} SAR</span>
                    </div>
                  </div>
                </div>

                <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6">
                  {session.role === 'BRAND' ? (
                    <div>
                      <h2 className="text-base font-bold text-white mb-2">شحن رصيد الضمان المالي</h2>
                      <p className="text-xs text-gray-400 mb-4">يتم إيداع المبلغ لحساب الضمان وإثباته كقيد مزدوج في دفتر الأستاذ</p>
                      <div className="space-y-3">
                        <div>
                          <label className="block text-xs font-semibold text-gray-400 mb-1">المبلغ المراد شحنه (ر.س)</label>
                          <input
                            type="number"
                            value={brandDepositAmount}
                            onChange={e => setBrandDepositAmount(e.target.value)}
                            className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2 text-xs text-white"
                          />
                        </div>
                        <button
                          onClick={handleBrandDeposit}
                          disabled={loading}
                          className="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-black py-2.5 rounded-xl text-xs transition"
                        >
                          تأكيد شحن الضمان (إثبات محاسبي)
                        </button>
                      </div>
                    </div>
                  ) : (
                    <form onSubmit={handleWithdrawalRequest} className="space-y-3">
                      <h2 className="text-base font-bold text-white mb-1">طلب سحب أرباح مالي</h2>
                      <p className="text-xs text-gray-400 mb-3">الحد الأدنى للسحب 150 ريال سعودي أو ما يعادله محلياً</p>
                      <div>
                        <label className="block text-xs font-semibold text-gray-400 mb-1">المبلغ المطلوب سحبه (ر.س)</label>
                        <input
                          type="number"
                          value={withdrawAmount}
                          onChange={e => setWithdrawAmount(e.target.value)}
                          required
                          className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2 text-xs text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold text-gray-400 mb-1">رقم الآيبان البنكي (IBAN)</label>
                        <input
                          type="text"
                          value={withdrawIban}
                          onChange={e => setWithdrawIban(e.target.value)}
                          required
                          className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold text-gray-400 mb-1">اسم المستفيد مطابقاً للبنك</label>
                        <input
                          type="text"
                          value={withdrawName}
                          onChange={e => setWithdrawName(e.target.value)}
                          required
                          className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3.5 py-2 text-xs text-white"
                        />
                      </div>
                      <button
                        type="submit"
                        disabled={loading}
                        className="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-black py-2.5 rounded-xl text-xs transition shadow-md shadow-emerald-500/20"
                      >
                        إرسال طلب السحب للبنك
                      </button>
                    </form>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* TAB 4: ADMIN */}
          {dashboardTab === 'admin' && session.role === 'SUPER_ADMIN' && (
            <div className="bg-[#101626] border border-gray-800 rounded-2xl p-6 space-y-4">
              <h2 className="text-base font-bold text-white flex items-center gap-2">
                <CreditCard className="w-5 h-5 text-emerald-400" />
                مراجعة وتسوية طلبات السحب البنكية
              </h2>
              <p className="text-xs text-gray-400">إدارة القيود المالية في دفتر الأستاذ وسداد حوالات المسوقين المعتمدة.</p>
              <div className="text-xs text-emerald-400 font-bold bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/20">
                جميع الحسابات المحاسبية متوازنة: Escrow = Debits = Credits (Double-Entry Ledger)
              </div>
            </div>
          )}
        </main>
      )}

      {/* CREATE CAMPAIGN MODAL (FOR BRANDS) */}
      {showCreateCampaignModal && (
        <div className="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#101626] border border-gray-800 rounded-3xl max-w-lg w-full p-6 text-right space-y-4">
            <div className="flex items-center justify-between border-b border-gray-800 pb-3">
              <h2 className="text-base font-bold text-white flex items-center gap-2">
                <PlusCircle className="w-4 h-4 text-emerald-400" /> إطلاق حملة تسويق بالأداء جديدة
              </h2>
              <button onClick={() => setShowCreateCampaignModal(false)} className="text-gray-400 hover:text-white">✕</button>
            </div>

            <form onSubmit={handleCreateCampaign} className="space-y-3 text-xs">
              <div>
                <label className="block text-gray-400 mb-1">عنوان الحملة التسويقية</label>
                <input
                  type="text"
                  required
                  value={newCampaignTitle}
                  onChange={e => setNewCampaignTitle(e.target.value)}
                  placeholder="مثال: تشكيلة العطور الصيفية الحصرية"
                  className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                />
              </div>

              <div>
                <label className="block text-gray-400 mb-1">وصف الحملة والشروط للمسوقين</label>
                <textarea
                  rows={2}
                  required
                  value={newCampaignDesc}
                  onChange={e => setNewCampaignDesc(e.target.value)}
                  placeholder="حدد ما يميز المنتج ونوع الجمهور المستهدف..."
                  className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-gray-400 mb-1">التصنيف</label>
                  <select
                    value={newCampaignCategory}
                    onChange={e => setNewCampaignCategory(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  >
                    <option value="عطور وتجميل">عطور وتجميل</option>
                    <option value="أزياء وموضة">أزياء وموضة</option>
                    <option value="إلكترونيات">إلكترونيات</option>
                    <option value="عناية وصحة">عناية وصحة</option>
                    <option value="أغذية ومشروبات">أغذية ومشروبات</option>
                    <option value="أطفال وألعاب">أطفال وألعاب</option>
                  </select>
                </div>

                <div>
                  <label className="block text-gray-400 mb-1">عملة الحملة</label>
                  <select
                    value={newCampaignCurrency}
                    onChange={e => setNewCampaignCurrency(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  >
                    {SUPPORTED_CURRENCIES.map(c => (
                      <option key={c.code} value={c.code}>{c.code} ({c.name})</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-gray-400 mb-1">نوع العمولة</label>
                  <select
                    value={newCampaignCommType}
                    onChange={e => setNewCampaignCommType(e.target.value as any)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  >
                    <option value="PERCENTAGE">نسبة مئوية من المبيعات (%)</option>
                    <option value="FLAT_FEE">مبلغ ثابت لكل بيع</option>
                  </select>
                </div>

                <div>
                  <label className="block text-gray-400 mb-1">قيمة العمولة للمسوق</label>
                  <input
                    type="number"
                    required
                    value={newCampaignCommVal}
                    onChange={e => setNewCampaignCommVal(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-gray-400 mb-1">سقف الميزانية الإجمالية</label>
                  <input
                    type="number"
                    required
                    value={newCampaignBudget}
                    onChange={e => setNewCampaignBudget(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  />
                </div>

                <div>
                  <label className="block text-gray-400 mb-1">رابط صفحة المتجر / المنتج</label>
                  <input
                    type="url"
                    required
                    value={newCampaignUrl}
                    onChange={e => setNewCampaignUrl(e.target.value)}
                    className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white"
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-black py-2.5 rounded-xl text-xs transition mt-2 shadow-md shadow-emerald-500/20"
              >
                نشر الحملة في سوق نمولي للمبدعين
              </button>
            </form>
          </div>
        </div>
      )}

      {/* AUTH & DEMO ACCESS MODAL */}
      {showAuthModal && (
        <div className="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#101626] border border-gray-800 rounded-3xl max-w-md w-full p-6 text-right space-y-4">
            <div className="flex items-center justify-between border-b border-gray-800 pb-3">
              <h2 className="text-base font-bold text-white">
                {authMode === 'login' ? 'تسجيل الدخول إلى نمولي' : 'إنشاء حساب جديد في سوق الأداء'}
              </h2>
              <button onClick={() => setShowAuthModal(false)} className="text-gray-400 hover:text-white">✕</button>
            </div>

            {/* Quick Demo Access Bar */}
            <div className="bg-gray-950 border border-gray-800/80 p-3 rounded-2xl space-y-2">
              <span className="text-[11px] text-gray-400 block font-semibold">
                تجربة فورية بنقرة واحدة (Quick Demo):
              </span>
              <div className="grid grid-cols-2 gap-2 text-xs">
                <button
                  type="button"
                  onClick={() => handleQuickDemoLogin('BRAND')}
                  className="bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 py-2 rounded-xl font-bold transition flex items-center justify-center gap-1"
                >
                  <Building2 className="w-3.5 h-3.5" /> تجربة براند
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickDemoLogin('MARKETER')}
                  className="bg-teal-500/10 hover:bg-teal-500/20 border border-teal-500/30 text-teal-400 py-2 rounded-xl font-bold transition flex items-center justify-center gap-1"
                >
                  <Users className="w-3.5 h-3.5" /> تجربة صانع محتوى
                </button>
              </div>
            </div>

            <div className="flex items-center gap-2 text-gray-500 text-xs">
              <div className="h-px bg-gray-800 flex-1"></div>
              <span>أو عبر بريدك الإلكتروني</span>
              <div className="h-px bg-gray-800 flex-1"></div>
            </div>

            <form onSubmit={handleAuth} className="space-y-3 text-xs">
              <div>
                <label className="block text-gray-400 mb-1">البريد الإلكتروني</label>
                <input
                  type="email"
                  required
                  value={authEmail}
                  onChange={e => setAuthEmail(e.target.value)}
                  placeholder="name@example.com"
                  className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500"
                />
              </div>

              <div>
                <label className="block text-gray-400 mb-1">كلمة المرور</label>
                <input
                  type="password"
                  required
                  value={authPassword}
                  onChange={e => setAuthPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500"
                />
              </div>

              {authMode === 'register' && (
                <>
                  <div>
                    <label className="block text-gray-400 mb-1">نوع الحساب</label>
                    <select
                      value={authRole}
                      onChange={e => setAuthRole(e.target.value as any)}
                      className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500"
                    >
                      <option value="BRAND">علامة تجارية / متجر إلكتروني (Brand)</option>
                      <option value="MARKETER">صانع محتوى / مسوق بالعمولة (Creator)</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-gray-400 mb-1">
                      {authRole === 'BRAND' ? 'اسم المتجر أو الشركة' : 'الاسم الكامل أو اسم القناة'}
                    </label>
                    <input
                      type="text"
                      required
                      value={authName}
                      onChange={e => setAuthName(e.target.value)}
                      placeholder={authRole === 'BRAND' ? 'متجر العود الملكي' : 'سارة القحطاني'}
                      className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500"
                    />
                  </div>
                </>
              )}

              <button
                type="submit"
                disabled={loading}
                className="w-full bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-black font-black py-2.5 rounded-xl text-xs transition mt-2 shadow-lg shadow-emerald-500/20"
              >
                {loading ? 'جارٍ التحقق...' : authMode === 'login' ? 'دخول' : 'تسجيل حساب جديد'}
              </button>
            </form>

            <div className="pt-2 text-center text-xs text-gray-400">
              {authMode === 'login' ? (
                <span>
                  لا تملك حساباً؟{' '}
                  <button onClick={() => setAuthMode('register')} className="text-emerald-400 font-bold hover:underline">
                    أنشئ حساباً مجاناً
                  </button>
                </span>
              ) : (
                <span>
                  لديك حساب بالفعل؟{' '}
                  <button onClick={() => setAuthMode('login')} className="text-emerald-400 font-bold hover:underline">
                    سجل دخولك
                  </button>
                </span>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Global Footer (Matches rihlatsharq.com) */}
      <footer className="border-t border-gray-800/80 bg-[#070a13] text-gray-400 text-xs py-14 mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-black font-black text-lg">ن</div>
              <span className="text-xl font-black text-white">نَمولي</span>
            </div>
            <p className="text-gray-400 leading-relaxed text-[11px] sm:text-xs">
              أكبر Marketplace عربي للتسويق بالأداء واقتصاد المبدعين. منصة الحملات التي تحقق النمو الحقيقي وتضمن حقوق الجميع بالنتائج المؤكدة.
            </p>
          </div>

          <div>
            <h3 className="font-bold text-white mb-3 text-sm">المنصة</h3>
            <ul className="space-y-2">
              <li><button onClick={() => { setPublicPage('home'); setCurrentView('public'); }} className="hover:text-emerald-400">الرئيسية</button></li>
              <li><button onClick={() => { setPublicPage('about-us'); setCurrentView('public'); }} className="hover:text-emerald-400">عن المنصة</button></li>
              <li><button onClick={() => { setPublicPage('how-it-works'); setCurrentView('public'); }} className="hover:text-emerald-400">كيف تعمل</button></li>
              <li><button onClick={() => { setPublicPage('campaigns'); setCurrentView('public'); }} className="hover:text-emerald-400">لوحة الحملات</button></li>
              <li><button onClick={() => { setPublicPage('prices'); setCurrentView('public'); }} className="hover:text-emerald-400">الأسعار والاشتراكات</button></li>
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-white mb-3 text-sm">المساعدة ودليل النمو</h3>
            <ul className="space-y-2">
              <li><button onClick={() => { setPublicPage('growth'); setCurrentView('public'); }} className="hover:text-emerald-400">مركز ودليل النمو</button></li>
              <li><button onClick={() => { setPublicPage('contact-us'); setCurrentView('public'); }} className="hover:text-emerald-400">تواصل معنا</button></li>
              <li><span className="text-gray-400 font-mono">hello@namoly.com</span></li>
              <li><span className="text-gray-400 font-mono" dir="ltr">+20 114 437 6519</span></li>
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-white mb-3 text-sm">قانوني والسياسات</h3>
            <ul className="space-y-2 text-gray-400">
              <li><button onClick={() => { setPublicPage('privacy'); setCurrentView('public'); }} className="hover:text-emerald-400">سياسة الخصوصية</button></li>
              <li><button onClick={() => { setPublicPage('terms'); setCurrentView('public'); }} className="hover:text-emerald-400">الشروط والأحكام</button></li>
              <li className="text-[11px] text-gray-500 pt-2">المملكة العربية السعودية • جمهورية مصر العربية • الإمارات العربية المتحدة</li>
            </ul>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 pt-6 border-t border-gray-800/80 flex flex-col sm:flex-row items-center justify-between text-gray-500 text-[11px] gap-2">
          <div>© {new Date().getFullYear()} نمولي (Nomowly). جميع الحقوق محفوظة.</div>
          <div>تم التطوير كأكبر منصة عربية للتسويق بالأداء واقتصاد المبدعين</div>
        </div>
      </footer>
    </div>
  );
}
