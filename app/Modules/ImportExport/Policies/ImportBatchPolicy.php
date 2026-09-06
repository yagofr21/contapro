<?php

namespace App\Modules\ImportExport\Policies;

use App\Models\User;
use App\Modules\ImportExport\Models\ImportBatch;

class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ImportBatch $batch): bool
    {
        return $batch->user_id === $user->id;
    }

    public function update(User $user, ImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }

    public function delete(User $user, ImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }
}
