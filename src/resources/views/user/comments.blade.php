@extends('front::user.layouts.master')

@section('user-content')

    {{-- نقد و نظرات — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon is-red" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                <div>
                    <h2>دیدگاه های من</h2>
                    <p>نقدها و نظراتی که برای محصولات ثبت کرده‌اید</p>
                </div>
            </div>
        </div>

        @if($comments->count())
            <div class="orders">
                @foreach($comments as $comment)
                    <div class="order-item">
                        <div class="order-item-status">
                            <div class="order-item-status-item">
                                <a href="{{ $comment->product->link() }}"><img src="{{ $comment->product->image ? asset($comment->product->image) : asset('/no-image-product.svg') }}" alt="{{ $comment->product->title }}" width="80"></a>
                            </div>
                            <div class="order-item-status-item dropd-status">
                                <div class="dropdown">
                                    @if($comment->status == 'pending')
                                        <span class="usp-badge is-warning">منتظر تایید</span>
                                    @elseif($comment->status == 'accepted')
                                        <span class="usp-badge is-success">تایید شده</span>
                                    @else
                                        <span class="usp-badge is-danger">تایید نشده</span>
                                    @endif
                                    <a class="" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical text-dark fs-5"></i>
                                    </a>

                                    <ul class="dropdown-menu flex-column">
                                        <li>
                                            <button class="dropdown-item add-product-review-modal-btn" data-url="{{route('front.reviews.show',$comment->product->slug)}}" data-productId="{{$comment->product->id}}" data-bs-toggle="modal" data-bs-target="#edit-product-review-modal"><i class="bi bi-pencil"></i> ویرایش</button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger comment-remove-btn" data-bs-toggle="modal" data-bs-target="#comment-delete-modal" data-action="{{ route('front.comments.destroy', ['comment' => $comment]) }}"><i class="bi bi-trash text-danger"></i> حذف</button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="order-item-detail border-0">
                            <div class="comment-item">
                                <div class="comment-item-desc">
                                    <div class="comment-item-title">
                                        <div class="product-row-title">
                                            <h6>
                                                <a href="{{ $comment->product->link() }}"> {{ $comment->product->title }}</a>
                                            </h6>
                                            <p class="fw-bold mb-0">{{$comment->title}}</p>
                                        </div>
                                        <div class="comment-item-rating">
                                            <p class="mb-0 text-muted font-12">امتیاز من به محصول</p>
                                            <div class="comment-item-rating">
                                                @for($i=1;$i<=$comment->rating;$i++)
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                @endfor
                                                @for($i=$comment->rating;$i<5;$i++)
                                                    <i class="bi bi-star text-muted"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    <div class="comment-item-content">
                                        <p>{!! nl2br(htmlentities($comment->body)) !!}</p>
                                    </div>
                                    @if ($comment->points->where('type', 'positive')->count() or  $comment->points->where('type', 'negative')->count())
                                        <div class="d-flex flex-wrap gap-4 mt-2">
                                            <ul class="list-unstyled mb-0">
                                                @foreach ($comment->points->where('type', 'positive') as $point)
                                                    <li class="text-success"><b>+</b> {{ $point->text }}</li>
                                                @endforeach
                                            </ul>

                                            <ul class="list-unstyled mb-0">
                                                @foreach ($comment->points->where('type', 'negative') as $point)
                                                    <li class="text-danger"><b>-</b> {{ $point->text }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="usp-card">
                <div class="usp-card-body">
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                        <h6>هنوز دیدگاهی ثبت نکرده‌اید</h6>
                        <p>اولین نقد و نظر خود را دربارهٔ یکی از محصولات بنویسید تا اینجا نمایش داده شود.</p>
                    </div>
                </div>
            </div>
        @endif

        @if($comments->count())
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{$comments->links("pagination::bootstrap-4")}}
                </nav>
            </div>
        @endif

    </div>

    @if($comments->count())
        @include('front::user.partials.edit-review',['product'=>$comment->product])
    @endif
@endsection

@push('scripts')
    <div class="usp-scope">
        {{-- Start comment delete --}}
        <div class="modal fade" id="comment-delete-modal" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-geo-alt"></i>
                            حذف نقد و نظرات
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <p>آیا تمایل به حذف این نظر دارید؟</p>

                                <form id="comment-remove-form" action="#" method="POST">
                                    <div class="text-center">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">لغو</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End comment delete --}}
    </div>

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/profile/comment.js') }}"></script>
@endpush
