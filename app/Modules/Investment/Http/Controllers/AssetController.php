<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Http\Requests\AssetRequest;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $preferences = AssetPreference::query()
            ->where('user_id', $request->user()->id)
            ->pluck('auto_update', 'asset_id');

        return Inertia::render('Assets/Index', [
            'portfolios' => $request->user()->portfolios()
                ->orderBy('name')
                ->get(['id', 'name', 'currency'])
                ->map(fn ($portfolio) => [
                    'id' => $portfolio->id,
                    'name' => $portfolio->name,
                    'currency' => $portfolio->currency->value,
                ]),
            'assets' => Asset::query()
                ->with('latestPrice')
                ->withExists(['holdings as can_refresh' => fn ($query) => $query
                    ->whereHas('portfolio', fn ($portfolio) => $portfolio->where('user_id', $request->user()->id))])
                ->orderBy('market')
                ->orderBy('symbol')
                ->get()
                ->map(function (Asset $asset) use ($preferences): array {
                    $latestPrice = $asset->latestPrice;

                    return [
                        'id' => $asset->id,
                        'symbol' => $asset->symbol,
                        'name' => $asset->name,
                        'type' => $asset->type->value,
                        'market' => $asset->market->value,
                        'currency' => $asset->currency->value,
                        'is_active' => $asset->is_active,
                        'can_refresh' => $asset->is_active
                            && in_array($asset->market, [Market::B3, Market::Crypto], true)
                            && (bool) $asset->getAttribute('can_refresh'),
                        'auto_update' => (bool) ($preferences[$asset->id] ?? true),
                        'price' => $latestPrice !== null ? ($latestPrice->adjusted_close ?? $latestPrice->close) : null,
                        'price_date' => $latestPrice?->price_date->format('Y-m-d'),
                    ];
                }),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Assets/Create', [
            'types' => $this->enumOptions(AssetType::cases()),
            'markets' => $this->enumOptions(Market::cases()),
            'currencies' => $this->enumOptions(Currency::cases()),
        ]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        Asset::query()->create($request->validated());

        return to_route('assets.index')->with('success', 'Ativo adicionado ao catalogo.');
    }

    public function autoUpdate(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('autoUpdate', $asset);
        $request->validate(['auto_update' => ['required', 'boolean']]);

        AssetPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'asset_id' => $asset->id],
            ['auto_update' => $request->boolean('auto_update')],
        );

        $message = $request->boolean('auto_update')
            ? 'Atualizacao automatica ativada para este ativo.'
            : 'Atualizacao automatica desativada para este ativo.';

        return back()->with('success', $message);
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: int|string, label: int|string}>
     */
    private function enumOptions(array $cases): array
    {
        return collect($cases)->map(fn (\BackedEnum $case) => [
            'value' => $case->value,
            'label' => $case->value,
        ])->all();
    }
}
