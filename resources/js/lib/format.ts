export const formatMoney = (value: string, currency = 'BRL') =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency,
    }).format(Number(value));

export const formatDecimal = (
    value: string,
    minimumFractionDigits = 0,
    maximumFractionDigits = 8,
) => {
    if (value === '') return '';

    const [integerPart, fractionPart = ''] = value.replace(',', '.').split('.');
    const negative = integerPart.startsWith('-');
    const integer = integerPart.replace('-', '').replace(/^0+(?=\d)/, '') || '0';
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const fraction = fractionPart
        .slice(0, maximumFractionDigits)
        .replace(/0+$/, '')
        .padEnd(minimumFractionDigits, '0');

    return `${negative ? '-' : ''}${grouped}${fraction ? `,${fraction}` : ''}`;
};

export const parseDecimalInput = (value: string) =>
    value.trim().replace(/\s/g, '').replace(/\./g, '').replace(',', '.');

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
