export type Option = {
    value: string;
    label: string;
};

export type Account = {
    id: number;
    name: string;
    type: string;
    currency: string;
    initial_balance: string;
    balance?: string;
    is_archived: boolean;
};

export type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string | null;
    parent_id: number | null;
    parent_name?: string | null;
};

export type Transaction = {
    id: number;
    account_id: number;
    account_name: string;
    currency: string;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    destination_account_id?: number | null;
    type: 'income' | 'expense' | 'transfer' | 'transfer_out';
    amount: string;
    transaction_date: string;
    description: string | null;
    is_transfer: boolean;
};

export type ExpectedIncome = {
    id: number;
    description: string;
    amount: string;
    currency: string;
    account_id: number;
    account_name: string | null;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    expected_date: string;
    received: boolean;
    received_at: string | null;
    transaction_id: number | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};
