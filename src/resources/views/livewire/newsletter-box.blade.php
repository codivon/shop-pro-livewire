{{-- SaniarTheme — فرم خبرنامهٔ فوتر (لایووایر ۴) --}}
<form wire:submit="submit" class="footer-newsletter-form mt-3 d-flex gap-2" novalidate>
    <input type="text"
           name="contact"
           wire:model="contact"
           class="form-control rounded-pill border-0 @error('contact') is-invalid @enderror"
           placeholder="آدرس ایمیل یا شماره موبایل خود را وارد کنید"
           aria-label="ایمیل یا شماره موبایل برای خبرنامه">
    <button class="btn main-color-one-bg rounded-pill border-0" type="submit"
            wire:loading.attr="disabled" wire:target="submit">
        <span wire:loading.remove wire:target="submit">ارسال</span>
        <span wire:loading wire:target="submit">در حال ثبت…</span>
    </button>

    @error('contact')
        <div class="saniar-newsletter-error text-danger w-100 small mt-1">{{ $message }}</div>
    @enderror

    @if($done)
        <div class="saniar-newsletter-success w-100 small mt-1" style="color: var(--main-color-one, #3ecaf6);">
            عضویت شما در خبرنامه با موفقیت ثبت شد
        </div>
    @endif
</form>
