<?php

namespace App\Services;

use App\Models\Opportunity;
use Carbon\Carbon;
use Throwable;

/**
 * خلاصه وضعیت فرصت فقط از داده دیتابیس (بدون مدل زبانی).
 */
class OpportunityInsightService
{
    public function forOpportunity(Opportunity $opp): array
    {
        try {
            $local = $this->localInsight($opp);
        } catch (Throwable) {
            $local = [
                'bullets' => ['خلاصه فرصت موقتاً ساده شده است.'],
                'suggestion' => 'یک فعالیت کوتاه ثبت کنید و نتیجه را بنویسید.',
                'score_label' => 'پایدار',
                'score_tone' => 'ok',
            ];
        }

        return array_merge($local, [
            'source' => 'local',
        ]);
    }

    private function asDay(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function localInsight(Opportunity $opp): array
    {
        $steps = $opp->steps ?? collect();
        $activities = $opp->activities ?? collect();

        $open = $steps->filter(fn ($s) => $s->status === 'open' || $s->is_current);
        $closed = $steps->filter(fn ($s) => $s->status === 'closed');

        $today = now()->startOfDay();
        $overdue = $open->filter(function ($s) use ($today) {
            $due = $this->asDay($s->due_on ?? null);

            return $due && $due->lt($today);
        });

        $statusMap = [
            'active' => 'فعال',
            'nurturing' => 'در حال پرورش',
            'paused' => 'متوقف موقت',
            'won' => 'بسته‌شده-موفق',
            'lost' => 'بسته‌شده-ناموفق',
        ];

        $typeMap = [
            'call' => 'تماس',
            'meeting' => 'جلسه',
            'message' => 'ایمیل / پیام',
            'visit' => 'بازدید',
            'proposal' => 'ارسال پیشنهاد',
            'other' => 'سایر',
            'note' => 'یادداشت',
            'referral' => 'ارجاع',
        ];

        $bullets = [];
        $bullets[] = 'وضعیت فرصت: '.($statusMap[$opp->status] ?? $opp->status).' · مرحله قیف: '.($opp->stage?->name ?? '—');
        $bullets[] = 'گام‌های باز: '.$open->count().' · بسته‌شده: '.$closed->count().' · کل فعالیت: '.$activities->count();

        $current = $steps->first(fn ($s) => $s->is_current && $s->status === 'open')
            ?? $steps->first(fn ($s) => $s->status === 'open');

        $scoreTone = 'ok';
        $scoreLabel = 'پایدار';

        if ($overdue->isNotEmpty()) {
            $bullets[] = '⚠ '.$overdue->count().' گام از مهلت گذشته — اولویت فوری بستن یا تمدید.';
            $scoreTone = 'danger';
            $scoreLabel = 'نیازمند توجه';
        } elseif ($current) {
            $due = $this->asDay($current->due_on ?? null);
            if ($due) {
                $days = (int) $today->diffInDays($due);
                if ($due->lt($today)) {
                    $days = -$days;
                }
                if ($days <= 2) {
                    $bullets[] = 'مهلت گام جاری نزدیک است؛ امروز پیگیری فعال توصیه می‌شود.';
                    $scoreTone = 'warn';
                    $scoreLabel = 'حساس';
                } else {
                    $bullets[] = 'گام جاری «'.$current->title.'» در وضعیت قابل مدیریت است.';
                }
            } else {
                $bullets[] = 'گام جاری «'.$current->title.'» در وضعیت قابل مدیریت است.';
            }
        } else {
            $bullets[] = 'گام بازی تعریف نشده است.';
            $scoreTone = 'warn';
            $scoreLabel = 'حساس';
        }

        $recent = $activities->sortByDesc('id')->take(3)->values();
        if ($recent->isNotEmpty()) {
            $titles = $recent->map(fn ($a) => $typeMap[$a->type] ?? ($a->subject ?: $a->type))->implode('، ');
            $bullets[] = 'آخرین فعالیت‌ها: '.$titles;
        } else {
            $bullets[] = 'هنوز فعالیت ثبت‌شده‌ای نیست — ثبت اولین تماس/جلسه مسیر را روشن می‌کند.';
        }

        $suggestion = 'یک فعالیت پیگیری کوتاه ثبت کنید و نتیجه را شفاف بنویسید.';
        if ($overdue->isNotEmpty()) {
            $suggestion = 'ابتدا گام معوق را با نتیجه واقعی ببندید یا مهلت جدید تعریف کنید.';
        } elseif ($activities->isEmpty() && $current) {
            $suggestion = 'برای «'.$current->title.'» یک تماس یا جلسه اولیه ثبت کنید.';
        } elseif ($closed->isNotEmpty() && $open->isNotEmpty()) {
            $suggestion = 'گام فعلی قابل ادامه است؛ می‌توانید گام بعدی را از الان طراحی کنید.';
        } elseif ($opp->status === 'nurturing') {
            $suggestion = 'فرصت در پرورش است؛ یک محتوای ارزش‌افزا یا نمونه برای مخاطب پیشنهاد دهید.';
        }

        return [
            'bullets' => $bullets,
            'suggestion' => $suggestion,
            'score_label' => $scoreLabel,
            'score_tone' => $scoreTone,
        ];
    }
}
