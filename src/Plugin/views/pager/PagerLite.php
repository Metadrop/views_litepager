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
  protected bool $nextPage = TRUE;

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
        $this->nextPage = FALSE;
      }
    }

    // Set limit to one more item of the default items per page to know
    // if there are more items to display in the next page.
    if ($this->nextPage) {
      $limit = $this->getItemsPerPage();
      $this->view->query->setLimit($limit + 1);
    }
  }

  /**
   * Total items based on current page.
   *
   * If there are next page return the items per page multiplying it
   * by the current page plush one, indicating that there are more
   * items to show.
   *
   * @return float|int
   *   Total items.
   */
  public function getTotalItems() {
    $items_per_page = $this->getItemsPerPage();

    if (!($this->nextPage)) {
      return $items_per_page;
    }

    $result = $items_per_page;
    if ($this->getCurrentPage() == 0) {
      $result = $items_per_page + 1;
    }
    elseif ($this->getCurrentPage() > 0) {
      $result = $this->getCurrentPage() * $items_per_page + 1;
    }

    return $result;
  }

  /**
   * Calculate if there are next page.
   */
  public function preRender(&$result) {

    // If the items on the result are more than the items per page,
    // then it should exist a next page.
    if (count($result) > $this->getItemsPerPage()) {
      // Remove the item to know if there are next page.
      array_pop($result);

      $this->nextPage = TRUE;
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
      $this->nextPage = FALSE;
      $this->setCurrentPage();
    }
  }

  /**
   * Render the pager lite.
   *
   * @param mixed $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
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
      '#has_next' => $this->nextPage,
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
