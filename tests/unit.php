<?php

declare(strict_types=1);

/*
 * Unit tests for the pure domain rules (no server or database needed).
 *
 *   php tests/unit.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Domain\BloodGroup as G;
use App\Domain\Priority;
use App\Domain\RequestStatus as S;

$passed = 0;
$failed = 0;

function check(string $label, bool $ok, mixed $got = null): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? "  \033[32m✓\033[0m " : "  \033[31m✗\033[0m ") . $label
        . ($ok || $got === null ? '' : '  → got ' . json_encode($got)) . PHP_EOL;
}

$codes = static fn (array $groups) => array_map(static fn (G $g) => $g->value, $groups);

echo "\033[1mCompatibility\033[0m" . PHP_EOL;
check('O- can receive only O-', $codes(G::ONeg->canReceiveFrom()) === ['O-']);
check('AB+ can receive from every group', count(G::ABPos->canReceiveFrom()) === 8);
check('A- can receive A- and O-', $codes(G::ANeg->canReceiveFrom()) === ['A-', 'O-']);
check('Rh- patients never get Rh+ blood', !G::BNeg->isCompatibleDonor(G::BPos) && !G::ABNeg->isCompatibleDonor(G::OPos));
check('A patient cannot receive B', !G::APos->isCompatibleDonor(G::BPos));

echo PHP_EOL . "\033[1mPreferred donor order\033[0m" . PHP_EOL;
$stock = ['A+' => 4, 'A-' => 7, 'B+' => 9, 'B-' => 5, 'AB+' => 6, 'AB-' => 3, 'O+' => 12, 'O-' => 9];
check('Own group comes first', G::APos->preferredDonors($stock)[0] === G::APos);
check('O- comes last when compatible', array_slice($codes(G::APos->preferredDonors($stock)), -1) === ['O-']);
check('Other groups are ordered by stock, most first', $codes(G::APos->preferredDonors($stock)) === ['A+', 'O+', 'A-', 'O-'], $codes(G::APos->preferredDonors($stock)));
check('O- patient: only O-', $codes(G::ONeg->preferredDonors($stock)) === ['O-']);
check('O+ patient: O+ then O-', $codes(G::OPos->preferredDonors($stock)) === ['O+', 'O-']);

echo PHP_EOL . "\033[1mSuggested allocation\033[0m" . PHP_EOL;
check('Enough of the own group: all from it', G::APos->suggestAllocation(3, $stock) === ['A+' => 3]);
check('Short: tops up from the best-stocked compatible group', G::APos->suggestAllocation(6, $stock) === ['A+' => 4, 'O+' => 2], G::APos->suggestAllocation(6, $stock));
check('Uses O- only after everything else', G::ANeg->suggestAllocation(10, $stock) === ['A-' => 7, 'O-' => 3], G::ANeg->suggestAllocation(10, $stock));
check('Not enough compatible stock: partial', G::ONeg->suggestAllocation(20, $stock) === ['O-' => 9]);
check('Nothing compatible in stock: empty', G::ONeg->suggestAllocation(2, ['O-' => 0]) === []);
check('Missing stock entries count as zero', G::BNeg->suggestAllocation(2, ['O-' => 5]) === ['O-' => 2]);
check('Never allocates more than requested', array_sum(G::ABPos->suggestAllocation(5, $stock)) === 5);

echo PHP_EOL . "\033[1mPriority & lifecycle\033[0m" . PHP_EOL;
check('Emergency outranks urgent outranks routine', Priority::Emergency->rank() < Priority::Urgent->rank() && Priority::Urgent->rank() < Priority::Routine->rank());
check('Pending can be dispatched or cancelled', S::Pending->canTransitionTo(S::InTransit) && S::Pending->canTransitionTo(S::Cancelled));
check('Pending cannot jump straight to received', !S::Pending->canTransitionTo(S::Completed));
check('In transit can be received or recalled', S::InTransit->canTransitionTo(S::Completed) && S::InTransit->canTransitionTo(S::Cancelled));
check('Received and cancelled are final', !S::Completed->canTransitionTo(S::Cancelled) && !S::Cancelled->canTransitionTo(S::InTransit));
check('Completed is labelled “Received”', S::Completed->label() === 'Received');

echo PHP_EOL . ($failed === 0 ? "\033[32m" : "\033[31m") . "{$passed} passed, {$failed} failed\033[0m" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
