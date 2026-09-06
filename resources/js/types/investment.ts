export type PortfolioSummary = {
    id: number;
    name: string;
    currency: string;
    holdings_count: number;
    cost: string;
    current_value: string;
    return: string;
};

export type Portfolio = { id: number; name: string; currency: string };

export type PortfolioOption = Portfolio;

export type Holding = {
    id: number;
    asset_id: number;
    symbol: string;
    name: string;
    type: string;
    currency: string;
    quantity: string;
    average_cost: string;
    current_price: string;
    cost: string;
    current_value: string;
    return: string;
};

export type InvestmentTransaction = {
    id: number;
    asset_id?: number;
    asset_symbol?: string;
    asset_name?: string;
    type: 'buy' | 'dividend' | 'interest' | 'sell';
    quantity: string;
    unit_price: string;
    fees: string;
    gross_amount: string | null;
    net_amount: string | null;
    transaction_date?: string;
    date?: string;
    note: string | null;
};

export type AssetOption = {
    id: number;
    symbol: string;
    name: string;
    currency: string;
    market: string;
    available_quantity: string;
};

export type MarketAsset = {
    id: number;
    symbol: string;
    name: string;
    type: string;
    market: string;
    currency: string;
    is_active: boolean;
    can_refresh: boolean;
    price: string | null;
    price_date: string | null;
};
