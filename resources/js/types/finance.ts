export type Option = {
    value: string;
    label: string;
};

export type CreditCardInfo = {
    id: number;
    name: string;
    currency: string;
    has_limit: boolean;
    credit_limit: string | null;
    utilized: string | null;
    available: string | null;
    over_limit: boolean;
    utilization: number | null;
    initial_debt: string;
    overdue_balance: string;
    current_invoice: string;
    future_invoices: string;
    total_debt: string;
    credit_balance: string;
    current_invoice_start: string;
    current_invoice_end: string;
    next_closing: string;
    next_due: string;
    status: 'open' | 'closing_soon' | 'due_soon' | 'overdue';
    future_by_month: { month: string; amount: string }[];
    current_purchases?: { date: string; description: string; amount: string; remaining: string }[];
    current_refunds?: { date: string; description: string; amount: string; remaining: string }[];
    future_installments?: { date: string; description: string; amount: string; remaining: string; cycle_end: string; due_date: string }[];
    payments?: { date: string; amount: string; description: string | null }[];
    history?: { cycle: string; due_date: string; amount: string }[];
};

export type Account = {
    id: number;
    name: string;
    bank: string | null;
    color: string | null;
    type: string;
    currency: string;
    initial_balance: string;
    balance?: string;
    credit_limit?: string | null;
    credit_closing_day?: number | null;
    credit_due_day?: number | null;
    credit_card?: CreditCardInfo | null;
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

export type Bank = {
    id: number;
    code: string;
    label: string;
    color: string;
    initials: string;
    is_active: boolean;
    accounts_count?: number;
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
    is_future: boolean;
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

export type InstallmentScheduleRow = {
    number: number;
    due_date: string;
    amount: string;
    status: 'pago' | 'pendente';
};

export type Installment = {
    id: number;
    account_id: number;
    account_name: string;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    type: 'income' | 'expense';
    amount: string;
    total_amount: string;
    total_count: number;
    paid_count: number;
    remaining_count: number;
    current_parcela: number | null;
    total_paid: string;
    total_remaining: string;
    next_due_date: string;
    description: string | null;
    is_finished: boolean;
    schedule: InstallmentScheduleRow[];
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
