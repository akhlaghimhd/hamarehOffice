<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use RuntimeException;

class CompanyContext
{
    protected ?int $companyId = null;

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function check(): int
    {
        if ($this->companyId === null) {
            throw new RuntimeException('Company context is not set.');
        }

        return $this->companyId;
    }

    public function set(?int $companyId): void
    {
        $this->companyId = $companyId;

        if ($companyId !== null) {
            app()->instance('currentCompanyId', $companyId);
        } else {
            app()->forgetInstance('currentCompanyId');
        }
    }

    public function clear(): void
    {
        $this->set(null);
    }

    public function setForUser(User $user, int $companyId): CompanyUser
    {
        $membership = CompanyUser::query()
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $membership) {
            throw new RuntimeException('عضویت فعال برای این شرکت یافت نشد.');
        }

        $this->set($companyId);

        return $membership;
    }

    public function company(): ?Company
    {
        if ($this->companyId === null) {
            return null;
        }

        return Company::query()->find($this->companyId);
    }
}
