{{-- SaniarTheme — فرم خبرنامهٔ فوتر (لایووایر ۴) --}}
{{-- فاز ۲۰۱ —
     • متن «در حال ثبت…» با CSS و :disabled جابه‌جا می‌شود (wire:loading.attr="disabled"
       با پایان هر درخواست قطعاً برمی‌گردد) — قبلاً span های wire:loading.remove
       بعد از مورف DOM گیر می‌کردند و دکمه روی «در حال ثبت…» می‌ماند.
     • پیام موفقیت/خطا با کاستوم‌تست تم (@script + dispatch رویدادهای مرورگری).
     • عنوان دکمه سفید (style.css فاز ۲۰۱). --}}
<form wire:submit="submit" class="footer-newsletter-form mt-3 d-flex gap-2 nl-form" novalidate>
    <input type="text"
           name="contact"
           wire:model="contact"
           class="form-control rounded-pill border-0 @error('contact') is-invalid @enderror"
           placeholder="آدرس ایمیل یا شماره موبایل خود را وارد کنید"
           aria-label="ایمیل یا شماره موبایل برای خبرنامه">
    <button class="btn main-color-one-bg rounded-pill border-0" type="submit"
            wire:loading.attr="disabled" wire:target="submit">
        <span class="nl-label">ارسال</span>
        <span class="nl-busy" aria-hidden="true">در حال ثبت…</span>
    </button>

    @error('contact')
        <div class="saniar-newsletter-error text-danger w-100 small mt-1">{{ $message }}</div>
    @enderror
</form>

<style>
    /* جابه‌جایی متن دکمه فقط با وضعیت disabled — مستقل از مورف DOM */
    .footer-newsletter-form .nl-busy { display: none; }
    .footer-newsletter-form button:disabled .nl-label { display: none; }
    .footer-newsletter-form button:disabled .nl-busy { display: inline; }
    .footer-newsletter-form button { color: #fff !important; }
    .footer-newsletter-form button:disabled { opacity: .75; cursor: wait; }
</style>

@script
<script>
    $wire.on('newsletter-subscribed', () => {
        if (typeof window.showCustomToast === 'function') {
            window.showCustomToast('عضویت شما در خبرنامه با موفقیت ثبت شد', 'success');
        }
    });

    $wire.on('newsletter-error', (params) => {
        /* پارامتر بسته به نسخهٔ لایووایر آبجکت یا آرایهٔ تک‌عضوی می‌آید */
        var payload = Array.isArray(params) ? (params[0] || {}) : (params || {});
        var message = payload.message || 'خطایی رخ داده است؛ دوباره تلاش کنید';
        if (typeof window.showCustomToast === 'function') {
            window.showCustomToast(message, 'error');
        }
    });
</script>
@endscript
