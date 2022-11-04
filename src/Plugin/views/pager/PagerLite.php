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
   * Indicates if the view has next paage.
   *
   * @var bool
   */
  protected $next_page = TRUE;

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

    // If option total pages is set, and it gets to the last page, then
    // next page does not exist.
    if (!empty($this->options['total_pages'])) {
      if (($this->getCurrentPage() + 1) >= $this->options['total_pages']) {
        $this->next_page = FALSE;
      }
    }

    // Set limit to one more item of the default items per page to know
    // if there are more items to display in the next page.
    if ($this->next_page) {
      $limit = $this->getItemsPerPage();
      $this->view->query->setLimit($limit + 1);
    }
  }

  /**
   * Total items based on current page.
   *
   * Always return that there are more items that the current page and
   * calculate if there are next page after the views execution.
   *
   * @return float|int
   * Total items.
   */
  public function getTotalItems() {
    return $this->getCurrentPage() * $this->getItemsPerPage() + 1;
  }

  /**
   * Calculate if there are next page.
   *
   * @param array $result
   *   List of items after run the views query,
   *
   * @return void
   */
  public function preRender(&$result) {

    // If the items on the result are more than the items per page,
    // then it should exist a next page.
    if (count($result) > $this->getItemsPerPage()) {
      // Remove the item to know if there are next page.
      array_pop($result);

      $this->next_page = TRUE;
      // If the page was not set, then set current page.
      if ($this->getCurrentPage() == -1) {
        $this->setCurrentPage();
      }
      // Set the next page.
      $this->setCurrentPage($this->getCurrentPage() + 1);

    }
    else {
      // If there are no next page, set current page to 0
      // or the current page id.
      $this->next_page = FALSE;
      $this->setCurrentPage();
    }
  }

  /**
   * Render the pager lite.
   *
   * @param array $input
   *   Views input.
   *
   * @return array
   *   Render array pager lite.
   */
  public function render($input) {

    return [
      '#theme' => $this->themeFunctions(),
      '#tags' => $this->options['tags'],
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
      '#has_next' => $this->next_page,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function executeCountQuery(&$count_query) {
    // Do not execute count query to avoid timeout
    // due the revision table dimensions.
  }

}
