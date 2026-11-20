<?php

namespace App\Services;

/**
 * Registry مرکزی برای تعریف ویجت‌های قابل انتخاب در پنل ادمین
 *
 * هر ماژول می‌تونه ویجت‌های خودش رو با متادیتای کامل (title, image, options, rules)
 * برای یک "صفحه" خاص (home, posts, products, ...) ثبت کنه.
 */
class WidgetTemplateRegistry
{
    /**
     * @var array<string, array<string, array>> لیست ویجت‌های ثبت‌شده
     *   ساختار: [page => [key => config]]
     */
    protected static array $widgets = [];

    /**
     * ثبت یک ویجت برای یک صفحه‌ی مشخص
     *
     * @param string $page    نوع صفحه: 'home', 'posts', 'products', ...
     * @param string $key     کلید یکتا ویجت (مثلاً 'main-story')
     * @param array  $config  شامل title, image, options, rules
     * @param string $module  نام ماژول (مثلاً 'Story')
     */
    public static function register(string $page, string $key, array $config, string $module): void
    {
        // فاز ۱۸۹ — نرمال‌سازی ساختار config ماژول (دقیقاً مثل configهای تم)
        $config = self::normalizeTemplate($config, $key);

        // اگر image از نوع relative هست، URL کامل بساز
        $imageUrl = $config['image'] ?? null;
        if ($imageUrl && !preg_match('#^https?://#', $imageUrl)) {
            $imageUrl = module_asset($module, $config['image']);
        }

        if (!isset(static::$widgets[$page])) {
            static::$widgets[$page] = [];
        }

        static::$widgets[$page][$key] = array_merge($config, [
            'key'       => $key,
            'module'    => $module,
            'page'      => $page,
            'image_url' => $imageUrl,
        ]);
    }

    /* ===================================================================
     *  فاز ۱۸۹ — نرمال‌سازی مقادیر اسکالر config ابزارک
     * ===================================================================
     *
     * ریشهٔ خطای «Array to string conversion» هنگام ویرایش/تغییر نوع ابزارک:
     * برخی مقادیر config تم/ماژول (مثل attributes، class، default، title یا
     * items یک select) ممکن است به‌جای رشته/عدد، آرایه باشند (خطای تایپ در
     * config تم یا مقدار جدید اضافه‌شده). در Laravel هشدار PHP به ErrorException
     * تبدیل می‌شود ⇒ هر echo آرایه در فرم ابزارک = خطای ۵۰۰ «Array to string
     * conversion» و فرم ابزارک می‌میرد.
     *
     * راه‌حل: همهٔ مقادیر اسکالرِ مورد انتظار، در «نقطهٔ واحد» همین رجیستری
     * نرمال می‌شوند — هرچی آرایه باشد به JSON خوانا تبدیل می‌شود تا هیچ‌گاه
     * آرایه‌ای به فرم/ویو/DB نرسد. ساختارهای مجاز آرایه‌ای (options هر option
     * و rules) حفظ می‌شوند ولی خودِ آیتم‌هایشان اسکالر نرمال می‌شوند.
     */

    /**
     * تبدیل امن هر مقدار به رشتهٔ قابل‌نمایش — هرگز آرایه را به رشتهٔ PHP
     * تبدیل نمی‌کند (که «Array to string conversion» می‌دهد)؛ آرایه → JSON.
     */
    public static function scalarString(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }

