<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Http\Requests\AssetRequest;
use App\Modules\Investment\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

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
                ->map(function (Asset $asset): array {
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
