{{-- SaniarTheme — دکمهٔ علاقه‌مندی محصول (لایووایر ۴) --}}
{{-- مارکاپ هم‌قرارداد با دکمهٔ قبلی تم (btn-option/btn-option-wishes/tooltip-short)
     است تا CSS صفحهٔ محصول بدون تغییر کار کند. wire:click حالت را درجا عوض می‌کند. --}}
<button type="button"
        wire:key="saniar-favorites-{{ $productId }}"
        class="btn-option btn-option-wishes bg-transparent border-0 p-0 {{ $isFavorite ? 'btn-option-favorites' : '' }}"
        data-product="{{ $productId }}"
        data-wire-favorites="1"
        wire:click="toggle"
        wire:loading.attr="disabled"
        wire:target="toggle"
        aria-label="{{ $isFavorite ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی' }}"
        aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
    <i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
    <span class="tooltip-short">{{ $isFavorite ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی' }}</span>
</button>
