{{-- پروفایل فروشنده — پورت sellers-old/panel/profile/index.blade.php
     یک فرم کلاسیک PUT به seller.profile.update با عیناً همان فیلدهای قدیمی
     + مودال تأیید + امضای قرارداد (POST کلاسیک به seller.profile.set_econtract).
     کنترلر قدیمی response("success") می‌دهد پس ارسال با fetch و ریدایرکت به
     seller.profile.index انجام می‌شود (هم‌الگوی index.js قدیمی). --}}
<div x-data="{
        // فاز ۲۱۷ — تب فعال از sessionStorage برمی‌گردد (بعد از ثبت کارت بانکی
        // و رفرش صفحه، کاربر دوباره به تب حساب بانکی می‌رسد)
        tab: sessionStorage.getItem('sp-profile-tab') || 'info',
        confirmOpen: false,
        econtractOpen: false,
        econtractChoice: '',
        loading: false,
        stateId: '{{ $seller->seller_info->state_id }}',
        {{-- @js نه @json — داخل attribute کوتیشن‌های خام attribute را می‌شکند --}}
        cities: @js($seller_info->province?->cities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values() ?? []),
        // ⑨-۳ — کامبوباکس جستجودار استان/شهر (فرم کلاسیک با name حفظ می‌شود)؛
        // nullsafe برای فروشنده‌ای که هنوز استان/شهر ندارد
        provOpen: false,
        provQuery: '',
        provTitle: @js($provinces->firstWhere('id', (int) $seller->seller_info->state_id)?->name ?? ''),
        provOptions: @js($provinces->map(fn ($p) => ['id' => $p->id, 'title' => $p->name])->values()->all()),
        cityOpen: false,
        cityQuery: '',
        cityId: '{{ $seller->seller_info->city_id }}',
        cityTitle: @js($seller_info->province?->cities->firstWhere('id', (int) $seller->seller_info->city_id)?->name ?? ''),
        // ⑧-۶ — مشمولیت مالیات ارزش افزوده (باید در x-data تعریف شود)
        vatFree: '{{ $seller->seller_info->vat_free == '1' ? '1' : '2' }}',
        updateUrl: @js($updateUrl),
        setEcontractUrl: @js($setEcontractUrl),
        citiesUrl: @js($citiesUrl),

        // فاز ۲۱۷ — کارت‌های بانکی فروشنده (مدیریت مستقل تب حساب بانکی،
        // خارج از فرم کلاسیک پروفایل و بدون چرخهٔ تایید مدیر)
        bankAccounts: @js($bankAccounts),
        bankStoreUrl: @js($bankStoreUrl),
        bankModalOpen: false,
        bankEditingId: null,
        bankForm: { holder_name: '', card_number: '', sheba_number: '' },
        bankErrors: {},
        bankBusy: false,
        bankDeleteId: null,
        bankDeleteBusy: false,

        // پورت data-action قدیمی province → getCities
        async fetchCities() {
            if (!this.stateId) { this.cities = []; return; }
            try {
                const res = await fetch(this.citiesUrl + '?id=' + this.stateId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                this.cities = await res.json();
            } catch (e) { this.cities = []; }
        },

        // ⑨-۳ — انتخاب استان از کامبوباکس
        get provFiltered() {
            const q = this.provQuery.trim();
            if (!q) { return this.provOptions; }
            return this.provOptions.filter(function (o) { return o.title.indexOf(q) !== -1; });
        },
        toggleProv() {
            this.provOpen = !this.provOpen;
            if (this.provOpen) { this.provQuery = ''; this.$nextTick(() => this.$refs.provSearch?.focus()); }
        },
        pickProv(option) {
            this.stateId = String(option.id);
            this.provTitle = option.title;
            this.provOpen = false;
            this.cityId = ''; this.cityTitle = '';
            this.fetchCities();
        },
        get cityFiltered() {
            const q = this.cityQuery.trim();
            if (!q) { return this.cities; }
            return this.cities.filter(function (o) { return o.name.indexOf(q) !== -1; });
        },
        toggleCity() {
            if (!this.stateId) { this.$dispatch('toast', { message: 'ابتدا استان را انتخاب کنید', type: 'error' }); return; }
            this.cityOpen = !this.cityOpen;
            if (this.cityOpen) { this.cityQuery = ''; this.$nextTick(() => this.$refs.citySearch?.focus()); }
        },
        pickCity(option) {
            this.cityId = String(option.id);
            this.cityTitle = option.name;
            this.cityOpen = false;
        },

        // ارسال فرم کلاسیک به seller.profile.update — کنترلر قدیمی response('success') می‌دهد
        // داخل attribute x-data هیچ double-quote خام مجاز نیست (attribute را می‌شکند)
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch(this.updateUrl, {
                    method: 'POST',
                    body: new FormData(this.$refs.form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.confirmOpen = false;
                    this.$dispatch('toast', { message: 'تغییرات شما با موفقیت ثبت شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js(route('seller.profile.index'))) : (window.location.href = @js(route('seller.profile.index'))), 800);
                    return;
                }

                // خطای اعتبارسنجی (422) — اولین پیام به‌صورت toast
                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ثبت اطلاعات، دوباره تلاش کنید.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },

        // پورت click .set-econtract قدیمی — POST JSON به seller.profile.set_econtract
        async setEcontract() {
            if (!this.econtractChoice) {
                this.$dispatch('toast', { message: 'یک گزینه را انتخاب کنید', type: 'error' });
                return;
            }
            if (this.econtractChoice == '0') { this.econtractOpen = false; return; }

            this.loading = true;
            try {
                const body = new FormData();
                body.append('econtract', '1');
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);

                const res = await fetch(this.setEcontractUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.status == 'success') {
                    this.econtractOpen = false;
                    this.$dispatch('toast', { message: 'قرارداد با موفقیت امضا شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 800);
                } else if (data.status == 'documents') {
                    this.$dispatch('toast', { message: 'اطلاعات فروشنده تاییده نشده است', type: 'error' });
                }
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },

        // ================= فاز ۲۱۷ — کارت‌های بانکی =================
        // تبدیل ارقام فارسی/عربی به انگلیسی (داخل attribute کوتیشن دابل ممنوع است)
        bankEnDigits(value) {
            var map = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9', '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };
            return String(value || '').replace(/[۰-۹٠-٩]/g, function (d) { return map[d]; });
        },
        bankNormCard(value) {
            return this.bankEnDigits(value).replace(/[^0-9]/g, '').slice(0, 16);
        },
        bankNormSheba(value) {
            var s = this.bankEnDigits(value).toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (/^[0-9]{24}$/.test(s)) { s = 'IR' + s; }
            return s.slice(0, 26);
        },
        // کنترل رقم کارت بانکی (الگوریتم Luhn)
        bankValidCard(card) {
            card = this.bankEnDigits(card).replace(/\D/g, '');
            if (!/^[2569]\d{15}$/.test(card)) { return false; }
            var sum = 0, alt = false;
            for (var i = card.length - 1; i >= 0; i--) {
                var d = parseInt(card[i], 10);
                if (alt) { d *= 2; if (d > 9) { d -= 9; } }
                sum += d;
                alt = !alt;
            }
            return sum % 10 === 0;
        },
        // کنترل شبای ایرانی (IR + ۲۴ رقم، MOD-97؛ IR معادل 1827)
        bankValidSheba(code) {
            code = this.bankEnDigits(code).toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (/^[0-9]{24}$/.test(code)) { code = 'IR' + code; }
            if (!/^IR[0-9]{24}$/.test(code)) { return false; }
            var s = code.substring(4) + '1827' + code.substring(2, 4);
            var r = 0;
            for (var i = 0; i < s.length; i++) { r = (r * 10 + parseInt(s[i], 10)) % 97; }
            return r === 1;
        },
        bankMaskCard(card) {
            var c = String(card || '');
            if (c.length !== 16) { return c || '—'; }
            return c.replace(/(\d{4})(?=\d)/g, '$1 ');
        },
        openBankAdd() {
            this.bankEditingId = null;
            this.bankForm = { holder_name: '', card_number: '', sheba_number: '' };
            this.bankErrors = {};
            this.bankModalOpen = true;
        },
        openBankEdit(account) {
            this.bankEditingId = account.id;
            this.bankForm = { holder_name: account.holder_name, card_number: account.card_number, sheba_number: account.sheba_number };
            this.bankErrors = {};
            this.bankModalOpen = true;
        },
        closeBankModal() {
            this.bankModalOpen = false;
            this.bankEditingId = null;
            this.bankErrors = {};
        },
        bankFind(id) {
            var found = null;
            for (var i = 0; i < this.bankAccounts.length; i++) {
                if (this.bankAccounts[i].id === id) { found = this.bankAccounts[i]; }
            }
            return found;
        },
        async saveBank() {
            this.bankForm.holder_name = this.bankForm.holder_name.trim();
            this.bankForm.card_number = this.bankNormCard(this.bankForm.card_number);
            this.bankForm.sheba_number = this.bankNormSheba(this.bankForm.sheba_number);
            this.bankErrors = {};

            if (! this.bankForm.holder_name) { this.bankErrors.holder_name = ['نام صاحب حساب را وارد کنید']; }
            if (! this.bankForm.card_number) {
                this.bankErrors.card_number = ['شماره کارت را وارد کنید'];
            } else if (! this.bankValidCard(this.bankForm.card_number)) {
                this.bankErrors.card_number = ['شماره کارت وارد شده معتبر نیست؛ کارت بانکی ۱۶ رقمی را بررسی کنید'];
            }
            if (! this.bankForm.sheba_number) {
                this.bankErrors.sheba_number = ['شماره شبا را وارد کنید'];
            } else if (! this.bankValidSheba(this.bankForm.sheba_number)) {
                this.bankErrors.sheba_number = ['شماره شبا وارد شده معتبر نیست؛ شبای ۲۶ رقمی (IR + ۲۴ رقم) را بررسی کنید'];
            }
            if (Object.keys(this.bankErrors).length) { return; }

            this.bankBusy = true;
            try {
                var editing = this.bankEditingId ? this.bankFind(this.bankEditingId) : null;
                var body = new FormData();
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);
                if (editing) { body.append('_method', 'PUT'); }
                body.append('holder_name', this.bankForm.holder_name);
                body.append('card_number', this.bankForm.card_number);
                body.append('sheba_number', this.bankForm.sheba_number);

                const res = await fetch(editing ? editing.update_url : this.bankStoreUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    this.closeBankModal();
                    sessionStorage.setItem('sp-profile-tab', 'bank');
                    this.$dispatch('toast', { message: data.message || 'کارت بانکی ذخیره شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 700);
                    return;
                }
                if (data.errors) {
                    this.bankErrors = data.errors;
                    this.$dispatch('toast', { message: Object.values(data.errors)[0][0], type: 'error' });
                } else {
                    this.$dispatch('toast', { message: data.message || 'ثبت کارت بانکی ناموفق بود.', type: 'error' });
                }
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.bankBusy = false;
            }
        },
        askBankDelete(account) {
            // شرط «حداقل یک کارت» — حذف آخرین کارت ممنوع است
            if (this.bankAccounts.length <= 1) {
                this.$dispatch('toast', { message: 'حداقل یک کارت بانکی باید در پروفایل شما باقی بماند؛ ابتدا کارت دیگری اضافه کنید.', type: 'error' });
                return;
            }
            this.bankDeleteId = account.id;
        },
        async destroyBank() {
            var target = this.bankDeleteId ? this.bankFind(this.bankDeleteId) : null;
            if (! target) { return; }

            this.bankDeleteBusy = true;
            try {
                var body = new FormData();
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);
                body.append('_method', 'DELETE');

                const res = await fetch(target.destroy_url, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    this.bankDeleteId = null;
                    sessionStorage.setItem('sp-profile-tab', 'bank');
                    this.$dispatch('toast', { message: data.message || 'کارت بانکی حذف شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 700);
                    return;
                }
                this.$dispatch('toast', { message: data.message || 'حذف کارت بانکی ناموفق بود.', type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.bankDeleteBusy = false;
            }
        },
        async setBankDefault(account) {
            if (account.is_default) { return; }
            try {
                var body = new FormData();
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);

                const res = await fetch(account.default_url, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    sessionStorage.setItem('sp-profile-tab', 'bank');
                    this.$dispatch('toast', { message: data.message || 'کارت پیش‌فرض تسویه تغییر کرد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 700);
                    return;
                }
                this.$dispatch('toast', { message: data.message || 'تغییر کارت پیش‌فرض ناموفق بود.', type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            }
        },
     }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="user" class="size-5 text-brand-600 dark:text-brand-400" />
            پروفایل شما
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اطلاعات حساب، آدرس و مدارک فروشندگی خود را از این قسمت مدیریت کنید.</p>
    </div>

    {{-- فاز ۲۱۶ — اطلاع‌رسانی جابه‌جایی لوگو/گذرواژه به «حساب و امنیت" --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-3 text-xs text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
        <span class="flex items-center gap-2">
            <x-admin-panel::icon name="shield-check" class="size-4 shrink-0 text-brand-600 dark:text-brand-400" />
            تغییر لوگوی فروشنده و گذرواژه به صفحهٔ «حساب و امنیت» منتقل شد؛ آنجا بلافاصله و بدون تایید کارشناس اعمال می‌شوند.
        </span>
        <a href="{{ route('seller.profile.account') }}" wire:navigate
           class="ap-btn-primary inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-3 text-[11px] font-bold">
            <x-admin-panel::icon name="lock" class="size-3.5" />
            حساب و امنیت
        </a>
    </div>

    {{-- ================= تب‌ها ================= --}}
    <div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 sp-scroll">
        @foreach (['info' => 'اطلاعات کلی', 'address' => 'آدرس', 'bank' => 'حساب بانکی', 'econtract' => 'اطلاعات قرارداد', 'documents' => 'مدارک'] as $key => $label)
            {{-- فاز ۲۱۶ — تب «گذرواژه» از این صفحه حذف شد (تغییر رمز نباید وارد
                 چرخهٔ تایید مدیر شود) → صفحهٔ مستقل «حساب و امنیت" --}}
            <button type="button" x-on:click="tab = '{{ $key }}'"
                    class="min-h-11 shrink-0 rounded-xl px-4 text-xs font-bold transition-colors"
                    x-bind:class="tab === '{{ $key }}'
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'bg-surface-2 text-ink-600 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2'"
                    x-bind:aria-selected="tab === '{{ $key }}'" role="tab">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ================= فرم کلاسیک PUT — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="form" method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('put')

        {{-- ---------- تب اطلاعات کلی ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'info'" role="tabpanel" aria-label="اطلاعات کلی">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام</span>
                    <input type="text" name="first_name" value="{{ $seller->seller_info->first_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام خانوادگی</span>
                    <input type="text" name="last_name" value="{{ $seller->seller_info->last_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">جنسیت</span>
                    <select name="gender" class="ap-input w-full">
                        <option value="male" @selected($seller->seller_info->gender == 'male')>مرد</option>
                        <option value="female" @selected($seller->seller_info->gender == 'female')>زن</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تاریخ تولد</span>
                    <input type="text" name="birth_day" value="{{ $seller->seller_info->birth_day }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد ملی</span>
                    <input type="number" name="national_identity_number" value="{{ $seller->seller_info->national_identity_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره شناسنامه</span>
                    <input type="number" name="identity_card_number" value="{{ $seller->seller_info->identity_card_number }}" class="ap-input w-full fa-digits">
                </label>
            </div>

            {{-- بخش کسب‌وکار — برای «حقیقی» پنهان (هم‌الگوی business-div قدیمی) --}}
            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-night-line"
                 x-bind:class="{{ json_encode($seller->seller_info->private_business == 'private') }} ? 'hidden' : 'grid'">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام شرکت</span>
                    <input type="text" name="company_name" value="{{ $seller->seller_info->company_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع شرکت</span>
                    <select name="company_type" class="ap-input w-full">
                        <option value="public" @selected($seller->seller_info->company_type == 'public')>سهامی عام</option>
                        <option value="joint_stock" @selected($seller->seller_info->company_type == 'joint_stock')>سهامی خاص</option>
                        <option value="ltd" @selected($seller->seller_info->company_type == 'ltd')>مسولیت محدود</option>
                        <option value="coop" @selected($seller->seller_info->company_type == 'coop')>تعاونی</option>
                        <option value="solidarity" @selected($seller->seller_info->company_type == 'solidarity')>تضامنی</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره ثبت</span>
                    <input type="text" name="company_registration_number" value="{{ $seller->seller_info->company_registration_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شناسه ملی</span>
                    <input type="text" name="company_national_identity_number" value="{{ $seller->seller_info->company_national_identity_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد اقتصادی</span>
                    <input type="text" name="company_economic_number" value="{{ $seller->seller_info->company_economic_number }}" class="ap-input w-full fa-digits">
                </label>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 lg:grid-cols-2 dark:border-night-line">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قصد فروش چه کالاهایی را دارید؟</span>
                    <select name="main_supply_category_id" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($seller->seller_info && $seller->seller_info->main_supply_category_id == $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تعداد حدودی تنوع کالای آماده فروش</span>
                    <select name="number_of_products" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ([10 => '1-10', 50 => '11-50', 100 => '51-100', 300 => '101-300', 1000 => '301-1000', 3000 => '1001-3000', 10000 => '3001-10000', 30000 => '10001-30000'] as $value => $label)
                            <option value="{{ $value }}" @selected($seller->seller_info && $seller->seller_info->number_of_products == (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-4 border-t border-line pt-4 dark:border-night-line">
                {{-- فاز ۲۱۶ — لوگوی فروشنده از این صفحه حذف شد؛ تغییر لوگو بدون
                     چرخهٔ تایید مدیر باید انجام شود → صفحهٔ «حساب و امنیت" --}}
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">درباره فروشنده</span>
                    <textarea name="bio" rows="3" class="ap-input w-full">{{ $seller->seller_info->bio }}</textarea>
                </label>
            </div>
        </section>

        {{-- ---------- تب آدرس ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'address'" role="tabpanel" aria-label="آدرس" x-cloak>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">ایمیل</span>
                    <input type="email" name="email" value="{{ $seller->email }}" class="ap-input w-full" dir="ltr">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تلفن همراه <span class="text-red-500">(ثبت نام)</span></span>
                    <input type="number" value="{{ $seller->seller_info->mobile }}" disabled class="ap-input w-full cursor-not-allowed opacity-60 fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وب سایت</span>
                    <input type="text" name="website" value="{{ $seller->seller_info->website }}" class="ap-input w-full" dir="ltr">
                </label>

                {{-- ⑨-۳ — استان با کامبوباکس جستجودار (name فرم کلاسیک حفظ شده) --}}
                <div class="relative" x-on:click.outside="provOpen = false" x-on:keydown.escape.window="provOpen = false">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">استان فروشگاه</span>
                    <input type="hidden" name="state_id" x-model="stateId">
                    <button type="button" class="ap-input flex w-full items-center justify-between gap-2 py-2.5! text-start"
                            x-on:click="toggleProv()" aria-haspopup="listbox" x-bind:aria-expanded="String(provOpen)">
                        <span class="truncate text-[13px]" x-bind:class="provTitle ? 'font-bold text-ink dark:text-night-ink' : 'font-semibold text-ink-3 dark:text-night-ink-2'"
                              x-text="provTitle || 'جستجو و انتخاب استان…'"></span>
                        <x-admin-panel::icon name="chevron-down" class="size-4 shrink-0 text-ink-3 transition-transform" x-bind:class="provOpen ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="provOpen" x-cloak x-transition.opacity.duration.150ms
                         class="absolute inset-x-0 top-full z-30 mt-1.5 overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl dark:border-night-line dark:bg-night-2"
                         role="listbox" aria-label="فهرست استان‌ها">
                        <div class="border-b border-line p-2 dark:border-night-line">
                            <div class="relative">
                                <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                                <input x-ref="provSearch" type="text" x-model="provQuery"
                                       class="ap-input w-full py-2.5! pe-10! text-[13px]!" placeholder="جستجوی استان…" aria-label="جستجوی استان">
                            </div>
                        </div>
                        <div class="max-h-56 overflow-y-auto p-1.5">
                            <template x-for="option in provFiltered" :key="option.id">
                                <button type="button" x-on:click="pickProv(option)" role="option"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-start text-[13px] font-semibold transition-colors"
                                        :class="String(stateId) === String(option.id)
                                            ? 'bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                            : 'text-ink-2 hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3'"
                                        x-text="option.title"></button>
                            </template>
                            <p x-show="! provFiltered.length" class="px-3 py-6 text-center text-xs font-semibold text-ink-3 dark:text-night-ink-2" x-cloak>
                                استانی با این نام پیدا نشد
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ⑨-۳ — شهر با کامبوباکس جستجودار --}}
                <div class="relative" x-on:click.outside="cityOpen = false" x-on:keydown.escape.window="cityOpen = false">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شهر فروشگاه</span>
                    <input type="hidden" name="city_id" x-model="cityId">
                    <button type="button" class="ap-input flex w-full items-center justify-between gap-2 py-2.5! text-start"
                            x-bind:class="! stateId && 'opacity-60 cursor-not-allowed'"
                            x-on:click="toggleCity()" aria-haspopup="listbox" x-bind:aria-expanded="String(cityOpen)">
                        <span class="truncate text-[13px]" x-bind:class="cityTitle ? 'font-bold text-ink dark:text-night-ink' : 'font-semibold text-ink-3 dark:text-night-ink-2'"
                              x-text="stateId ? (cityTitle || 'جستجو و انتخاب شهر…') : 'ابتدا استان را انتخاب کنید'"></span>
                        <x-admin-panel::icon name="chevron-down" class="size-4 shrink-0 text-ink-3 transition-transform" x-bind:class="cityOpen ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="cityOpen" x-cloak x-transition.opacity.duration.150ms
                         class="absolute inset-x-0 top-full z-30 mt-1.5 overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl dark:border-night-line dark:bg-night-2"
                         role="listbox" aria-label="فهرست شهرها">
                        <div class="border-b border-line p-2 dark:border-night-line">
                            <div class="relative">
                                <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                                <input x-ref="citySearch" type="text" x-model="cityQuery"
                                       class="ap-input w-full py-2.5! pe-10! text-[13px]!" placeholder="جستجوی شهر…" aria-label="جستجوی شهر">
                            </div>
                        </div>
                        <div class="max-h-56 overflow-y-auto p-1.5">
                            <template x-for="city in cityFiltered" :key="city.id">
                                <button type="button" x-on:click="pickCity(city)" role="option"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-start text-[13px] font-semibold transition-colors"
                                        :class="String(cityId) === String(city.id)
                                            ? 'bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                            : 'text-ink-2 hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3'"
                                        x-text="city.name"></button>
                            </template>
                            <p x-show="! cityFiltered.length" class="px-3 py-6 text-center text-xs font-semibold text-ink-3 dark:text-night-ink-2" x-cloak>
                                شهری با این نام پیدا نشد
                            </p>
                        </div>
                    </div>
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">موقعیت مکانی</span>
                    <input type="text" name="location" value="{{ $seller->seller_info->location }}" class="ap-input w-full">
                </label>

                <label class="block sm:col-span-2 lg:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">آدرس</span>
                    <input type="text" name="address" value="{{ $seller->seller_info->address }}" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد پستی</span>
                    <input type="number" name="post_code" value="{{ $seller->seller_info->post_code }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تلفن ثابت</span>
                    <input type="number" name="phone" value="{{ $seller->seller_info->phone }}" class="ap-input w-full fa-digits">
                </label>
            </div>
        </section>

        {{-- ---------- تب حساب بانکی — فاز ۲۱۷ بازطراحی کامل ----------
             کارت‌های بانکی به‌صورت کارت‌نمای مدرن؛ افزودن/ویرایش/حذف مستقل
             و فوری (بدون چرخهٔ تایید مدیر). حداقل یک کارت باید باقی بماند؛
             کارت پیش‌فرض مقصد تسویه است و با sellers_info.shaba_number همگام می‌شود.
             (شبا و صاحب حسابِ فقط‌نمایشی از فرم کلاسیک پروفایل حذف شدند) --}}
        <section class="ap-card space-y-4 p-4 sm:p-6" x-show="tab === 'bank'" role="tabpanel" aria-label="حساب بانکی" x-cloak>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-semibold leading-6 text-ink-500 dark:text-night-ink-2">
                    کارت‌های بانکی خود را برای دریافت تسویه مدیریت کنید؛
                    <span class="font-bold text-ink-600 dark:text-night-ink">حداقل یک کارت</span>
                    باید در پروفایل شما باقی بماند.
                </p>
                <button type="button" x-on:click="openBankAdd()"
                        class="ap-btn-primary inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن کارت جدید
                </button>
            </div>

            {{-- کارت‌ها به‌صورت کارت‌نمای بانکی --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <template x-for="account in bankAccounts" :key="account.id">
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-bl from-brand-600 via-brand-700 to-emerald-700 p-5 text-white shadow-xl transition-transform hover:scale-[1.01]">
                        <div class="flex items-start justify-between gap-2">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-white/15">
                                <x-admin-panel::icon name="credit-card" class="size-5" />
                            </span>
                            <span x-show="account.is_default"
                                  class="flex items-center gap-1 rounded-full bg-white/25 px-2.5 py-1 text-[10px] font-black">
                                <x-admin-panel::icon name="badge-check" class="size-3" />
                                کارت پیش‌فرض تسویه
                            </span>
                        </div>

                        <p dir="ltr" class="mt-5 text-start font-mono text-lg font-extrabold tracking-widest"
                           x-text="bankMaskCard(account.card_number)"></p>
                        <p x-show="! account.card_number"
                           class="mt-2 rounded-lg bg-amber-400/20 px-2.5 py-1.5 text-[11px] font-bold text-amber-100">
                            شماره کارت ثبت نشده است؛ برای تکمیل، کارت را ویرایش کنید.
                        </p>

                        <div class="mt-4 space-y-1.5 text-[12px]">
                            <p class="flex items-center gap-1.5 font-bold">
                                <x-admin-panel::icon name="user" class="size-3.5 shrink-0 opacity-75" />
                                <span x-text="account.holder_name"></span>
                            </p>
                            <p dir="ltr" class="truncate font-mono text-[11px] text-white/80" x-text="account.sheba_number"></p>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/15 pt-3">
                            <button type="button" x-on:click="openBankEdit(account)"
                                    class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-white/15 px-3 text-[11px] font-bold transition-colors hover:bg-white/25">
                                <x-admin-panel::icon name="pencil" class="size-3.5" />
                                ویرایش
                            </button>
                            <button type="button" x-on:click="askBankDelete(account)"
                                    class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-red-500/25 px-3 text-[11px] font-bold transition-colors hover:bg-red-500/40">
                                <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                حذف
                            </button>
                            <button type="button" x-show="! account.is_default" x-on:click="setBankDefault(account)"
                                    class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-emerald-400/20 px-3 text-[11px] font-bold transition-colors hover:bg-emerald-400/35">
                                <x-admin-panel::icon name="badge-check" class="size-3.5" />
                                انتخاب برای تسویه
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- حالت خالی (نبود کارت) --}}
            <div x-show="! bankAccounts.length"
                 class="rounded-2xl border border-dashed border-line p-8 text-center dark:border-night-line">
                <x-admin-panel::icon name="credit-card" class="mx-auto size-8 text-ink-3" />
                <p class="mt-3 text-xs font-bold text-ink-600 dark:text-night-ink-2">هنوز کارت بانکی ثبت نکرده‌اید</p>
                <button type="button" x-on:click="openBankAdd()"
                        class="ap-btn-primary mx-auto mt-3 inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن کارت بانکی
                </button>
            </div>
        </section>

        {{-- ---------- تب اطلاعات قرارداد ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'econtract'" role="tabpanel" aria-label="اطلاعات قرارداد" x-cloak>
            @if ($seller_info->econtract == '1')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    قرارداد توسط شما امضا شده است.
                </div>
                <button type="button" x-on:click="econtractOpen = true"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="file-signature" class="size-4" />
                    مشاهده قرارداد
                </button>
            @else
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    قرارداد توسط شما امضا نشده است.
                </div>
                <button type="button" x-on:click="econtractOpen = true"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="file-signature" class="size-4" />
                    از اینجا امضا کنید
                </button>
            @endif
        </section>

        {{-- ---------- تب مدارک ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'documents'" role="tabpanel" aria-label="مدارک" x-cloak>
            @if ($seller->status_documents == 'Reject')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما توسط کارشناسان ما برسی و مورد تایید قرار نگرفت، لطفا مجددا مدارک خود را بارگذاری کنید.
                </div>
            @elseif ($seller->status_documents == 'Accept')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما تایید شده است.
                </div>
            @elseif ($seller->status_documents == 'Waiting')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما در حال برسی می باشد.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر کارت ملی</span>
                    <img src="{{ $seller->seller_info->card_image ? asset($seller->seller_info->card_image) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر کارت ملی">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="card_image" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
                <div>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر پشت کارت ملی</span>
                    <img src="{{ $seller->seller_info->card_image_back ? asset($seller->seller_info->card_image_back) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر پشت کارت ملی">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="card_image_back" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">مشمولیت مالیات بر ارزش افزوده</span>
                    <select name="vat_free" class="ap-input w-full" x-on:change="vatFree = $event.target.value">
                        <option value="1" @selected($seller->seller_info->vat_free == '1')>بله</option>
                        <option value="2" @selected($seller->seller_info->vat_free == '2')>خیر</option>
                    </select>
                </label>
                <div x-show="vatFree !== '2'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر گواهی ارزش افزوده</span>
                    <img src="{{ $seller->seller_info->vat_image ? asset($seller->seller_info->vat_image) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر گواهی ارزش افزوده">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="vat_image" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
            </div>
        </section>

        {{-- ---------- تب گذرواژه — فاز ۲۱۶ حذف شد ----------
             تغییر رمز عبور به صفحهٔ مستقل «حساب و امنیت» منتقل شد تا مثل
             لوگو، بدون تایید کارشناس و بلافاصله اعمال شود. --}}
    </form>

    {{-- دکمهٔ ثبت تغییرات — با مودال تأیید (پورت error-edit-modal قدیمی) --}}
    <div class="ap-card mt-4 flex items-center justify-between gap-3 p-4 sm:p-6">
        <p class="hidden text-xs text-ink-500 sm:block dark:text-night-ink-2">پس از ثبت، اطلاعات شما برای بررسی به کارشناسان ارسال می‌شود.</p>
        <button type="button" x-on:click="confirmOpen = true"
                class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold">
            <x-admin-panel::icon name="save" class="size-4" />
            ثبت تغییرات
        </button>
    </div>

    {{-- ================= مودال تأیید ثبت (پورت error-edit-modal قدیمی) ================= --}}
    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="confirmOpen = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-sm" x-show="confirmOpen" x-transition.scale.origin.center>
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="text-sm font-bold">آیا مطمئن هستید؟</h3>
            </header>
            <div class="px-4 py-4 text-xs leading-relaxed text-ink-600 dark:text-night-ink-2">
                با ایجاد تغییرات در پنل،اطلاعات شما باید توسط کارشناسان برسی و تایید شود، این کار ممکن است زمان بر باشد.
            </div>
            <footer class="flex items-center justify-start gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                <button type="button" x-on:click="confirmOpen = false"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">خیر</button>
                <button type="button" x-on:click="submitForm()"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white transition-colors hover:bg-red-600"
                        :disabled="loading" x-bind:class="loading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                    بله ویرایش شود
                </button>
            </footer>
        </div>
    </div>

    {{-- ================= مودال قرارداد (پورت seller-econtracts-modal قدیمی) ================= --}}
    <div x-show="econtractOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="econtractOpen = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 max-h-[90vh] w-full max-w-3xl overflow-y-auto sp-scroll" x-show="econtractOpen" x-transition.scale.origin.center>
            <header class="sticky top-0 flex items-center justify-between border-b border-line bg-surface px-4 py-3 dark:border-night-line dark:bg-night-1">
                <h3 class="text-sm font-bold">قرارداد فروشندگی</h3>
                <button type="button" x-on:click="econtractOpen = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            <div class="space-y-3 px-4 py-4 text-xs leading-relaxed text-ink-700 dark:text-night-ink-2">
                <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-2">{!! $econtract->header !!}</div>
                <div class="rounded-xl border border-line p-3 dark:border-night-line">{!! $econtract->content !!}</div>
            </div>

            @if ($seller_info->econtract == '0')
                <footer class="space-y-3 border-t border-line px-4 py-4 dark:border-night-line">
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-xs font-semibold text-ink-700 dark:text-night-ink-2">
                        <input type="radio" value="1" name="econtract" x-model="econtractChoice" class="size-4 accent-emerald-600">
                        قرارداد را کامل خوانده‌ام و موافقم
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-xs font-semibold text-ink-700 dark:text-night-ink-2">
                        <input type="radio" value="0" name="econtract" x-model="econtractChoice" class="size-4 accent-red-500">
                        مخالفم و از ثبت‌نام انصراف می‌دهم
                    </label>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button" x-on:click="setEcontract()"
                                class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold"
                                :disabled="loading" x-bind:class="loading && 'opacity-60'">
                            <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                            ثبت امضا
                        </button>
                        <button type="button" x-on:click="econtractOpen = false"
                                class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">بعدا انجام می‌دهم</button>
                    </div>
                </footer>
            @endif
        </div>
    </div>

    {{-- ================= مودال افزودن/ویرایش کارت بانکی (فاز ۲۱۷) ================= --}}
    <div x-show="bankModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="closeBankModal()" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-md" x-show="bankModalOpen" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="credit-card" class="size-4 text-brand-600 dark:text-brand-400" />
                    <span x-text="bankEditingId ? 'ویرایش کارت بانکی' : 'افزودن کارت بانکی جدید'"></span>
                </h3>
                <button type="button" x-on:click="closeBankModal()" class="min-h-9 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            <div class="space-y-4 px-4 py-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام صاحب حساب <span class="text-red-500">*</span></span>
                    <input type="text" x-model="bankForm.holder_name" x-on:input="delete bankErrors.holder_name"
                           class="ap-input w-full" placeholder="نام و نام خانوادگی صاحب حساب">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="bankErrors.holder_name" x-text="(bankErrors.holder_name || [])[0]" x-cloak></p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره کارت <span class="text-red-500">*</span></span>
                    <input type="text" inputmode="numeric" maxlength="16" dir="ltr" autocomplete="off"
                           x-model="bankForm.card_number"
                           x-on:input="$event.target.value = bankNormCard($event.target.value); bankForm.card_number = $event.target.value; delete bankErrors.card_number"
                           class="ap-input w-full fa-digits font-mono text-start" placeholder="6037991112345678">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="bankErrors.card_number" x-text="(bankErrors.card_number || [])[0]" x-cloak></p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره شبا <span class="text-red-500">*</span></span>
                    <input type="text" dir="ltr" autocomplete="off"
                           x-model="bankForm.sheba_number"
                           x-on:input="$event.target.value = bankNormSheba($event.target.value); bankForm.sheba_number = $event.target.value; delete bankErrors.sheba_number"
                           class="ap-input w-full fa-digits font-mono text-start" placeholder="IR______________________________">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="bankErrors.sheba_number" x-text="(bankErrors.sheba_number || [])[0]" x-cloak></p>
                </label>
                <p class="rounded-xl bg-surface-2 p-3 text-[11px] font-semibold leading-5 text-ink-500 dark:bg-night-3 dark:text-night-ink-2">
                    تسویه‌ها به کارتِ «پیش‌فرض» انجام می‌شود. شبای معتبر به‌صورت IR + ۲۴ رقم کنترل می‌شود تا تسویه بدون مشکل انجام شود.
                </p>
            </div>
            <footer class="flex items-center gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                <button type="button" x-on:click="closeBankModal()"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">انصراف</button>
                <button type="button" x-on:click="saveBank()" :disabled="bankBusy" x-bind:class="bankBusy && 'opacity-60'"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="bankBusy" x-cloak />
                    ذخیره کارت
                </button>
            </footer>
        </div>
    </div>

    {{-- ================= مودال تأیید حذف کارت بانکی (فاز ۲۱۷) ================= --}}
    <div x-show="bankDeleteId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="bankDeleteId = null" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-sm" x-show="bankDeleteId !== null" x-transition.scale.origin.center>
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="triangle-alert" class="size-4 text-red-500" />
                    حذف کارت بانکی
                </h3>
            </header>
            <div class="px-4 py-4 text-xs leading-relaxed text-ink-600 dark:text-night-ink-2">
                آیا از حذف این کارت بانکی مطمئن هستید؟ اگر کارت پیش‌فرض تسویه را حذف کنید، یکی از کارت‌های دیگر به‌صورت خودکار به‌عنوان پیش‌فرض انتخاب می‌شود.
            </div>
            <footer class="flex items-center gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                <button type="button" x-on:click="bankDeleteId = null"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">خیر</button>
                <button type="button" x-on:click="destroyBank()" :disabled="bankDeleteBusy" x-bind:class="bankDeleteBusy && 'opacity-60'"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white transition-colors hover:bg-red-600">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="bankDeleteBusy" x-cloak />
                    بله، حذف شود
                </button>
            </footer>
        </div>
    </div>
</div>
