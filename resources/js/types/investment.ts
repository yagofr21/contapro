export type PortfolioSummary = {
    id: number;
    name: string;
    currency: string;
    holdings_count: number;
    cost: string;
    current_value: string;
    market_return: string;
    realized_profit_loss: string;
    net_income: string;
    total_return: string;
};

export type Portfolio = { id: number; name: string; currency: string };

export type PortfolioOption = Portfolio;

export type Broker = {
    id: number;
    name: string;
    is_active: boolean;
    transactions_count?: number;
};

export type BrokerOption = Broker;

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
    broker_id?: number | null;
    broker_name?: string | null;
    type: 'buy' | 'dividend' | 'interest' | 'sell' | 'split';
    quantity: string;
    unit_price: string;
    fees: string;
    split_from: string | null;
    split_to: string | null;
    gross_amount: string | null;
    net_amount: string | null;
    realized_cost_basis: string | null;
    realized_profit_loss: string | null;
    total_amount?: string | null;
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
