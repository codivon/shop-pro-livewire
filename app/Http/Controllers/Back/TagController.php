<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Tag::class, 'tags');
    }

public function export(Request $request)
    {
        $tags = Tag::all();

        $filename = 'tags_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($tags) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['شناسه', 'عنوان', 'اسلاگ', 'تعداد استفاده', 'بازدید', 'تاریخ ایجاد']);

            foreach ($tags as $tag) {
                fputcsv($file, [
                    $tag->id,
                    $tag->name,
                    $tag->slug,
                    $tag->taggables()->count(),
                    $tag->view_count,
                    jdate($tag->created_at)->format('Y/m/d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
