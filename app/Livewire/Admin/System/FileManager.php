<?php

namespace App\Livewire\Admin\System;

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * مدیریت فایل‌ها — همان iframe فایل‌منیجر داخل قالب جدید رندر می‌شود.
 * آدرس iframe: روت np.file-manager-iframe (پس از حذف پنل قدیمی).
 */
#[Title('مدیریت فایل‌ها')]
class FileManager extends Component
{
    public function mount(): void
    {
        auth('adminPanel')->user()?->can('file-manager') ?: abort(403);
    }

    /** آدرس iframe فایل‌منیجر (np جدید یا روت قدیمی) */
    public function getIframeUrlProperty(): string
    {
        return route('np.file-manager-iframe');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.file-manager')
            ->layout('admin-panel::layout');
    }
}
