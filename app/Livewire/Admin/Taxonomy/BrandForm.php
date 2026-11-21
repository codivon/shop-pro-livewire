<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Brand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('admin-panel::layout')]
#[Title('ویرایش برند')]
class BrandForm extends Component
{
    use WithFileUploads;

    public Brand $brand;

    /* ---------------- فیلدهای فرم ---------------- */

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    /** فاز ۱۹۱ — «نمایش در صفحهٔ اصلی»: فقط برندهای انتخاب‌شده در ویجت برندهای صفحهٔ اصلی نمایش داده می‌شوند */
    public bool $show_on_home = false;

    /** تصویر جدید (اختیاری — در صورت انتخاب، تصویر قبلی حذف می‌شود مثل قدیمی) */
    public $image = null;

    /** فاز ۱۹۱ — آیا ستون show_on_home روی جدول brands موجود است؟
     *  (ادمین ستون را دستی اضافه می‌کند؛ تا قبل از آن، فرم/لیست نباید کرش کند) */
    public static function showOnHomeColumnExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            try {
                $exists = Schema::hasColumn((new Brand)->getTable(), 'show_on_home');
            } catch (\Throwable) {
                $exists = false;
            }
        }

        return $exists;
    }

    public function mount(Brand $brand): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $this->brand = $brand;
        $this->name = (string) $brand->name;
        $this->slug = (string) $brand->slug;
        $this->description = (string) $brand->description;

        /* فاز ۱۹۱ — مقدار فعلی «نمایش در صفحهٔ اصلی» */
        $this->show_on_home = self::showOnHomeColumnExists() && (bool) $brand->show_on_home;
    }

    public function generateSlug(): void
    {
        // اسلاگ فارسی (Str::slug حروف فارسی را حذف می‌کرد)
        $this->slug = sluggable_helper_function($this->name) ?: trim($this->name);
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $data = $this->validate([
            'name'        => 'required|unique:brands,name,' . $this->brand->id,
            'slug'        => 'required|unique:brands,slug,' . $this->brand->id,
            'description' => 'nullable|string',
        ], [], [
            'name'        => 'نام',
            'slug'        => 'آدرس (slug)',
            'description' => 'توضیحات',
        ]);

        $data['description'] = $this->description ?: null;

        /* فاز ۱۹۱ — «نمایش در صفحهٔ اصلی»؛ فقط وقتی ستون موجود است ذخیره می‌شود */
        if (self::showOnHomeColumnExists()) {
            $data['show_on_home'] = $this->show_on_home;
        }

        if ($this->image) {
            // حذف تصویر قبلی مثل قدیمی (با نرمال‌سازی اسلش ابتدای مسیر)
            if ($this->brand->image) {
                Storage::disk('public')->delete(ltrim($this->brand->image, '/'));
            }

            // الگوی ذخیره قدیمی حفظ شد: public/uploads/brands/{uniqid}_{time}.{ext} + مسیر /uploads/brands/ در DB
            $name = uniqid() . '_' . time() . '.' . $this->image->getClientOriginalExtension();
            $this->image->storeAs('brands', $name);

            $data['image'] = '/uploads/brands/' . $name;
        }

        $this->brand->update($data);

        session()->flash('np-toast', ['type' => 'success', 'message' => 'برند با موفقیت ویرایش شد.']);

        $this->redirectIntended(route('np.brands.index', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.brand-form');
    }
}
