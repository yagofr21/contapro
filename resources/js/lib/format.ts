export const formatMoney = (value: string, currency = 'BRL') =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency,
    }).format(Number(value));

export const formatDate = (value: string) =>
    new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(
        new Date(`${value}T00:00:00Z`),
    );

export const formatMonth = (value: string) => {
    const [year, month] = value.split('-').map(Number);

    return new Intl.DateTimeFormat('pt-BR', { month: 'short', year: '2-digit' }).format(
        new Date(Date.UTC(year, month - 1, 1)),
    );
};
