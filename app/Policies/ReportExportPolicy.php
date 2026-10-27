<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    public function view(User $user, ReportExport $export): bool
    {
        return $export->user_id === $user->id
            || $user->can('audit.view');
    }

    public function download(User $user, ReportExport $export): bool
    {
        return $this->view($user, $export) && $user->can('reports.export');
    }
}
