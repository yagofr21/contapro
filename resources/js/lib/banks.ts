export type BankMeta = {
    value: string;
    label: string;
    color: string;
    initials: string;
    text?: string;
};

export type BankOption = {
    value: string;
    label: string;
    color: string;
    initials: string;
};

export const BANKS: Record<string, BankMeta> = {
    nubank: { value: 'nubank', label: 'Nubank', color: '#820AD1', initials: 'N' },
    itau: { value: 'itau', label: 'Itau', color: '#EC7001', initials: 'IT' },
    bradesco: { value: 'bradesco', label: 'Bradesco', color: '#CC092F', initials: 'BR', text: 'white' },
    santander: { value: 'santander', label: 'Santander', color: '#EC0000', initials: 'ST', text: 'white' },
    banco_do_brasil: { value: 'banco_do_brasil', label: 'Banco do Brasil', color: '#153B86', initials: 'BB', text: 'white' },
    caixa: { value: 'caixa', label: 'Caixa', color: '#0059A9', initials: 'CX', text: 'white' },
    inter: { value: 'inter', label: 'Inter', color: '#FF7A00', initials: 'IN' },
    c6: { value: 'c6', label: 'C6 Bank', color: '#1B1B1B', initials: 'C6', text: 'white' },
    xp: { value: 'xp', label: 'XP Investimentos', color: '#0058A0', initials: 'XP', text: 'white' },
    mercado_pago: { value: 'mercado_pago', label: 'Mercado Pago', color: '#00A7E0', initials: 'MP', text: 'white' },
    picpay: { value: 'picpay', label: 'PicPay', color: '#11C76F', initials: 'PP', text: 'white' },
    btg: { value: 'btg', label: 'BTG Pactual', color: '#00512E', initials: 'BT', text: 'white' },
};

export const bankMeta = (bank: string | null | undefined): BankMeta | null => (bank ? (BANKS[bank] ?? null) : null);

export const bankMetaFrom = (list: BankOption[] | null | undefined, bank: string | null | undefined): BankMeta | null => {
    if (!bank) return null;
    const found = list?.find((item) => item.value === bank);
    if (found) return { ...found };
    return BANKS[bank] ?? null;
};