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
            Log::warning('Opportunity insight LLM failed: '.$e->getMessage(), [
                'base_url' => config('services.openai.base_url'),
                'model' => config('services.openai.model'),
            ]);
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
            if (
                str_contains($base, '11434')
                || str_contains($base, 'localhost')
                || str_contains($base, '127.0.0.1')
                || str_contains($base, 'host.docker.internal')
                || str_contains($base, '172.17.')
            ) {
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
            'local_hint' => [
                'score_label' => $local['score_label'] ?? null,
                'score_tone' => $local['score_tone'] ?? null,
                'suggestion' => $local['suggestion'] ?? null,
            ],
        ];

        $system = 'تو مشاور فروش CRM فارسی هستی. فقط یک JSON معتبر و فشرده برگردان. کلیدها دقیقاً: '
            .'"bullets" (آرایه ۲ تا ۴ رشته کوتاه فارسی)، '
            .'"suggestion" (یک جمله فارسی)، '
            .'"score_label" (فقط یکی از: پایدار | حساس | نیازمند توجه)، '
            .'"score_tone" (فقط یکی از: ok | warn | danger). '
            .'هیچ متنی بیرون از JSON ننویس. ستاره و markdown ممنوع.';

        $user = "داده فرصت:\n"
            .json_encode($payloadContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ."\nفقط JSON بده.";

        $base = rtrim((string) config('services.openai.base_url'), '/');
        $model = (string) config('services.openai.model');
        $key = (string) config('services.openai.key');
        $timeout = (int) config('services.openai.timeout', 45);
        $url = $base.'/chat/completions';

        $payload = [
            'model' => $model,
            'temperature' => 0.1,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];

        if ($this->usesJsonMode()) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        Log::info('Opportunity insight LLM request', [
            'url' => $url,
            'model' => $model,
            'json_mode' => isset($payload['response_format']),
        ]);

        $response = Http::timeout($timeout)
            ->withToken($key)
            ->acceptJson()
            ->post($url, $payload);

        // Retry once without response_format if server rejected it (common on Ollama)
        if (! $response->successful() && isset($payload['response_format'])) {
            unset($payload['response_format']);
            $response = Http::timeout($timeout)
                ->withToken($key)
                ->acceptJson()
                ->post($url, $payload);
        }

        if (! $response->successful()) {
            Log::warning('LLM insight HTTP '.$response->status().': '.$response->body(), [
                'url' => $url,
            ]);

            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            Log::warning('LLM insight empty content', ['url' => $url, 'body' => $response->body()]);

            return null;
        }

        $decoded = $this->parseJsonContent($content);
        if (! is_array($decoded)) {
            // مدل کوچک اغلب JSON نمی‌دهد — متن خام را قاطی نکن؛ فقط تحلیل محلی بمان
            Log::info('LLM insight JSON parse failed; keeping local insight only', [
                'preview' => mb_substr($content, 0, 180),
            ]);

            return null;
        }

        $bullets = $decoded['bullets'] ?? null;
        if (is_string($bullets)) {
            $bullets = array_values(array_filter(array_map('trim', preg_split('/[\n\r•\-]+/u', $bullets) ?: [])));
        }
        if (! is_array($bullets)) {
            $bullets = [];
        }

        $bullets = array_values(array_filter(array_map(function ($b) {
            $b = trim((string) $b);
            $b = preg_replace('/^[*\-•\s]+/u', '', $b) ?? $b;
            $b = preg_replace('/\*+/u', '', $b) ?? $b;
            $b = trim($b);

            // رد کردن تکه‌های خام/انگلیسی مدل
            if ($b === '' || mb_strlen($b) < 6) {
                return null;
            }
            if (preg_match('/\b(Tone|bullets|score_tone|score_label|suggestion)\b/i', $b)) {
                return null;
            }

            return $b;
        }, $bullets)));

        if ($bullets === []) {
            // اگر بولت تمیز نبود، از محلی استفاده کن و فقط پیشنهاد مدل را در صورت تمیز بودن بگیر
            $suggestion = $this->cleanSuggestion($decoded['suggestion'] ?? null) ?? ($local['suggestion'] ?? '');

            return [
                'bullets' => $local['bullets'],
                'suggestion' => $suggestion,
                'score_label' => $this->normalizeScoreLabel($decoded['score_label'] ?? null) ?? ($local['score_label'] ?? 'پایدار'),
                'score_tone' => $this->normalizeTone($decoded['score_tone'] ?? null) ?? ($local['score_tone'] ?? 'ok'),
            ];
        }

        return [
            'bullets' => array_slice($bullets, 0, 5),
            'suggestion' => $this->cleanSuggestion($decoded['suggestion'] ?? null) ?? ($local['suggestion'] ?? ''),
            'score_label' => $this->normalizeScoreLabel($decoded['score_label'] ?? null) ?? ($local['score_label'] ?? 'پایدار'),
            'score_tone' => $this->normalizeTone($decoded['score_tone'] ?? null) ?? ($local['score_tone'] ?? 'ok'),
        ];
    }

    private function cleanSuggestion(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $s = trim($value);
        $s = preg_replace('/\*+/u', '', $s) ?? $s;
        $s = trim($s);

        if ($s === '' || mb_strlen($s) < 8) {
            return null;
        }
        if (preg_match('/\b(Tone|bullets|score_tone|score_label)\b/i', $s)) {
            return null;
        }

        return mb_substr($s, 0, 220);
    }

    private function normalizeTone(mixed $tone): ?string
    {
        if (! is_string($tone)) {
            return null;
        }

        $tone = strtolower(trim($tone));

        return in_array($tone, ['ok', 'warn', 'danger'], true) ? $tone : null;
    }

    private function normalizeScoreLabel(mixed $label): ?string
    {
        if (! is_string($label)) {
            return null;
        }

        $label = trim($label);
        $allowed = ['پایدار', 'حساس', 'نیازمند توجه'];

        return in_array($label, $allowed, true) ? $label : null;
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
