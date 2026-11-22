<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyContext
{
    public function __construct(private CompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $companyId = $request->header('X-Company-Id')
            ?? $request->input('company_id');

        if (! $companyId) {
            return response()->json([
                'message' => 'شرکت جاری مشخص نشده است.',
                'code' => 'company_context_required',
            ], 422);
        }

        try {
            $this->context->setForUser($user, (int) $companyId);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'company_context_invalid',
            ], 403);
        }

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
