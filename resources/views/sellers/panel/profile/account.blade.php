{{-- فاز ۲۱۶ — حساب و امنیت فروشنده (پنل مدرن) — لوگو + گذرواژه --}}
{{-- لوگو و تغییر رمز از پروفایل به اینجا منتقل شدند چون این دو باید بدون
     تایید کارشناس و بلافاصله اعمال شوند (برخلاف سایر فیلدهای پروفایل). --}}
<div x-data="{
        logoLoading: false,
        passLoading: false,
        logoPreview: '',
        logoUrl: @js($logoUrl),
        passwordUrl: @js($passwordUrl),

        // آپلود لوگو — POST کلاسیک به seller.profile.account.logo
        async submitLogo() {
            const input = this.$refs.logoInput;
            if (! input || ! input.files || ! input.files.length) {
                this.$dispatch('toast', { message: 'ابتدا تصویر لوگو را انتخاب کنید', type: 'error' });
                return;
            }

            this.logoLoading = true;
            try {
                const body = new FormData();
                body.append('image', input.files[0]);
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);

                const res = await fetch(this.logoUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.$dispatch('toast', { message: 'لوگوی فروشنده با موفقیت به‌روزرسانی شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js(route('seller.profile.account'))) : window.location.reload(), 800);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در بارگذاری لوگو، دوباره تلاش کنید.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.logoLoading = false;
            }
        },

        // پیش‌نمایش زندهٔ لوگوی انتخاب‌شده
        pickLogo(event) {
            const file = event.target.files && event.target.files[0];
            this.logoPreview = file ? URL.createObjectURL(file) : '';
        },

        // تغییر گذرواژه — POST کلاسیک به seller.profile.account.password
        async submitPassword() {
            const form = this.$refs.passForm;
            const prev = form.querySelector('[name=prev_password]').value;
            const pass = form.querySelector('[name=password]').value;
            const confirmation = form.querySelector('[name=password_confirmation]').value;

            if (! prev) { this.$dispatch('toast', { message: 'وارد کردن گذرواژه قبلی الزامی است.', type: 'error' }); return; }
            if (! pass || pass.length < 6) { this.$dispatch('toast', { message: 'گذرواژه جدید باید حداقل ۶ کاراکتر باشد.', type: 'error' }); return; }
            if (pass !== confirmation) { this.$dispatch('toast', { message: 'تکرار گذرواژه جدید با گذرواژه جدید یکسان نیست.', type: 'error' }); return; }

            this.passLoading = true;
            try {
                const body = new FormData(form);
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);

                const res = await fetch(this.passwordUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    form.reset();
                    this.$dispatch('toast', { message: 'رمز ورود شما تغییر کرد.', type: 'success' });
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در تغییر گذرواژه، دوباره تلاش کنید.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.passLoading = false;
            }
        },
     }">

    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="shield-check" class="size-5 text-brand-600 dark:text-brand-400" />
            حساب و امنیت
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">لوگوی فروشگاه و گذرواژهٔ ورود خود را از این قسمت تغییر دهید؛ این تغییرات بلافاصله اعمال می‌شوند و نیازی به تایید کارشناس ندارند.</p>
    </div>

    {{-- ================= لوگوی فروشنده ================= --}}
    <section class="ap-card p-4 sm:p-6" aria-label="لوگوی فروشنده">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="image" class="size-4 text-brand-600 dark:text-brand-400" />
            لوگوی فروشنده
        </h2>

        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-3">
            <div class="flex flex-col items-center gap-2">
                <span class="mb-1 block self-start text-xs font-semibold text-ink-600 dark:text-night-ink-2">لوگوی فعلی</span>
                <img :src="logoPreview || '{{ $seller_info->logo ? asset($seller_info->logo) : asset('/empty.svg') }}'"
                     src="{{ $seller_info->logo ? asset($seller_info->logo) : asset('/empty.svg') }}"
                     class="h-28 w-28 rounded-xl border border-line object-cover dark:border-night-line" alt="لوگوی فروشنده">
            </div>

            <div class="sm:col-span-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تغییر لوگو</span>
                    <input type="file" accept="image/*" name="image" x-ref="logoInput" x-on:change="pickLogo($event)"
                           class="ap-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">بهترین اندازه <span class="text-red-500">600 * 600</span> پیکسل میباشد. فرمت‌های مجاز: JPG، PNG، WEBP — حداکثر ۳ مگابایت.</small>
                </label>
                <div class="mt-3">
                    <button type="button" x-on:click="submitLogo()"
                            class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold"
                            :disabled="logoLoading" x-bind:class="logoLoading && 'opacity-60'">
                        <x-admin-panel::icon name="loader-circle" class="size-4" x-show="logoLoading" x-cloak />
                        <x-admin-panel::icon name="upload" class="size-4" x-show="! logoLoading" />
                        بارگذاری لوگو
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= تغییر گذرواژه ================= --}}
    <section class="ap-card mt-4 p-4 sm:p-6" aria-label="تغییر گذرواژه">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="lock" class="size-4 text-brand-600 dark:text-brand-400" />
            تغییر گذرواژه
        </h2>

        <form x-ref="passForm" method="POST" action="{{ $passwordUrl }}" class="mx-auto grid max-w-xl grid-cols-1 gap-4" x-on:submit.prevent="submitPassword()">
            @csrf
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">گذرواژه قبلی</span>
                <input type="password" name="prev_password" class="ap-input w-full" dir="ltr" autocomplete="current-password">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">گذرواژه جدید</span>
                <input type="password" name="password" class="ap-input w-full" dir="ltr" autocomplete="new-password">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تکرار گذرواژه جدید</span>
                <input type="password" name="password_confirmation" class="ap-input w-full" dir="ltr" autocomplete="new-password">
            </label>
            <div class="flex items-start gap-2 rounded-xl border border-line bg-surface-2 p-3 text-xs text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                پس از ذخیره، گذرواژهٔ جدید بلافاصله فعال می‌شود؛ دفعهٔ بعد با گذرواژهٔ جدید وارد شوید.
            </div>
            <div>
                <button type="submit"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold"
                        :disabled="passLoading" x-bind:class="passLoading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="passLoading" x-cloak />
                    <x-admin-panel::icon name="save" class="size-4" x-show="! passLoading" />
                    ذخیرهٔ گذرواژه
                </button>
            </div>
        </form>
    </section>
</div>
