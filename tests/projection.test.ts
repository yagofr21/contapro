import { test } from 'node:test';
import assert from 'node:assert/strict';
import { estimateGoal, projectSpending } from '../resources/js/lib/projections.ts';
test('spending scenarios apply monthly savings without adding scheduled expenses twice', () => {
    assert.deepEqual(projectSpending(2000, 3000, 2500, 200, 12), { monthlySavings: 700, balance: 10400, baselineBalance: 8000 });
});
test('goals round up months and reject zero or negative contributions', () => {
    assert.equal(estimateGoal(8000, 500), 16);
    assert.equal(estimateGoal(8000, 700), 12);
    assert.equal(estimateGoal(8000, 0), null);
    assert.equal(estimateGoal(8000, -50), null);
    assert.equal(estimateGoal(0, 0), 0);
});
