<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpportunityInsightService
{
    public function forOpportunity(Opportunity $opp): array
    {
        $local = $this->localInsight($opp);

        if (! $this->llmEnabled()) {
            return array_merge($local, [
                'source' => 'local',
                'llm_available' => false,
            ]);
        }

        try {
            $llm = $this->llmInsight($opp, $local);
            if ($llm) {
                return array_merge($llm, [
                    'source' => 'llm',
                    'llm_available' => true,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Opportunity insight LLM failed: '.$e->getMessage());
        }

        // Always keep UI working even if LLM is down
        return array_merge($local, [
            'source' => 'local',
            'llm_available' => true,
            'llm_error' => true,
        ]);
    }

    public function llmEnabled(): bool
    {
        $key = config('services.openai.key');

        return is_string($key) && trim($key) !== '';
    }

    private function usesJsonMode(): bool
    {
        $flag = config('services.openai.json_mode');

        if ($flag === null || $flag === '') {
            $base = (string) config('services.openai.base_url');

            // Ollama / local OpenAI-compatible servers often reject response_format
            if (str_contains($base, '11434') || str_contains($base, 'localhost') || str_contains($base, '127.0.0.1')) {
                return false;
            }

            return true;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    private function localInsight(Opportunity $opp): array
    {
        $steps = $opp->steps ?? collect();
        $activities = $opp->activities ?? collect();

        $open = $steps->filter(fn ($s) => $s->status === 'open' || $s->is_current);
        $closed = $steps->filter(fn ($s) => $s->status === 'closed');

        $today = now()->startOfDay();
        $overdue = $open->filter(function ($s) use ($today) {
            if (! $s->due_on) {
                return false;
            }

            return $s->due_on->copy()->startOfDay()->lt($today);
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

        if ($overdue->isNotEmpty()) {
            $bullets[] = '⚠ '.$overdue->count().' گام از مهلت گذشته — اولویت فوری بستن یا تمدید.';
            $scoreTone = 'danger';
            $scoreLabel = 'نیازمند توجه';
        } elseif ($current?->due_on) {
            $due = $current->due_on->copy()->startOfDay();
            $days = $today->diffInDays($due, false);
            if ($days <= 2) {
                $bullets[] = 'مهلت گام جاری نزدیک است؛ امروز پیگیری فعال توصیه می‌شود.';
                $scoreTone = 'warn';
                $scoreLabel = 'حساس';
            } else {
                $bullets[] = 'گام جاری «'.$current->title.'» در وضعیت قابل مدیریت است.';
                $scoreTone = 'ok';
                $scoreLabel = 'پایدار';
            }
        } elseif ($current) {
            $bullets[] = 'گام جاری «'.$current->title.'» در وضعیت قابل مدیریت است.';
            $scoreTone = 'ok';
            $scoreLabel = 'پایدار';
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

    private function llmInsight(Opportunity $opp, array $local): ?array
    {
        $payloadContext = [
            'organization' => $opp->organization_name,
            'status' => $opp->status,
            'stage' => $opp->stage?->name,
            'products' => $opp->products ?: array_filter([$opp->product_name]),
            'contact' => $opp->contact_name,
            'steps' => ($opp->steps ?? collect())->map(fn ($s) => [
                'title' => $s->title,
                'status' => $s->status,
                'due_on' => $s->due_on?->format('Y-m-d'),
                'result' => $s->result,
                'is_current' => (bool) $s->is_current,
            ])->values()->all(),
            'activities' => ($opp->activities ?? collect())->sortByDesc('id')->take(8)->values()->map(fn ($a) => [
                'type' => $a->type,
                'subject' => $a->subject,
                'body' => mb_substr((string) $a->body, 0, 240),
                'happened_at' => $a->happened_at?->format('Y-m-d'),
            ])->all(),
            'local_hint' => $local,
        ];

        $system = 'تو مشاور فروش CRM فارسی هستی. فقط JSON معتبر برگردان با کلیدهای: '
            .'bullets (آرایه حداکثر 5 رشته کوتاه)، suggestion (یک جمله)، score_label (یکی از: پایدار|حساس|نیازمند توجه)، '
            .'score_tone (یکی از: ok|warn|danger). بدون توضیح اضافه و بدون markdown.';

        $user = "بر اساس این داده فرصت، خلاصه وضعیت و پیشنهاد گام/فعالیت بعدی بده:\n"
            .json_encode($payloadContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $base = (string) config('services.openai.base_url');
        $model = (string) config('services.openai.model');
        $key = (string) config('services.openai.key');
        $timeout = (int) config('services.openai.timeout', 45);

        $payload = [
            'model' => $model,
            'temperature' => 0.3,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];

        if ($this->usesJsonMode()) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout($timeout)
            ->withToken($key)
            ->acceptJson()
            ->post($base.'/chat/completions', $payload);

        // Retry once without response_format if server rejected it (common on Ollama)
        if (! $response->successful() && isset($payload['response_format'])) {
            unset($payload['response_format']);
            $response = Http::timeout($timeout)
                ->withToken($key)
                ->acceptJson()
                ->post($base.'/chat/completions', $payload);
        }

        if (! $response->successful()) {
            Log::warning('LLM insight HTTP '.$response->status().': '.$response->body());

            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $decoded = $this->parseJsonContent($content);
        if (! is_array($decoded)) {
            return null;
        }

        $bullets = $decoded['bullets'] ?? null;
        if (! is_array($bullets) || $bullets === []) {
            return null;
        }

        $tone = $decoded['score_tone'] ?? 'ok';
        if (! in_array($tone, ['ok', 'warn', 'danger'], true)) {
            $tone = 'ok';
        }

        return [
            'bullets' => array_values(array_map('strval', array_slice($bullets, 0, 5))),
            'suggestion' => (string) ($decoded['suggestion'] ?? $local['suggestion']),
            'score_label' => (string) ($decoded['score_label'] ?? $local['score_label']),
            'score_tone' => $tone,
        ];
    }

    private function parseJsonContent(string $content): ?array
    {
        $content = trim($content);

        // Strip ```json ... ``` fences from local models
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $content, $m)) {
            $content = $m[1];
        } elseif (preg_match('/(\{.*\})/s', $content, $m)) {
            $content = $m[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
