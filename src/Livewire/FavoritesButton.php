<?php

namespace Themes\SaniarTheme\src\Livewire;

use App\Models\Favorite;
use App\Models\Product;
use Livewire\Component;

/**
 * SaniarTheme — دکمهٔ «علاقه‌مندی» صفحهٔ تک‌محصول (لایووایر ۴)
 * ---------------------------------------------------------------------------
 * فاز ۱۸۵ — هر چیزی که لازم دارد داخل خودِ تم است:
 *   • کلاس: Themes\SaniarTheme\src\Livewire\FavoritesButton
 *   • ویو:  front::livewire.favorites-button
 *   • ثبت با نام «saniar-favorites-button» در ThemeServiceProvider
 *
 * رفتار:
 *   • کاربر واردشده → افزودن/حذف درجا (بدون رفرش، state با wire:click)
 *   • مهمان → پیغام + هدایت به صفحهٔ ورود (در SPA با wire:navigate انجام
 *     می‌شود؛ دیگر خطای خام ۴۰۱ «Unauthenticated or Token Expired» در
 *     کنسول دیده نمی‌شود)
 */
class FavoritesButton extends Component
{
    /** شناسهٔ محصول */
    public $productId;

    /** وضعیت فعلی علاقه‌مندی */
    public bool $isFavorite = false;

    public function mount($product)
    {
        $this->productId = $product instanceof Product ? $product->getKey() : (int) $product;
        $this->refreshState();
    }

    private function refreshState(): void
    {
        $user = auth('web')->user();

        $this->isFavorite = false;

        if ($user) {
            $this->isFavorite = Favorite::query()
                ->where('user_id', $user->getKey())
                ->where('product_id', $this->productId)
                ->exists();
        }
    }

    public function toggle()
    {
        $user = auth('web')->user();

        if (! $user) {
            /* مهمان — هدایت به صفحهٔ ورود (Livewire خودش با navigate می‌برد) */
            session()->flash('toast-warning', 'برای افزودن به علاقه‌مندی ابتدا وارد حساب کاربری خود شوید');

            return redirect()->route('login');
        }

        $favorite = Favorite::query()
            ->where('user_id', $user->getKey())
            ->where('product_id', $this->productId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorite = false;
        } else {
            /* همان الگوی هسته — از رابطهٔ کاربر (بدون وابستگی به $fillable) */
            $user->favorites()->create([
                'product_id' => $this->productId,
            ]);
            $this->isFavorite = true;
        }
    }

    public function render()
    {
        return view('front::livewire.favorites-button');
    }
}