    /**
     * مقدار اسکالر یا null — برای فیلدهای اختیاری مثل type/default.
     * آرایه‌ها به JSON تبدیل می‌شوند (نه حذف) تا اطلاعات از بین نرود.
     */
    public static function scalarOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return self::scalarString($value);
        }

        return $value;
    }

    /**
     * نرمال‌سازی کامل config یک ابزارک (تم یا ماژول) — فاز ۱۸۹.
     * این تابع تنها نقطهٔ ورود مقادیر فرم ابزارک است؛ بعد از آن هیچ
     * مقدار آرایه‌ای در فیلدهای اسکالر باقی نمی‌ماند.
     */
    public static function normalizeTemplate(array $config, string $key): array
    {
        // فیلدهای متنی سطح بالا
        foreach (['title', 'image', 'module'] as $field) {
            if (array_key_exists($field, $config)) {
                $config[$field] = self::scalarOrNull($config[$field]);
            }
        }

        // options: هر option باید آرایه باشد؛ فیلدهای اسکالرش نرمال می‌شوند
        $normalizedOptions = [];

        foreach ($config['options'] ?? [] as $option) {
            if (! is_array($option) || ! isset($option['key']) || is_array($option['key'])) {
                continue; // entry معیوب — به‌جای کرش، حذف بی‌صدا
            }

            $optionKey = (string) $option['key'];

            $normalized = [
                'key'         => $optionKey,
                'title'       => self::scalarString($option['title'] ?? $optionKey),
                'input-type'  => self::scalarString($option['input-type'] ?? 'input'),
            ];

            // فیلدهای اختیاری اسکالر
            foreach (['type', 'default', 'class', 'attributes', 'help'] as $field) {
                if (array_key_exists($field, $option)) {
                    $normalized[$field] = self::scalarOrNull($option[$field]);
                }
            }

            // هر فیلد ناشناختهٔ دیگر هم اسکالر نرمال شود (مقاوم به configهای جدید)
            foreach ($option as $field => $value) {
                if (! array_key_exists($field, $normalized) && $field !== 'options' && $field !== 'rules') {
                    $normalized[$field] = self::scalarOrNull($value);
                }
            }

            // آیتم‌های select: ['value' => scalar, 'title' => scalar]
            if (isset($option['options']) && is_array($option['options'])) {
                $items = [];

                foreach ($option['options'] as $item) {
                    // ساختار استاندارد: ['value' => .., 'title' => ..]
                    if (is_array($item) && ! isset($item['value']) && isset($item[0])) {
                        // ساختار تخت: ['asc', 'صعودی'] → تبدیل به ساختار استاندارد
                        $item = ['value' => $item[0], 'title' => $item[1] ?? $item[0]];
                    } elseif (! is_array($item)) {
                        // ساختار خیلی تخت: 'asc' → خودش value است
                        $item = ['value' => $item, 'title' => self::scalarString($item)];
                    }

                    $items[] = [
                        'value' => self::scalarOrNull($item['value'] ?? null),
                        'title' => self::scalarString($item['title'] ?? ($item['value'] ?? '')),
                    ];
                }

                $normalized['options'] = $items;
            } elseif (isset($option['options'])) {
                // فاز ۱۸۹ — options معیوب (رشته/عدد) → خالی، تا @foreach ویو نمرده باشد
                $normalized['options'] = [];
            }

            // rules باید آرایه بماند (validation) — فقط نام‌کلیدها اسکالر می‌مانند
            if (isset($option['rules']) && is_array($option['rules'])) {
                $normalized['rules'] = $option['rules'];
            }

            $normalizedOptions[] = $normalized;
        }

        $config['options'] = $normalizedOptions;

        // rules سطح تمپلیت باید آرایه بماند
        if (! isset($config['rules']) || ! is_array($config['rules'])) {
            $config['rules'] = [];
        }

        return $config;
    }

    /**
     * ثبت گروهی ویجت‌ها برای یک صفحه
     *
     * @param string $page     نوع صفحه
     * @param array  $widgets  آرایه‌ی ویجت‌ها (key => config)
     * @param string $module   نام ماژول
     */
    public static function registerMany(string $page, array $widgets, string $module): void
    {
        foreach ($widgets as $key => $config) {
            static::register($page, $key, $config, $module);
        }
    }

    /**
     * دریافت یک ویجت با کلید (از همه‌ی صفحه‌ها جستجو می‌کنه)
     */
    public static function get(string $key): ?array
    {
        foreach (static::$widgets as $page => $widgets) {
            if (isset($widgets[$key])) {
                return $widgets[$key];
            }
        }
        return null;
    }

    /**
     * دریافت یک ویجت برای یک صفحه‌ی مشخص
     */
    public static function getForPage(string $page, string $key): ?array
    {
        return static::$widgets[$page][$key] ?? null;
    }

    /**
     * دریافت همه‌ی ویجت‌های ثبت‌شده توسط ماژول‌ها برای یک صفحه‌ی مشخص
     *
     * @return array<string, array>
     */
    public static function allForPage(string $page): array
    {
        return static::$widgets[$page] ?? [];
    }

    /**
     * دریافت همه‌ی ویجت‌های ثبت‌شده توسط ماژول‌ها (همه‌ی صفحه‌ها)
     *
     * @return array<string, array<string, array>>
     */
    public static function all(): array
    {
        return static::$widgets;
    }

    /**
     * بررسی وجود ویجت (در همه‌ی صفحه‌ها)
     */
    public static function has(string $key): bool
    {
        foreach (static::$widgets as $page => $widgets) {
            if (isset($widgets[$key])) {
                return true;
            }
        }
        return false;
    }

    /**
     * بررسی وجود ویجت برای یک صفحه‌ی مشخص
     */
    public static function hasForPage(string $page, string $key): bool
    {
        return isset(static::$widgets[$page][$key]);
    }

    /* ===================================================================
     *  متدهای کمکی برای ترکیب با configهای اصلی پروژه
     * =================================================================== */

    /**
     * دریافت همه‌ی کلیدهای مجاز برای یک صفحه (برای validation)
     * شامل: home-widgets (یا posts-widgets) + module widgets
     *
     * @return array<string>
     */
    public static function allKeysForPage(string $page): array
    {
        $coreConfig = static::getCoreConfigForPage($page);
        $moduleWidgets = static::allForPage($page);

        $keys = array_merge(
            array_keys($coreConfig),
            array_keys($moduleWidgets)
        );
        return array_unique($keys);
    }

    /**
     * دریافت config یک ویجت برای یک صفحه (از ماژول یا core)
     *
     * @return array|null
     */
    public static function findConfigForPage(string $page, string $key): ?array
    {
        // 1) ویجت‌های ماژول‌ها (اولویت اول)
        if (static::hasForPage($page, $key)) {
            return static::getForPage($page, $key);
        }

        // 2) configهای اصلی پروژه
        $coreConfig = static::getCoreConfigForPage($page);
        return $coreConfig[$key] ?? null;
    }

    /**
     * دریافت همه‌ی ویجت‌ها (هم اصلی و هم ماژول) برای یک صفحه
     *
     * @return array<string, array>
     */
    public static function mergedForPage(string $page): array
    {
        $coreConfig = static::getCoreConfigForPage($page);
        $moduleWidgets = static::allForPage($page);
        return array_merge($coreConfig, $moduleWidgets);
    }

    /**
     * نگاشت page → config key در پروژه اصلی
     *
     * @return array
     */
    protected static function getCoreConfigForPage(string $page): array
    {
        $mapping = [
            'home'     => 'front.home-widgets',
            'posts'    => 'front.posts-widgets',
            'products' => 'front.products-widgets', // اگه دارید
        ];

        $configKey = $mapping[$page] ?? "front.{$page}-widgets";

        $configs = config($configKey, []);

        // فاز ۱۸۹ — نرمال‌سازی configهای تم (منبع خطای Array to string conversion
        // وقتی config تم مقدار آرایه‌ای در فیلد اسکالر دارد)
        if (! is_array($configs)) {
            return [];
        }

        $normalized = [];

        foreach ($configs as $key => $config) {
            if (! is_string($key) || $key === '' || ! is_array($config)) {
                continue;
            }

            $normalized[$key] = self::normalizeTemplate($config, $key);
        }

        return $normalized;
    }

    /**
     * دریافت همه‌ی ویجت‌ها (هم اصلی و هم ماژول) برای یک صفحه
     * به‌صورت یک آرایه‌ی مسطح با metadata کامل (شامل source و module)
     *
     * @return array<int, array>  هر آیتم شامل: key, title, image, source, module
     */
    public static function listForPage(string $page): array
    {
        $result = [];
        $coreConfig = static::getCoreConfigForPage($page);
        $moduleWidgets = static::allForPage($page);

        // ویجت‌های اصلی پروژه
        foreach ($coreConfig as $key => $config) {
            $result[] = [
                'key'       => $key,
                'title'     => $config['title'] ?? $key,
                'image'     => isset($config['image']) ? theme_asset($config['image']) : null,
                'source'    => 'core',
                'module'    => null,
            ];
        }

        // ویجت‌های ماژول‌ها
        foreach ($moduleWidgets as $key => $config) {
            $result[] = [
                'key'       => $key,
                'title'     => $config['title'] ?? $key,
                'image'     => $config['image_url'] ?? null,
                'source'    => 'module',
                'module'    => $config['module'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * پاک کردن همه (برای تست)
     */
    public static function clear(): void
    {
        static::$widgets = [];
    }
}
