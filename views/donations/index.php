<?php
/**
 * @var array{q: string, type: ?string} $filters
 * @var App\Core\Paginator $page
 * @var array{total_bags: int, month_bags: int, donors: int} $stats
 * @var array<string, int> $stock
 */
use App\Domain\BloodGroup;

$filtered = $filters['q'] !== '' || $filters['type'];
?>
<div class="page-head">
    <div>
        <h1>Donations</h1>
        <p><?= $stats['total_bags'] ?> bags collected from <?= plural($stats['donors'], 'donor') ?> · <?= $stats['month_bags'] ?> this month</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-dialog="donation-create"><?= icon('plus', 'icon-sm') ?> Record donation</button>
    </div>
</div>

<form method="get" action="<?= e(url('/donations')) ?>" class="toolbar" role="search" data-auto-submit>
    <?php if ($filters['type']): ?><input type="hidden" name="type" value="<?= e($filters['type']) ?>"><?php endif; ?>
    <label class="search">
        <span class="sr-only">Search donors</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search donor name or contact…">
    </label>
    <noscript><button class="btn" type="submit">Search</button></noscript>
</form>

<div class="chips" style="margin-bottom:14px" aria-label="Filter by blood type">
    <a class="chip <?= $filters['type'] ? '' : 'is-active' ?>" href="<?= e(url_with(['type' => null, 'page' => null])) ?>">All types</a>
    <?php foreach (BloodGroup::codes() as $code): ?>
        <a class="chip <?= $filters['type'] === $code ? 'is-active' : '' ?>" href="<?= e(url_with(['type' => $code, 'page' => null])) ?>">
            <?= e($code) ?> <span class="n"><?= (int) ($stock[$code] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="card table-wrap">
    <?php if (!$page->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon($filtered ? 'search' : 'heart', 'icon-lg') ?></div>
            <h3><?= $filtered ? 'No matching donations' : 'No donations yet' ?></h3>
            <p><?= $filtered ? 'Try a different name or blood type.' : 'Record the first donation to start building stock.' ?></p>
            <?php if ($filtered): ?><a class="btn" style="margin-top:8px" href="<?= e(url('/donations')) ?>">Clear filters</a><?php endif; ?>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Donor</th>
                    <th scope="col">Type</th>
                    <th scope="col" class="num">Bags</th>
                    <th scope="col">Donated</th>
                    <th scope="col">Medical notes</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($page->items as $d):
                    $payload = [
                        'id' => $d['id'], 'first_name' => $d['first_name'], 'middle_name' => $d['middle_name'] ?? '',
                        'last_name' => $d['last_name'], 'sex' => $d['sex'], 'age' => $d['age'], 'contact' => $d['contact'],
                        'address' => $d['address'], 'blood_type' => $d['blood_type'], 'bags' => $d['bags'],
                        'medical_status' => $d['medical_status'],
                    ];
                ?>
                    <tr>
                        <td class="cell-full">
                            <div class="cell-person">
                                <span class="avatar avatar-sm"><?= e(initials($d['first_name'] . ' ' . $d['last_name'])) ?></span>
                                <div class="list-main">
                                    <div class="list-title"><?= e($d['donor_name']) ?></div>
                                    <div class="list-sub"><?= e($d['sex']) ?>, <?= (int) $d['age'] ?> · <?= e($d['contact']) ?> · <?= e($d['address']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Type"><span class="blood-code" style="min-width:40px;height:26px;font-size:13px"><?= e($d['blood_type']) ?></span></td>
                        <td data-label="Bags" class="num"><?= (int) $d['bags'] ?></td>
                        <td data-label="Donated">
                            <?= e(fmt_date($d['donated_at'])) ?>
                            <div class="list-sub"><?= $d['hospital_name'] ? 'via ' . e($d['hospital_name']) : 'at blood bank' ?></div>
                        </td>
                        <td data-label="Medical notes"><span class="list-sub" style="white-space:normal"><?= e($d['medical_status']) ?></span></td>
                        <td class="cell-full">
                            <div class="row-actions">
                                <button type="button" class="btn btn-sm" data-edit-donation='<?= e(json_encode($payload)) ?>'>
                                    <?= icon('pencil', 'icon-sm') ?> Edit
                                </button>
                                <form method="post" action="<?= e(url("/donations/{$d['id']}/delete")) ?>"
                                      data-confirm="Remove this donation from <?= e($d['donor_name']) ?>? <?= e(plural((int) $d['bags'], 'bag')) ?> of <?= e($d['blood_type']) ?> will be taken out of stock."
                                      data-confirm-label="Remove donation" data-confirm-danger>
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Delete donation by <?= e($d['donor_name']) ?>"><?= icon('trash', 'icon-sm') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
</section>

<?php $dialogId = 'donation-create'; require BASE_PATH . '/views/partials/donation-dialog.php'; ?>
<?php $dialogId = 'donation-edit'; require BASE_PATH . '/views/partials/donation-dialog.php'; ?>
