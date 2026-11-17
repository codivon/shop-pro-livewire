<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Seller;
use App\Models\Tag;
use App\Models\Form;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    protected $sitemapPath = 'sitemap.xml';
    protected $sitemapDir = 'sitemap/';

    public function __construct()
    {
        // ایجاد پوشه sitemap اگر وجود ندارد
        if (!File::exists(public_path($this->sitemapDir))) {
            File::makeDirectory(public_path($this->sitemapDir), 0755, true);
        }
    }

    public function index()
    {
        // اگر فایل sitemap.xml وجود دارد و کمتر از 24 ساعت از ایجاد آن گذشته، فایل را برگردان
        $sitemapFile = public_path($this->sitemapPath);

        if (File::exists($sitemapFile) && (time() - File::lastModified($sitemapFile)) < 86400) {
            return response()->file($sitemapFile, ['Content-Type' => 'application/xml']);
        }

        // اگر فایل وجود ندارد یا منقضی شده، دوباره تولید کن
        return $this->generateSitemapIndex();
    }

    public function generateSitemapIndex()
    {
        $sitemapIndex = SitemapIndex::create();

        // محصولات (با Paginate برای تعداد زیاد)
        $productCount = Product::where('published', true)->count();
        $perPage = 1000;
        $pages = ceil($productCount / $perPage);

        for ($i = 1; $i <= $pages; $i++) {
            $sitemapIndex->add(url($this->sitemapDir . 'products-' . $i . '.xml'));
        }

        // مقالات
        $postCount = Post::published()->where('published', true)->count();
        $pages = ceil($postCount / $perPage);

        for ($i = 1; $i <= $pages; $i++) {
            $sitemapIndex->add(url($this->sitemapDir . 'articles-' . $i . '.xml'));
        }

        // سایر sitemap ها
        // (product_tags حذف شد؛ در فرانت صفحه‌ی اختصاصی برچسب محصول وجود ندارد و روت آن تعریف نشده است)
        $sitemapIndex->add(url($this->sitemapDir . 'statics.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'product_categories.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'article_categories.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'article_tags.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'pages.xml'));
        //$sitemapIndex->add(url($this->sitemapDir . 'forms.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'stores.xml'));
        $sitemapIndex->add(url($this->sitemapDir . 'brands.xml'));

        $content = $sitemapIndex->render();

        // ذخیره در فایل
        File::put(public_path($this->sitemapPath), $content);

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    // ==================== محصولات ====================
    public function products($page = 1)
    {
        $cacheKey = 'sitemap_products_' . $page;

        // اگر کش وجود دارد و کمتر از 24 ساعت گذشته
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $perPage = 1000;
        $products = Product::where('published', true)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->latest('updated_at')
            ->get();

        $sitemap = Sitemap::create();

        foreach ($products as $product) {
            // روتِ محصولات با اسلاگ بایند می‌شود؛ رکورد بدون اسلاگ آدرس مرده تولید می‌کند و باید حذف شود
            if (empty($product->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.products.show', ['product' => $product->slug]))
                    ->setLastModificationDate($product->updated_at ?? $product->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.9);
            } catch (\Throwable $e) {
                continue;
            }

            if ($product->image) {
                $url->addImage(asset($product->image), $product->title);
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        // ذخیره در فایل
        File::put(public_path($this->sitemapDir . 'products-' . $page . '.xml'), $content);

        // ذخیره در کش برای 24 ساعت
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== مقالات ====================
    public function articles($page = 1)
    {
        $cacheKey = 'sitemap_articles_' . $page;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $perPage = 1000;
        $posts = Post::published()
            ->where('published', true)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->latest('updated_at')
            ->get();

        $sitemap = Sitemap::create();

        foreach ($posts as $post) {
            // روتِ مقالات blog/articles/{slug} است و با اسلاگ بایند می‌شود؛
            // کلیدِ آرایه باید «slug» باشد نه «post» (خطای Missing parameter: slug)
            if (empty($post->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.articles.show', ['slug' => $post->slug]))
                    ->setLastModificationDate($post->updated_at ?? $post->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8);
            } catch (\Throwable $e) {
                continue;
            }

            if ($post->image) {
                $url->addImage(asset($post->image), $post->title);
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'articles-' . $page . '.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== صفحات استاتیک ====================
    public function statics()
    {
        $cacheKey = 'sitemap_statics';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        // فقط روت‌هایی که واقعاً در فرانت تعریف شده‌اند (روت‌های ناموجود باعث 500 شدن کل sitemap می‌شدند)
        $staticPages = [
            ['route' => 'front.index', 'priority' => 1.0, 'freq' => Url::CHANGE_FREQUENCY_DAILY],
            ['route' => 'front.products.index', 'priority' => 0.9, 'freq' => Url::CHANGE_FREQUENCY_DAILY],
            ['route' => 'front.blog.index', 'priority' => 0.8, 'freq' => Url::CHANGE_FREQUENCY_DAILY],
            ['route' => 'front.articles.index', 'priority' => 0.8, 'freq' => Url::CHANGE_FREQUENCY_DAILY],
            ['route' => 'front.contact.index', 'priority' => 0.7, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['route' => 'front.showStore', 'priority' => 0.6, 'freq' => Url::CHANGE_FREQUENCY_WEEKLY],
        ];

        foreach ($staticPages as $page) {
            if (!Route::has($page['route'])) {
                continue;
            }

            try {
                $url = Url::create(route($page['route']))
                    ->setLastModificationDate(Carbon::now())
                    ->setChangeFrequency($page['freq'])
                    ->setPriority($page['priority']);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'statics.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== دسته‌بندی محصولات ====================
    public function productCategories()
    {
        $cacheKey = 'sitemap_product_categories';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        $categories = Category::where('type', 'productcat')
            ->where('published', true)
            ->get();

        foreach ($categories as $category) {
            if (empty($category->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.products.category', ['category' => $category->slug]))
                    ->setLastModificationDate($category->updated_at ?? $category->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'product_categories.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== برچسب‌های محصولات ====================
    public function productTags()
    {
        $cacheKey = 'sitemap_product_tags';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        // روت front.products.tag در فرانت تعریف نشده است؛ اگر روزی اضافه شد این sitemap
        // به‌طور خودکار پر می‌شود و در غیر این صورت یک sitemap خالی (و معتبر) برمی‌گرداند.
        if (!Route::has('front.products.tag')) {
            $content = $sitemap->render();

            File::put(public_path($this->sitemapDir . 'product_tags.xml'), $content);
            Cache::put($cacheKey, $content, 60 * 24);

            return $content;
        }

        $tags = Tag::whereHas('products')->get();

        foreach ($tags as $tag) {
            if (empty($tag->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.products.tag', ['tag' => $tag->slug]))
                    ->setLastModificationDate($tag->updated_at ?? $tag->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.6);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'product_tags.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

// ==================== دسته‌بندی مقالات ====================
    public function articleCategories()
    {
        $cacheKey = 'sitemap_article_categories';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        $categories = Category::where('type', 'postcat')
            ->where('published', true)
            ->get();

        foreach ($categories as $category) {
            // ساخت آدرس با پارامتر cat
            $url = Url::create(route('front.articles.index') . '?cat=' . ($category->slug ?? $category->id))
                ->setLastModificationDate($category->updated_at ?? $category->created_at ?? Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7);

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'article_categories.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }
    // ==================== برچسب‌های مقالات ====================
    public function articleTags()
    {
        $cacheKey = 'sitemap_article_tags';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        $tags = Tag::whereHas('posts')->get();

        foreach ($tags as $tag) {
            if (empty($tag->slug)) {
                continue;
            }

            // ساخت آدرس با پارامتر tag
            $url = Url::create(url('/blog/articles?tag=' . $tag->slug))
                ->setLastModificationDate($tag->updated_at ?? $tag->created_at ?? Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.6);

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'article_tags.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== صفحات ====================
    public function pages()
    {
        $cacheKey = 'sitemap_pages';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        $pages = Page::where('published', true)
            ->latest('updated_at')
            ->get();

        foreach ($pages as $page) {
            if (empty($page->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.pages.show', ['page' => $page->slug]))
                    ->setLastModificationDate($page->updated_at ?? $page->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8);
            } catch (\Throwable $e) {
                continue;
            }

            if ($page->image) {
                $url->addImage(asset($page->image), $page->title);
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'pages.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== فرم‌ها ====================
    public function forms()
    {
        $cacheKey = 'sitemap_forms';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        // روت front.forms.show در فرانت تعریف نشده؛ همین گارد از 500 شدن جلوگیری می‌کند
        if (!Route::has('front.forms.show')) {
            $content = $sitemap->render();

            File::put(public_path($this->sitemapDir . 'forms.xml'), $content);
            Cache::put($cacheKey, $content, 60 * 24);

            return $content;
        }

        $forms = Form::where('published', true)->get();

        foreach ($forms as $form) {
            if (empty($form->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.forms.show', ['form' => $form->slug]))
                    ->setLastModificationDate($form->updated_at ?? $form->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.5);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'forms.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

// ==================== فروشگاه‌ها (فروشندگان) ====================
    public function stores()
    {
        $cacheKey = 'sitemap_stores';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        // دریافت فروشندگان فعال
        $sellers = Seller::where('status', 'ACTIVE')
            ->whereHas('seller_info')
            ->where('status_register', 'complete')
            ->where('status_documents', 'Accept')
            ->where('status_work', 'ACTIVE')
            ->get();

        foreach ($sellers as $seller) {
            if (empty($seller->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.showSellerStore', ['seller' => $seller->slug]))
                    ->setLastModificationDate($seller->updated_at ?? $seller->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'stores.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }
    // ==================== برندها ====================
    public function brands()
    {
        $cacheKey = 'sitemap_brands';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sitemap = Sitemap::create();

        $brands = Brand::get();

        foreach ($brands as $brand) {
            if (empty($brand->slug)) {
                continue;
            }

            try {
                $url = Url::create(route('front.brands.show', ['brand' => $brand->slug]))
                    ->setLastModificationDate($brand->updated_at ?? $brand->created_at ?? Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.6);
            } catch (\Throwable $e) {
                continue;
            }

            $sitemap->add($url);
        }

        $content = $sitemap->render();

        File::put(public_path($this->sitemapDir . 'brands.xml'), $content);
        Cache::put($cacheKey, $content, 60 * 24);

        return $content;
    }

    // ==================== تولید همه Sitemap ها ====================
    public function generateAll()
    {
        // پاک کردن کش قبلی
        Cache::flush();

        // تولید تمام sitemap ها
        $this->generateSitemapIndex();

        // تولید محصولات
        $productCount = Product::where('published', true)->count();
        $perPage = 1000;
        $pages = ceil($productCount / $perPage);
        for ($i = 1; $i <= $pages; $i++) {
            $this->products($i);
        }

        // تولید مقالات
        $postCount = Post::published()->where('published', true)->count();
        $pages = ceil($postCount / $perPage);
        for ($i = 1; $i <= $pages; $i++) {
            $this->articles($i);
        }

        // تولید sitemap های دیگر
        $this->statics();
        $this->productCategories();
        $this->articleCategories();
        $this->articleTags();
        $this->pages();
        //$this->forms();
        $this->stores();
        $this->brands();

        return response()->json([
            'success' => true,
            'message' => 'همه sitemap ها با موفقیت تولید شدند',
            'files' => [
                'sitemap.xml' => url('/sitemap.xml'),
                'sitemap/products-*.xml' => url('/sitemap/products-1.xml'),
                'sitemap/articles-*.xml' => url('/sitemap/articles-1.xml'),
            ]
        ]);
    }
}
