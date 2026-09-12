<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\BankRequest;
use App\Modules\Finance\Models\Bank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Bank::class);

        return Inertia::render('Banks/Index', [
            'banks' => Bank::query()
                ->withCount('accounts')
                ->orderBy('label')
                ->get()
                ->map(fn (Bank $bank): array => [
                    'id' => $bank->id,
                    'code' => $bank->code,
                    'label' => $bank->label,
                    'color' => $bank->color,
                    'initials' => $bank->initials,
                    'is_active' => $bank->is_active,
                    'accounts_count' => $bank->accounts_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Bank::class);

        return Inertia::render('Banks/Create');
    }

    public function store(BankRequest $request): RedirectResponse
    {
        Bank::query()->create($request->validated());

        return to_route('banks.index')->with('success', 'Banco cadastrado com sucesso.');
    }

    public function edit(Bank $bank): Response
    {
        $this->authorize('update', $bank);

        return Inertia::render('Banks/Edit', [
            'bank' => [
                'id' => $bank->id,
                'code' => $bank->code,
                'label' => $bank->label,
                'color' => $bank->color,
                'initials' => $bank->initials,
                'is_active' => $bank->is_active,
            ],
        ]);
    }

    public function update(BankRequest $request, Bank $bank): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = $bank->code;

        $bank->update($data);

        return to_route('banks.index')->with('success', 'Banco atualizado com sucesso.');
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $this->authorize('delete', $bank);

        if ($bank->accounts()->exists()) {
            return back()->with('error', 'Nao e possivel remover um banco vinculado a contas.');
        }

        $bank->delete();

        return to_route('banks.index')->with('success', 'Banco removido com sucesso.');
    }
}
