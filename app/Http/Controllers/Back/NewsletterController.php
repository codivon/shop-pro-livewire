<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NewslettersExport;

class NewsletterController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Newsletter::class, 'newsletter');
    }

public function export(Request $request)
    {
        $type = $request->type;
        $format = $request->input('format') ?: 'xlsx';

        $query = Newsletter::query();

        switch ($type) {
            case 'email':
                $query->emailSubscribers();
                break;
            case 'mobile':
                $query->mobileSubscribers();
                break;
            case 'active':
                $query->where('is_active', true);
                break;
            case 'inactive':
                $query->where('is_active', false);
                break;
            case 'all':
            default:
                // همه موارد
                break;
        }

        $subscribers = $query->get();

        if ($format == 'csv') {
            return $this->exportCsv($subscribers, $type);
        }

        return Excel::download(new NewslettersExport($subscribers, $type), 'newsletters_' . $type . '_' . now()->format('Y-m-d') . '.xlsx');
    }

private function exportCsv($subscribers, $type)
    {
        $filename = 'newsletters_' . $type . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($subscribers) {
            $file = fopen('php://output', 'w');

            // ستون‌های CSV
            fputcsv($file, ['شناسه', 'ایمیل/شماره', 'نوع', 'وضعیت', 'IP', 'مرورگر', 'سیستم عامل', 'دستگاه', 'تاریخ ثبت نام']);

            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->id,
                    $subscriber->contact,
                    $subscriber->contact_type == 'email' ? 'ایمیل' : 'شماره موبایل',
                    $subscriber->is_active ? 'فعال' : 'غیرفعال',
                    $subscriber->ip_address ?? '-',
                    $subscriber->browser ?? '-',
                    $subscriber->os ?? '-',
                    $subscriber->device_type ?? '-',
                    jdate($subscriber->created_at)->format('Y/m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
