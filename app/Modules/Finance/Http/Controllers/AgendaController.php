<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\TransactionSchedule;
use App\Modules\Finance\Queries\AgendaProjectionQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function __invoke(Request $request, AgendaProjectionQuery $query): Response
    {
        $this->authorize('viewAny', TransactionSchedule::class);
        $this->authorize('viewAny', Installment::class);

        return Inertia::render('Agenda/Index', $query->forUser($request->user(), 60));
    }
}
