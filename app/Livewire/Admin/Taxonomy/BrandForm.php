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

    /** فاز ۱۹۲ — ترتیب نمایش برند (ویجت برندهای صفحهٔ اصلی + لیست ادمین) */
    public int $ordering = 0;

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

    /** فاز ۱۹۲ — آیا ستون ordering روی جدول brands موجود است؟
     *  (تا قبل از اجرای مایگریشن ویرایش‌شده، فرم/لیست نباید کرش کند) */
    public static function orderingColumnExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            try {
                $exists = Schema::hasColumn((new Brand)->getTable(), 'ordering');
            } catch (\Throwable) {
                $exists = false;
            }
        }

        return $exists;
    }

    public function mount(Brand $brand): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        /* فاز ۱۹۶ — خودترمیمی پرم فایل‌های تصویر برندها (فایل هست ولی وب 404 می‌داد) */
        shop_heal_brands_upload_permissions();

        $this->brand = $brand;
        $this->name = (string) $brand->name;
        $this->slug = (string) $brand->slug;
        $this->description = (string) $brand->description;

        /* فاز ۱۹۱ — مقدار فعلی «نمایش در صفحهٔ اصلی» */
        $this->show_on_home = self::showOnHomeColumnExists() && (bool) $brand->show_on_home;

        /* فاز ۱۹۲ — مقدار فعلی ترتیب نمایش */
        $this->ordering = (int) ($brand->ordering ?? 0);
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
            'ordering'    => 'nullable|integer|min:0',
            /* فاز ۱۹۴ — قواعد تصویر: پیام خطای واضح به‌جای آپلود بی‌صدا + سقف ۴MB */
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,svg|max:4096',
        ], [], [
            'name'        => 'نام',
            'slug'        => 'آدرس (slug)',
            'description' => 'توضیحات',
            'ordering'    => 'ترتیب نمایش',
            'image'       => 'تصویر',
        ]);

        $data['description'] = $this->description ?: null;

        /* فاز ۱۹۱ — «نمایش در صفحهٔ اصلی»؛ فقط وقتی ستون موجود است ذخیره می‌شود */
        if (self::showOnHomeColumnExists()) {
            $data['show_on_home'] = $this->show_on_home;
        }

        /* فاز ۱۹۲ — ترتیب نمایش؛ فقط وقتی ستون موجود است ذخیره می‌شود */
        if (self::orderingColumnExists()) {
            $data['ordering'] = max(0, (int) $this->ordering);
        }

        if ($this->image) {
            // فاز ۱۹۴ — تضمین وجود پوشهٔ مقصد؛ آپلود در پوشهٔ غایب بی‌صدا fail می‌شد
            Storage::disk('local')->makeDirectory('brands', 0755, true);

            // فاز ۱۹۲ — دیسک صریح «local» (ریشهٔ public/uploads) تا تغییر
            // FILESYSTEM_DRIVER در .env مسیر ذخیره را از مسیر DB جدا نکند
            $name = uniqid() . '_' . time() . '.' . strtolower($this->image->getClientOriginalExtension() ?: 'png');
            $stored = $this->image->storeAs('brands', $name, 'local');

            if ($stored === false || ! Storage::disk('local')->exists('brands/' . $name)) {
                // فاز ۱۹۲ — آپلود بی‌صدا نباید شکست بخورد؛ مسیر خراب در DB ذخیره نشود
                report(new \RuntimeException('برند: ذخیرهٔ تصویر در public/uploads/brands شکست خورد (دسترسی نوشتن پوشه را بررسی کنید). مسیر مطلق مقصد: '
                    . Storage::disk('local')->path('brands/' . $name)));

                session()->flash('np-toast', ['type' => 'error', 'message' => 'ذخیرهٔ تصویر برند ناموفق بود؛ دسترسی نوشتن پوشهٔ uploads را بررسی کنید.']);

                return;
            }

            /* فاز ۱۹۶ — ریشهٔ «فایل هست ولی 404 می‌خورد»: فایل/پوشه باید حتماً
               خواندنیِ وب شوند (0644/0755)؛ تضمین قطعی حتی با config:cache کهنه */
            shop_make_upload_web_readable('brands/' . $name);

            $data['image'] = '/uploads/brands/' . $name;

            // فاز ۱۹۴ — حذف تصویر قبلی فقط «بعد از» موفقیت ذخیرهٔ فایل جدید؛
            // اگر قبلاً قبل از ذخیره حذف می‌شد و ذخیره شکست می‌خورد، برند بی‌تصویر می‌شد
            if ($this->brand->image) {
                $relativeImage = preg_replace('#^/?uploads/#', '', ltrim($this->brand->image, '/'));
                Storage::disk('local')->delete($relativeImage);
            }
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
