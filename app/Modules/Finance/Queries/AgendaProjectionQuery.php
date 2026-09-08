<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AgendaProjectionQuery
{
    /**
     * Build the agenda of upcoming recurring/installment events together with a
     * projected balance per account over the given horizon.
     *
     * @return array{
     *     events: Collection<int, array<string, mixed>>,
     *     projection: Collection<int, array{
     *         id: int,
     *         name: string,
     *         balance: string,
     *         projected_balance: string,
     *     }>,
     *     horizonDays: int,
     * }
     */
    public function forUser(User $user, int $horizonDays = 60): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $horizonEnd = $today->addDays($horizonDays);

        $events = collect()
            ->merge($this->scheduleEvents($user, $today, $horizonEnd))
            ->merge($this->installmentEvents($user, $today, $horizonEnd))
            ->sortBy(fn (array $event): string => $event['date'])
            ->values();

        $currentBalances = $this->currentBalances($user);
        $projection = $this->projectBalances($currentBalances, $events);

        return [
            'events' => $events,
            'projection' => $projection,
            'horizonDays' => $horizonDays,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function scheduleEvents(User $user, CarbonImmutable $today, CarbonImmutable $horizonEnd): Collection
    {
        $events = collect();

        $user->transactionSchedules()
            ->with(['account:id,name,currency', 'category:id,name,color'])
            ->where('is_active', true)
            ->get()
            ->each(function (TransactionSchedule $schedule) use ($events, $horizonEnd): void {
                $cursor = $schedule->next_run_date->toImmutable();

                while ($cursor->lte($horizonEnd)) {
                    if ($schedule->ends_on !== null && $cursor->gt(CarbonImmutable::parse($schedule->ends_on))) {
                        break;
                    }

                    $events->push([
                        'date' => $cursor->format('Y-m-d'),
                        'kind' => 'schedule',
                        'type' => $schedule->type->value,
                        'amount' => $schedule->amount,
                        'account_id' => $schedule->account_id,
                        'account_name' => $schedule->account?->name,
                        'category_name' => $schedule->category?->name,
                        'description' => $schedule->description,
                        'frequency' => $schedule->frequency->value,
                    ]);

                    $cursor = $schedule->frequency->advance($cursor);
                }
            });

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function installmentEvents(User $user, CarbonImmutable $today, CarbonImmutable $horizonEnd): Collection
    {
        $events = collect();

        $user->installments()
            ->with(['account:id,name,currency', 'category:id,name,color'])
            ->where('remaining_count', '>', 0)
            ->get()
            ->each(function (Installment $installment) use ($events, $horizonEnd): void {
                $cursor = $installment->next_due_date->toImmutable();
                $paid = $installment->total_count - $installment->remaining_count;

                for ($i = 0; $i < $installment->remaining_count; $i++) {
                    if ($cursor->gt($horizonEnd)) {
                        break;
                    }

                    $events->push([
                        'date' => $cursor->format('Y-m-d'),
                        'kind' => 'installment',
                        'type' => $installment->type->value,
                        'amount' => $installment->amount,
                        'account_id' => $installment->account_id,
                        'account_name' => $installment->account?->name,
                        'category_name' => $installment->category?->name,
                        'description' => $installment->description,
                        'frequency' => 'monthly',
                    ]);

                    $cursor = $cursor->addMonth();
                }
            });

        return $events;
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     balance: string,
     * }>
     */
    private function currentBalances(User $user): Collection
    {
        return $user->financialAccounts()
            ->withSum([
                'transactions as credits' => fn ($query) => $query->whereIn('type', [
                    TransactionType::Income->value,
                    TransactionType::TransferIn->value,
                ]),
            ], 'amount')
            ->withSum([
                'transactions as debits' => fn ($query) => $query->whereIn('type', [
                    TransactionType::Expense->value,
                    TransactionType::TransferOut->value,
                ]),
            ], 'amount')
            ->orderBy('is_archived')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => $this->stringify(bcsub(
                    bcadd((string) $account->initial_balance, (string) ($account->credits ?? 0), 4),
                    (string) ($account->debits ?? 0),
                    4,
                )),
            ]);
    }

    /**
     * Compute the projected balance per account by applying the net effect of
     * the upcoming events to the current balance.
     *
     * @param Collection<int, array{
     *     id: int,
     *     name: string,
     *     balance: string,
     * }> $currentBalances
     * @param  Collection<int, array<string, mixed>>  $events
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     balance: string,
     *     projected_balance: string,
     * }>
     */
    private function projectBalances(Collection $currentBalances, Collection $events): Collection
    {
        $projection = [];

        foreach ($currentBalances->all() as $account) {
            /** @var array{id: int, name: string, balance: string} $account */
            $net = $events->filter(fn (array $event): bool => $event['account_id'] === $account['id'])
                ->reduce(fn (string $carry, array $event): string => bcadd(
                    $carry,
                    in_array($event['type'], [TransactionType::Income->value, TransactionType::TransferIn->value], true)
                        ? $event['amount']
                        : bcmul($event['amount'], '-1', 4),
                    4,
                ), '0');

            $projection[] = [
                'id' => $account['id'],
                'name' => $account['name'],
                'balance' => $this->stringify($account['balance']),
                'projected_balance' => $this->stringify(bcadd($account['balance'], $net, 4)),
            ];
        }

        return collect($projection);
    }

    private function stringify(mixed $value): string
    {
        return (string) $value;
    }
}
