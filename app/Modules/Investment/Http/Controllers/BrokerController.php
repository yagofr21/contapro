<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Investment\Http\Requests\BrokerRequest;
use App\Modules\Investment\Models\Broker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrokerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Broker::class);

        return Inertia::render('Brokers/Index', [
            'brokers' => $request->user()->brokers()
                ->withCount('transactions')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (Broker $broker) => [
                    'id' => $broker->id,
                    'name' => $broker->name,
                    'is_active' => $broker->is_active,
                    'transactions_count' => $broker->transactions_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Broker::class);

        return Inertia::render('Brokers/Create');
    }

    public function store(BrokerRequest $request): RedirectResponse
    {
        $request->user()->brokers()->create($request->validated());

        return to_route('brokers.index')->with('success', 'Corretora cadastrada com sucesso.');
    }

    public function edit(Broker $broker): Response
    {
        $this->authorize('update', $broker);

        return Inertia::render('Brokers/Edit', [
            'broker' => [
                'id' => $broker->id,
                'name' => $broker->name,
                'is_active' => $broker->is_active,
            ],
        ]);
    }

    public function update(BrokerRequest $request, Broker $broker): RedirectResponse
    {
        $broker->update($request->validated());

        return to_route('brokers.index')->with('success', 'Corretora atualizada com sucesso.');
    }
}
