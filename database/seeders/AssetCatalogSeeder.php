<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use Illuminate\Database\Seeder;

class AssetCatalogSeeder extends Seeder
{
    /**
     * Semeia um catalogo base de ativos negociados na B3 e criptomoedas,
     * todos cobertos pela atualizacao automatica de cotacoes.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $row) {
            Asset::query()->updateOrCreate(
                ['market' => $row['market'], 'symbol' => $row['symbol']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'currency' => Currency::BRL->value,
                    'is_active' => true,
                ],
            );
        }
    }

    /** @return list<array{symbol: string, name: string, market: string, type: string}> */
    private function catalog(): array
    {
        return [
            ...$this->map(Market::B3, AssetType::Stock, $this->stocks()),
            ...$this->map(Market::B3, AssetType::Fii, $this->fii()),
            ...$this->map(Market::B3, AssetType::Etf, $this->etf()),
            ...$this->map(Market::Crypto, AssetType::Crypto, $this->cryptos()),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $rows
     * @return list<array{symbol: string, name: string, market: string, type: string}>
     */
    private function map(Market $market, AssetType $type, array $rows): array
    {
        return array_map(
            fn (array $row): array => [
                'symbol' => $row[0],
                'name' => $row[1],
                'market' => $market->value,
                'type' => $type->value,
            ],
            $rows,
        );
    }

    /** @return list<array{0: string, 1: string}> */
    private function stocks(): array
    {
        return [
            ['ABEV3', 'Ambev'],
            ['ALOS3', 'Allos'],
            ['ALPA4', 'Alpargatas'],
            ['ASSA3', 'Assai'],
            ['AZUL4', 'Azul'],
            ['B3SA3', 'B3'],
            ['BBAS3', 'Banco do Brasil'],
            ['BBDC4', 'Bradesco'],
            ['BBSE3', 'BB Seguridade'],
            ['BEEF3', 'Minerva'],
            ['BRAP4', 'Bradespar'],
            ['BRFS3', 'BRF'],
            ['BRKM5', 'Braskem'],
            ['CCRO3', 'CCR'],
            ['CMIG4', 'Cemig'],
            ['COGN3', 'Cogna'],
            ['CPFE3', 'CPFL Energia'],
            ['CPLE6', 'Copel'],
            ['CRFB3', 'Carrefour Brasil'],
            ['CSAN3', 'Cosan'],
            ['CSNA3', 'CSN'],
            ['CVCB3', 'CVC Brasil'],
            ['CXSE3', 'Caixa Seguridade'],
            ['CYRE3', 'Cyrela'],
            ['DXCO3', 'Dexco'],
            ['EGIE3', 'Engie Brasil'],
            ['ELET3', 'Eletrobras'],
            ['EMBR3', 'Embraer'],
            ['ENGI11', 'Energisa'],
            ['EQTL3', 'Equatorial'],
            ['FLRY3', 'Fleury'],
            ['GGBR4', 'Gerdau'],
            ['GOAU4', 'Gerdau Metalurgica'],
            ['HAPV3', 'Hapvida'],
            ['HYPE3', 'Hypera'],
            ['IGTA3', 'Iguatemi'],
            ['ITSA4', 'Itausa'],
            ['ITUB4', 'Itau Unibanco'],
            ['JBSS3', 'JBS'],
            ['KLBN11', 'Klabin'],
            ['LREN3', 'Lojas Renner'],
            ['MGLU3', 'Magazine Luiza'],
            ['MRFG3', 'Marfrig'],
            ['MRVE3', 'MRV'],
            ['MULT3', 'Multiplan'],
            ['NTCO3', 'Natura'],
            ['PCAR3', 'Grupo Pao de Acucar'],
            ['PETR3', 'Petrobras ON'],
            ['PETR4', 'Petrobras PN'],
            ['PETZ3', 'Petz'],
            ['PRIO3', 'Prio'],
            ['RADL3', 'Raia Drogasil'],
            ['RAIL3', 'Rumo'],
            ['RAIZ4', 'Raizen'],
            ['RENT3', 'Localiza'],
            ['SANB11', 'Santander Brasil'],
            ['SBSP3', 'Sabesp'],
            ['SLCE3', 'SLC Agricola'],
            ['SMTO3', 'Smartfit'],
            ['SUZB3', 'Suzano'],
            ['TAEE11', 'Taesa'],
            ['TIMS3', 'TIM'],
            ['USIM5', 'Usiminas'],
            ['VALE3', 'Vale'],
            ['VAMO3', 'Vamos'],
            ['VBBR3', 'Vibra Energia'],
            ['VIVT3', 'Vivo'],
            ['WEGE3', 'WEG'],
            ['YDUQ3', 'Yduqs'],
        ];
    }

    /** @return list<array{0: string, 1: string}> */
    private function fii(): array
    {
        return [
            ['BCFF11', 'BTG Pactual Fundo de Fundos'],
            ['BRCR11', 'BTG Corporate'],
            ['BTLG11', 'BTG Pactual Logistica'],
            ['CPTS11', 'Capitania Securities'],
            ['DEVA11', 'Devant'],
            ['GARE11', 'Guardian Real Estate'],
            ['HFOF11', 'Hedge Top FOFII 3'],
            ['HGBS11', 'Hedge Brasil Shopping'],
            ['HGCR11', 'CSHG Recebiveis Imobiliarios'],
            ['HGLG11', 'CSHG Logistica'],
            ['HGPO11', 'CSHG Prime Offices'],
            ['HGRU11', 'CSHG Renda Urbana'],
            ['IRDM11', 'Iridium Recebiveis Imobiliarios'],
            ['JSRE11', 'JS Real Estate'],
            ['KNCR11', 'Kinea Rendimentos Imobiliarios'],
            ['KNRI11', 'Kinea Renda Imobiliaria'],
            ['MALL11', 'Multi Shoppings'],
            ['MXRF11', 'Maxi Renda'],
            ['PVBI11', 'VBI Prime Properties'],
            ['RBFF11', 'Rio Bravo Fundo de Fundos'],
            ['RBRF11', 'Rio Bravo Renda Corporativa'],
            ['RBRR11', 'RBR Rendimento High Grade'],
            ['RECR11', 'REC Recebiveis Imobiliarios'],
            ['TGAR11', 'TG Ativo Real'],
            ['VILG11', 'Vinci Logistica'],
            ['VISC11', 'Viscaya'],
            ['XPML11', 'XP Malls'],
            ['XPLG11', 'XP Logistica'],
            ['XPPR11', 'XP Properties'],
        ];
    }

    /** @return list<array{0: string, 1: string}> */
    private function etf(): array
    {
        return [
            ['ACWI11', 'iShares MSCI ACWI'],
            ['BOVA11', 'iShares Ibovespa'],
            ['DIVO11', 'Itau Dividendos'],
            ['GOLD11', 'Itau Ouro'],
            ['IDIV11', 'iShares Dividendos'],
            ['IVVB11', 'iShares S&P 500'],
            ['QQQ11', 'iShares Nasdaq-100'],
            ['SMAL11', 'iShares Small Cap'],
            ['SPXI11', 'Itau S&P 500'],
        ];
    }

    /** @return list<array{0: string, 1: string}> */
    private function cryptos(): array
    {
        return [
            ['BTC-BRL', 'Bitcoin'],
            ['ETH-BRL', 'Ethereum'],
            ['SOL-BRL', 'Solana'],
            ['BNB-BRL', 'BNB'],
            ['ADA-BRL', 'Cardano'],
            ['XRP-BRL', 'XRP'],
            ['DOGE-BRL', 'Dogecoin'],
            ['DOT-BRL', 'Polkadot'],
            ['AVAX-BRL', 'Avalanche'],
            ['LTC-BRL', 'Litecoin'],
            ['LINK-BRL', 'Chainlink'],
            ['MATIC-BRL', 'Polygon'],
            ['UNI-BRL', 'Uniswap'],
            ['ATOM-BRL', 'Cosmos'],
            ['XLM-BRL', 'Stellar'],
            ['TRX-BRL', 'Tron'],
            ['ALGO-BRL', 'Algorand'],
            ['NEAR-BRL', 'Near Protocol'],
            ['FIL-BRL', 'Filecoin'],
            ['SAND-BRL', 'The Sandbox'],
        ];
    }
}
