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

        // Only when LLM is unreachable / empty
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
                'body' => mb_substr((string) $a->body, 0, 180),
                'happened_at' => $a->happened_at?->format('Y-m-d'),
            ])->all(),
        ];

        // فرمت ساده برای مدل‌های کوچک لوکال (JSON اجباری نیست)
        $system = "تو مشاور فروش CRM به زبان فارسی هستی.\n"
            ."خروجی را دقیقاً با این قالب بده (بدون ستاره و بدون JSON):\n"
            ."نکته ۱: ...\n"
            ."نکته ۲: ...\n"
            ."نکته ۳: ...\n"
            ."پیشنهاد: یک جمله عملی\n"
            ."وضعیت: پایدار یا حساس یا نیازمند توجه\n"
            ."فقط همین خطوط را بنویس.";

        $user = "تحلیل کوتاه این فرصت:\n"
            .json_encode($payloadContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $base = rtrim((string) config('services.openai.base_url'), '/');
        $model = (string) config('services.openai.model');
        $key = (string) config('services.openai.key');
        $timeout = (int) config('services.openai.timeout', 45);
        $url = $base.'/chat/completions';

        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];

        // برای Ollama از response_format استفاده نکن
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
            Log::warning('LLM insight empty content', ['url' => $url]);

            return null;
        }

        // 1) JSON
        $decoded = $this->parseJsonContent($content);
        if (is_array($decoded)) {
            $structured = $this->fromStructured($decoded, $local);
            if ($structured) {
                return $structured;
            }
        }

        // 2) متن ساده مدل — اولویت با مدل، ظاهر تمیز
        $fromPlain = $this->fromPlainText($content, $local);
        if ($fromPlain) {
            Log::info('LLM insight used plain-text parser');

            return $fromPlain;
        }

        Log::warning('LLM insight could not parse content', ['preview' => mb_substr($content, 0, 240)]);

        return null;
    }

    private function fromStructured(array $decoded, array $local): ?array
    {
        $bullets = $decoded['bullets'] ?? null;
        if (is_string($bullets)) {
            $bullets = preg_split('/[\n\r]+/u', $bullets) ?: [];
        }
        if (! is_array($bullets)) {
            $bullets = [];
        }

        $bullets = $this->cleanBulletList($bullets);
        $suggestion = $this->cleanLine($decoded['suggestion'] ?? null);
        $scoreLabel = $this->normalizeScoreLabel($decoded['score_label'] ?? null) ?? ($local['score_label'] ?? 'پایدار');
        $scoreTone = $this->normalizeTone($decoded['score_tone'] ?? null) ?? ($local['score_tone'] ?? 'ok');

        if ($bullets === [] && ! $suggestion) {
            return null;
        }

        if ($bullets === []) {
            $bullets = array_slice($local['bullets'] ?? [], 0, 2);
        }

        return [
            'bullets' => array_slice($bullets, 0, 5),
            'suggestion' => $suggestion ?: ($local['suggestion'] ?? ''),
            'score_label' => $scoreLabel,
            'score_tone' => $scoreTone,
        ];
    }

    private function fromPlainText(string $content, array $local): ?array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $content);
        $raw = preg_replace('/\*+/u', '', $raw) ?? $raw;

        $lines = preg_split('/\n+/u', $raw) ?: [];
        $bullets = [];
        $suggestion = null;
        $scoreLabel = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // رد کلیدهای خام انگلیسی
            if (preg_match('/^(Tone|score_tone|score_label|bullets)\s*[:=]/i', $line)) {
                if (preg_match('/warn/i', $line)) {
                    $scoreLabel = $scoreLabel ?: 'حساس';
                } elseif (preg_match('/danger|critical/i', $line)) {
                    $scoreLabel = $scoreLabel ?: 'نیازمند توجه';
                } elseif (preg_match('/ok|stable/i', $line)) {
                    $scoreLabel = $scoreLabel ?: 'پایدار';
                }
                continue;
            }

            if (preg_match('/^(پیشنهاد|اقدام بعدی|گام بعدی)\s*[:：\-]\s*(.+)$/u', $line, $m)) {
                $suggestion = $this->cleanLine($m[2]);
                continue;
            }

            if (preg_match('/^(وضعیت|ارزیابی)\s*[:：\-]\s*(.+)$/u', $line, $m)) {
                $scoreLabel = $this->normalizeScoreLabel(trim($m[2])) ?? $scoreLabel;
                continue;
            }

            // نکات شماره‌دار / خط تیره
            $line = preg_replace('/^(نکته\s*\d+\s*[:：\-]|\d+[\.\)\-]\s*|[-•]\s*)/u', '', $line) ?? $line;
            $line = $this->cleanLine($line);
            if ($line && mb_strlen($line) >= 8) {
                $bullets[] = $line;
            }
        }

        // اگر مدل یک پاراگراف داد
        if ($bullets === [] && $suggestion === null) {
            $one = $this->cleanLine(preg_replace('/\s+/u', ' ', $raw));
            if ($one && mb_strlen($one) >= 12) {
                // به جملات کوتاه بشکن
                $parts = preg_split('/(?<=[.!?؟۔])\s+/u', $one) ?: [$one];
                foreach ($parts as $p) {
                    $p = $this->cleanLine($p);
                    if ($p && mb_strlen($p) >= 8) {
                        $bullets[] = $p;
                    }
                    if (count($bullets) >= 4) {
                        break;
                    }
                }
                if (count($bullets) > 1) {
                    $suggestion = array_pop($bullets);
                } elseif ($bullets !== []) {
                    $suggestion = $bullets[0];
                }
            }
        }

        $bullets = $this->cleanBulletList($bullets);

        if ($bullets === [] && ! $suggestion) {
            return null;
        }

        // اگر فقط پیشنهاد داشت، دو نکته زمینه‌ای محلی برای خوانایی
        if ($bullets === []) {
            $bullets = array_slice($local['bullets'] ?? [], 0, 2);
        }

        if (! $suggestion) {
            $suggestion = $local['suggestion'] ?? 'پیگیری فعال و ثبت نتیجه پیشنهاد می‌شود.';
        }

        $scoreLabel = $scoreLabel ?? ($local['score_label'] ?? 'پایدار');
        $scoreTone = match ($scoreLabel) {
            'نیازمند توجه' => 'danger',
            'حساس' => 'warn',
            default => 'ok',
        };

        return [
            'bullets' => array_slice($bullets, 0, 5),
            'suggestion' => $suggestion,
            'score_label' => $scoreLabel,
            'score_tone' => $scoreTone,
        ];
    }

    private function cleanBulletList(array $items): array
    {
        $out = [];
        foreach ($items as $b) {
            $line = $this->cleanLine($b);
            if (! $line) {
                continue;
            }
            if (preg_match('/\b(Tone|bullets|score_tone|score_label)\b/i', $line)) {
                continue;
            }
            $out[] = $line;
        }

        return array_values(array_unique($out));
    }

    private function cleanLine(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $s = trim($value);
        $s = preg_replace('/\*+/u', '', $s) ?? $s;
        $s = preg_replace('/^[-•\s]+/u', '', $s) ?? $s;
        $s = trim($s);

        if ($s === '' || mb_strlen($s) < 6) {
            return null;
        }

        return mb_substr($s, 0, 240);
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
        if (str_contains($label, 'نیازمند')) {
            return 'نیازمند توجه';
        }
        if (str_contains($label, 'حساس')) {
            return 'حساس';
        }
        if (str_contains($label, 'پایدار')) {
            return 'پایدار';
        }

        $allowed = ['پایدار', 'حساس', 'نیازمند توجه'];

        return in_array($label, $allowed, true) ? $label : null;
    }

    private function parseJsonContent(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $content, $m)) {
            $content = $m[1];
        } elseif (preg_match('/(\{.*\})/s', $content, $m)) {
            $content = $m[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
