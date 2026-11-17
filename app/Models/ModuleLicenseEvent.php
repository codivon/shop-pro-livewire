<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * رویدادهای گارد لایسنس ماژول‌ها — سخت‌سازی امنیتی .
 * ثبت: verified_online / grace_started / closed / restored / tamper_detected /
 * integrity_failed / manifest_provisioned / server_rejected.
 * برای پایش ادمین در پنل و تحلیل حوادث امنیتی.
 */
class ModuleLicenseEvent extends Model
{
    protected $table = 'module_license_events';

    protected $fillable = [
        'module_slug',
        'event',
        'details',
    ];

    protected $casts = [
        'details'   => 'array',
        'created_at' => 'datetime',
    ];

    public const EVENT_VERIFIED_ONLINE   = 'verified_online';
    public const EVENT_GRACE_STARTED     = 'grace_started';
    public const EVENT_CLOSED            = 'closed';
    public const EVENT_RESTORED          = 'restored';
    public const EVENT_TAMPER_DETECTED   = 'tamper_detected';
    public const EVENT_INTEGRITY_FAILED  = 'integrity_failed';
    public const EVENT_MANIFEST_PROVISIONED = 'manifest_provisioned';
    public const EVENT_SERVER_REJECTED   = 'server_rejected';
}
