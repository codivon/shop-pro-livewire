{{-- آیتم دسته‌بندی داخل لیست‌های منو (sub-menu mega-container) --}}
{{-- فاز ۱۸۵ — هر زیرمنو «زیر زیرمنوی پدرش» می‌آید: دسته‌ای که خودش فرزند دارد
     به‌صورت گروه با تیتر پررنگ + لیست تو در تو از فرزندان رندر می‌شود --}}
@if(!$category->getCategoriesCount())
    <li><a href="{{ $category->link }}">{{ $category->title }}</a></li>
@else
    <li class="snr-sub-group">
        <a class="title" href="{{ $category->link }}">{{ $category->title }}</a>
        <ul class="snr-sub-group-list">
            @foreach (front_category_children($category) as $childCategory)
                @include('front::partials.menu.child-category', ['category' => $childCategory])
            @endforeach
        </ul>
    </li>
@endif
