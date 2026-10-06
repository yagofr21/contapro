export type SpendingBaseline = { currency: string; months_count: number; period_start: string | null; period_end: string; monthly_income: string | null; monthly_expenses: string | null };
export const projectSpending = (balance: number, income: number, expenses: number, reduction: number, months: number) => {
    const monthlySavings = income - expenses + Math.min(Math.max(0, reduction), expenses);
    return { monthlySavings, balance: balance + monthlySavings * months, baselineBalance: balance + (income - expenses) * months };
};
export const estimateGoal = (remaining: number, contribution: number): number | null => remaining <= 0 ? 0 : contribution > 0 ? Math.ceil(remaining / contribution) : null;
