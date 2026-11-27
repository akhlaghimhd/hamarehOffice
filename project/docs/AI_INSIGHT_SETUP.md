# راه‌اندازی تحلیل هوشمند فرصت (لوکال + هاست)

کد پروژه **مدل را داخل گیت حمل نمی‌کند**. فقط کلاینت API سازگار با OpenAI را دارد.
بدون کلید هم همیشه تحلیل محلی (rule-based) کار می‌کند.

## توسعه لوکال (Ollama)

```bash
# نصب Ollama
curl -fsSL https://ollama.com/install.sh | sh

# دریافت و اجرای مدل
ollama pull llama3.2
ollama serve
```

در `project/.env`:

```env
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://127.0.0.1:11434/v1
OPENAI_MODEL=llama3.2
OPENAI_JSON_MODE=false
OPENAI_TIMEOUT=60
```

```bash
cd project
php artisan config:clear
```

صفحه مدیریت گام را باز کنید؛ پایین باکس «وضعیت و برآیند» باید «تحلیل با مدل زبانی» باشد.

## انتقال به هاست

1. کل پروژه را کپی/دیپلوی کنید (مثل همیشه).
2. روی هاست **Ollama لازم نیست** مگر خودتان بخواهید.
3. در `.env` هاست یکی از این‌ها:

### الف) OpenAI ابری

```env
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
OPENAI_JSON_MODE=true
OPENAI_TIMEOUT=30
```

### ب) بدون مدل (امن و آفلاین)

```env
OPENAI_API_KEY=
```

سیستم خودش به تحلیل محلی برمی‌گردد؛ UI نمی‌شکند.

```bash
php artisan config:clear
```

## نکات

- `.env` را commit نکنید؛ فقط `.env.example` راهنما است.
- لوکال و هاست **یک کد** دارند؛ فقط مقادیر `.env` فرق می‌کند.
- اگر LLM قطع شود، fallback محلی فعال می‌شود.
