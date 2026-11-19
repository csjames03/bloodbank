<?php /** @var App\Core\Paginator $page */ ?>
<?php if ($page->total > 0): ?>
    <nav class="pagination" aria-label="Pagination">
        <span>Showing <strong><?= $page->from() ?>–<?= $page->to() ?></strong> of <strong><?= $page->total ?></strong></span>
        <?php if ($page->hasPages()): ?>
            <div class="pages">
                <?php if ($page->page > 1): ?>
                    <a class="btn btn-sm" href="<?= e(url_with(['page' => $page->page - 1])) ?>" rel="prev"><?= icon('chevron-left', 'icon-sm') ?> Prev</a>
                <?php endif; ?>
                <span class="btn btn-sm btn-ghost" aria-current="page"><?= $page->page ?> / <?= $page->pages ?></span>
                <?php if ($page->page < $page->pages): ?>
                    <a class="btn btn-sm" href="<?= e(url_with(['page' => $page->page + 1])) ?>" rel="next">Next <?= icon('chevron-right', 'icon-sm') ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </nav>
<?php endif; ?>
