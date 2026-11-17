<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Sitemap\Sitemap;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap files';

    public function handle()
    {
        // Create the main sitemap with all URLs.
        $sitemap = Sitemap::create();

        $now = Carbon::now()->toW3cString();

        // Add static pages.
        $sitemap->add(url('/'), $now, '1.0', 'daily');
        $sitemap->add(url('/contact'), $now, '0.8', 'monthly');

        // Get published records once and reuse them.
        $posts = Post::published()->latest('updated_at')->get();
        $pages = Page::where('published', true)->latest('updated_at')->get();
        $products = Product::published()->latest('updated_at')->get();

        // Add posts to the main sitemap.
        foreach ($posts as $post) {
            if (empty($post->slug)) {
                continue;
            }

            $sitemap->add(
                route('front.articles.show', $post),
                optional($post->updated_at)->toW3cString() ?? $now,
                '0.9',
                'weekly'
            );
        }

        // Add pages to the main sitemap.
        foreach ($pages as $page) {
            if (empty($page->slug)) {
                continue;
            }

            $sitemap->add(
                route('front.pages.show', $page),
                optional($page->updated_at)->toW3cString() ?? $now,
                '0.9',
                'weekly'
            );
        }

        // Add products to the main sitemap.
        foreach ($products as $product) {
            if (empty($product->slug)) {
                continue;
            }

            $sitemap->add(
                route('front.products.show', $product),
                optional($product->updated_at)->toW3cString() ?? $now,
                '0.9',
                'daily'
            );
        }

        // Store the main sitemap.
        Storage::disk('public')->put('sitemap.xml', $sitemap->render());

        // Create and store posts sitemap.
        $postsSitemap = Sitemap::create();

        foreach ($posts as $post) {
            if (empty($post->slug)) {
                continue;
            }

            $postsSitemap->add(
                route('front.articles.show', $post),
                optional($post->updated_at)->toW3cString() ?? $now,
                '0.9',
                'weekly'
            );
        }

        Storage::disk('public')->put(
            'sitemap-posts.xml',
            $postsSitemap->render()
        );

        // Create and store pages sitemap.
        $pagesSitemap = Sitemap::create();

        foreach ($pages as $page) {
            if (empty($page->slug)) {
                continue;
            }

            $pagesSitemap->add(
                route('front.pages.show', $page),
                optional($page->updated_at)->toW3cString() ?? $now,
                '0.9',
                'weekly'
            );
        }

        Storage::disk('public')->put(
            'sitemap-pages.xml',
            $pagesSitemap->render()
        );

        // Create and store products sitemap.
        $productsSitemap = Sitemap::create();

        foreach ($products as $product) {
            if (empty($product->slug)) {
                continue;
            }

            $productsSitemap->add(
                route('front.products.show', $product),
                optional($product->updated_at)->toW3cString() ?? $now,
                '0.9',
                'daily'
            );
        }

        Storage::disk('public')->put(
            'sitemap-products.xml',
            $productsSitemap->render()
        );

        Log::info('Sitemap files generated successfully.', [
            'posts_count' => $posts->count(),
            'pages_count' => $pages->count(),
            'products_count' => $products->count(),
        ]);

        return self::SUCCESS;
    }
}
