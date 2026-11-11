<?php /** @var array $cms page prepared by PageService::render() */ ?>
<article class="cms-page">
  <?php if (!$cms['isPublished']): ?>
    <div class="container"><div class="alert alert-warning mt-3" role="status">Draft preview: visitors can’t see this page until you publish it.</div></div>
  <?php endif; ?>
  <?php if ($cms['blocks']): ?>
    <?php component('sections/page-blocks', ['blocks' => $cms['blocks']]); ?>
  <?php else: ?>
    <div class="container page"><div class="state-box"><h1><?= e($cms['title']) ?></h1><p>This page has no content yet.</p></div></div>
  <?php endif; ?>
</article>
