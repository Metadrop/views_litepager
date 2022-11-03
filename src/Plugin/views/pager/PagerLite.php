<?php

namespace Drupal\views_litepager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\Full;

/**
 * Plugin for views without count query.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "pager_lite",
 *   title = @Translation("Pager Lite - Without expensive count query"),
 *   short_title = @Translation("Lite"),
 *   help = @Translation("A simple pager that doesn\'t require an expensive count query."),
 *   theme = "pager_lite"
 * )
 */
class PagerLite extends Full {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural(
        $this->options['items_per_page'],
        'Lite pager, @count item, skip @skip',
        'Lite pager, @count items, skip @skip',
        [
          '@count' => $this->options['items_per_page'],
          '@skip' => $this->options['offset'],
        ]
      );
    }

    return $this->formatPlural(
      $this->options['items_per_page'],
      'Lite pager, @count item',
      'Lite pager, @count items',
      ['@count' => $this->options['items_per_page']]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function useCountQuery() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    $next_page = TRUE;
    if (!empty($this->options['total_pages'])) {
      if (($this->getCurrentPage() + 1) >= $this->options['total_pages']) {
        $next_page = FALSE;
      }
    }
    if ($next_page) {
      $limit = $this->getItemsPerPage();
      $this->view->query->setLimit($limit + 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$result) {
    if (count($result) > $this->options['items_per_page']) {
      array_pop($result);
      $this->next_page = TRUE;
      if ($this->getCurrentPage() == -1) {
        $this->setCurrentPage();
      }
      $this->setCurrentPage($this->getCurrentPage() + 1);
    }
    else {
      $this->setCurrentPage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {

    if (empty($this->getCurrentPage()) && (!isset($this->next_page) || $this->next_page != TRUE)) {
      return '';
    }
    return [
      '#theme' => $this->themeFunctions(),
      '#tags' => $this->options['tags'],
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function executeCountQuery(&$count_query) {
    // Do not execute count query to avoid timeout
    // because of revision table dimensions.
  }

}
