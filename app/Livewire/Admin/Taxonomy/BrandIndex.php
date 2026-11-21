<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست برندها')]
class BrandIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- کارت «برند جدید» (پورت create/store قدیمی) ---------------- */

    public string $createName = '';

    public string $createSlug = '';

    public string $createDescription = '';

    /** فاز ۱۹۲ — ترتیب نمایش برند جدید */
    public int $createOrdering = 0;

    /** تصویر برند جدید (Livewire TemporaryUploadedFile) */
    public $createImage = null;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        /* فاز ۱۹۶ — خودترمیمی پرم فایل‌های تصویر برندها:
           فایل‌های قبلی با پرم private (فایل 0600/پوشه 0700) ساخته شده بودند و
           وب‌سرور نمی‌توانست بخواند → 404. اولین بازدید این صفحه همه را سالم می‌کند. */
        shop_heal_brands_upload_permissions();
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->detectLang()
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            /* فاز ۱۹۲ — مرتب‌سازی بر اساس ترتیب نمایش (وقتی ستون موجود است)؛
               وگرنه مثل قبل جدیدترین‌ها اول */
            ->when(BrandForm::orderingColumnExists(), fn ($query) => $query->orderBy('ordering')->orderBy('id'), fn ($query) => $query->latest())
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Brand::detectLang()->count();
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- تولید خودکار اسلاگ ---------------- */

    public function generateCreateSlug(): void
    {
        // اسلاگ فارسی (Str::slug حروف فارسی را حذف می‌کرد)
        $this->createSlug = sluggable_helper_function($this->createName) ?: trim($this->createName);
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createBrand(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $data = $this->validate([
            'createName'        => 'required|unique:brands,name',
            'createSlug'        => 'required|unique:brands,slug',
            'createDescription' => 'nullable|string',
            'createImage'       => 'nullable|image|max:2048',
        ], [], [
            'createName'        => 'نام',
            'createSlug'        => 'آدرس (slug)',
            'createDescription' => 'توضیحات',
            'createImage'       => 'تصویر',
        ]);

        $data['name'] = $this->createName;
        $data['slug'] = $this->createSlug;
        $data['description'] = $this->createDescription ?: null;

        /* فاز ۱۹۲ — ترتیب نمایش برند جدید (فقط وقتی ستون موجود است) */
        if (BrandForm::orderingColumnExists()) {
            $data['ordering'] = max(0, (int) $this->createOrdering);
        }

        // فاز ۱۹۲ — دیسک صریح «local» (ریشهٔ public/uploads) + تأیید ذخیره؛
        // آپلود بی‌صدا نباید مسیر خراب در DB بگذارد
        if ($this->createImage) {
            // فاز ۱۹۴ — تضمین وجود پوشهٔ مقصد؛ آپلود در پوشهٔ غایب بی‌صدا fail می‌شد
            Storage::disk('local')->makeDirectory('brands', 0755, true);

            $name = uniqid() . '_' . time() . '.' . strtolower($this->createImage->getClientOriginalExtension() ?: 'png');
            $stored = $this->createImage->storeAs('brands', $name, 'local');

            if ($stored === false || ! Storage::disk('local')->exists('brands/' . $name)) {
                report(new \RuntimeException('برند: ذخیرهٔ تصویر در public/uploads/brands شکست خورد (دسترسی نوشتن پوشه را بررسی کنید). مسیر مطلق مقصد: '
                    . Storage::disk('local')->path('brands/' . $name)));

                $this->dispatch('toast', message: 'ذخیرهٔ تصویر برند ناموفق بود؛ دسترسی نوشتن پوشهٔ uploads را بررسی کنید.', type: 'error');

                return;
            }

            /* فاز ۱۹۶ — ریشهٔ «فایل هست ولی 404 می‌خورد»: دیسک local بدون visibility
               فایل را 0600 و پوشه را 0700 می‌سازد (پیش‌فرض PRIVATE) و وب‌سرور نمی‌تواند
               بخواند. visibility در config حالا public است، اما این chmod قطعیِ
               تضمین است — حتی با config:cache کهنه روی سرور هم پرم درست می‌شود. */
            shop_make_upload_web_readable('brands/' . $name);

            $data['image'] = '/uploads/brands/' . $name;
        }

        $data['lang'] = app()->getLocale();

        Brand::create($data);

        $this->reset(['createName', 'createSlug', 'createDescription', 'createImage', 'createOrdering']);

        $this->dispatch('toast', message: 'برند با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- toggle سریع «نمایش در صفحهٔ اصلی» (فاز ۱۹۱) ---------------- */

    public function toggleShowOnHome(int $id): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        if (! BrandForm::showOnHomeColumnExists()) {
            $this->dispatch('toast', message: 'ستون show_on_home هنوز به جدول برندها اضافه نشده است.', type: 'error');

            return;
        }

        $brand = Brand::find($id);

        if ($brand) {
            $brand->update(['show_on_home' => ! $brand->show_on_home]);

            $this->dispatch('toast', message: $brand->show_on_home
                ? 'برند در صفحهٔ اصلی نمایش داده می‌شود.'
                : 'برند از صفحهٔ اصلی حذف شد.', type: 'success');
        }
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $brand = Brand::find($this->confirmingDelete);

        if ($brand) {
            // فاز ۱۹۳ — مسیر DB «uploads/brands/…» است ولی ریشهٔ دیسک «local»
            // خودِ پوشهٔ uploads؛ پیشوند uploads/ برداشته شود تا حذف واقعی شود
            if ($brand->image) {
                $relativeImage = preg_replace('#^/?uploads/#', '', ltrim($brand->image, '/'));
                Storage::disk('local')->delete($relativeImage);
            }

            // برابری FK قدیمی (products.brand_id onDelete set null) — قابل‌حمل بین SQLite/MySQL
            Product::where('brand_id', $brand->id)->update(['brand_id' => null]);

            $brand->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'برند با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.brands')
            ->layout('admin-panel::layout');
    }
}
