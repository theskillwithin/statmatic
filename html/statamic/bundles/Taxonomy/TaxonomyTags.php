<?php

namespace Statamic\Addons\Taxonomy;

use Statamic\API\Helper;
use Statamic\API\Str;
use Statamic\API\Content;
use Statamic\Extend\Tags;

class TaxonomyTags extends Tags
{
    /**
     * @var \Statamic\Data\Taxonomies\TermCollection
     */
    protected $taxonomies;

    public function __call($method, $args)
    {
        $group = explode(':', $this->tag)[1];

        $this->taxonomies = Content::taxonomyTerms($group, null, true);

        $this->filter();

        if ($this->taxonomies->isEmpty()) {
            return $this->parse(['no_results' => true]);
        }

        $data = $this->taxonomies->toArray();

        return $this->parseLoop($data);
    }

    protected function filter()
    {
        if ($this->get('show') !== 'all') {
            $this->filterMinCount();
            $this->filterCollections();
            $this->filterPages();
            $this->filterUnpublished();
            $this->filterFuture();
            $this->filterPast();
            $this->filterSince();
            $this->filterUntil();
            $this->filterConditions();
        }

        $this->sort();

        // Limiting and offsetting should be done after all other filters
        $this->limit();
    }

    private function filterMinCount()
    {
        $min = $this->getInt('min_count', 0);

        if ($min > 0) {
            $this->taxonomies = $this->taxonomies->filter(function($taxonomy) use ($min) {
                return $taxonomy->count() > $min;
            });
        }
    }

    private function filterCollections()
    {
        if (! $collections = $this->get(['collection', 'collections'])) {
            return;
        }

        $collections = Helper::explodeOptions($collections);

        $this->taxonomies = $this->taxonomies->filterContent(function($content) use ($collections) {
            return $content->filter(function($item) use ($collections) {
                return in_array($item->collectionName(), $collections);
            });
        });
    }

    private function filterPages()
    {
        if (! $pages = $this->get(['page', 'pages'])) {
            return;
        }

        $pages = Helper::explodeOptions($pages);
        $collections = [];

        foreach ($pages as $page) {
            $url = Str::ensureLeft($page, '/');

            if ($content = Content::pageRaw($url)) {
                $collections[] = $content->entriesCollection();
            }
        }

        $this->taxonomies = $this->taxonomies->filterContent(function($content) use ($collections) {
            return $content->filter(function($item) use ($collections) {
                return in_array($item->collectionName(), $collections);
            });
        });
    }

    private function filterUnpublished()
    {
        if (! $this->getBool('show_unpublished', false)) {
            $this->taxonomies = $this->taxonomies->filterContent(function($content) {
                return $content->removeUnpublished();
            });
        }
    }

    private function filterFuture()
    {
        if (! $this->getBool('show_future', false)) {
            $this->taxonomies = $this->taxonomies->filterContent(function($content) {
                return $content->removeFuture();
            });
        }
    }

    private function filterPast()
    {
        if (! $this->getBool('show_past', true)) {
            $this->taxonomies = $this->taxonomies->filterContent(function($content) {
                return $content->removePast();
            });
        }
    }

    private function filterSince()
    {
        if ($since = $this->get('since')) {
            $this->taxonomies = $this->taxonomies->filterContent(function($content) use ($since) {
                return $content->removeBefore($since);
            });
        }
    }

    private function filterUntil()
    {
        if ($until = $this->get('until')) {
            $this->taxonomies = $this->taxonomies->filterContent(function($content) use ($until) {
                return $content->removeAfter($until);
            });
        }
    }

    private function limit()
    {
        $limit = $this->getInt('limit');
        $limit = ($limit == 0) ? $this->taxonomies->count() : $limit;
        $offset = $this->getInt('offset');

        $this->taxonomies = $this->taxonomies->splice($offset, $limit);
    }

    private function filterConditions()
    {
        if ($filter = $this->get('filter')) {
            // If a "filter" parameter has been specified, we want to use a custom filter class
            // to filter *the taxonomy collection*. If they want to use a custom filter to
            // filter the actual content collection, they can do it from the filter.
            $this->taxonomies = collection_filter($filter, $this->taxonomies)->filter();
        } else {
            // No filter parameter has been specified, so we should filter the content by condition parameters
            $conditions = array_filter_key($this->parameters, function ($key) {
                return Str::contains($key, ':');
            });

            $this->taxonomies = $this->taxonomies->filterContent(function($content) use ($conditions) {
                return $content->conditions($conditions);
            });
        }
    }

    private function sort()
    {
        if ($sort = $this->get('sort')) {
            $this->taxonomies = $this->taxonomies->multisort($sort);
        } else {
            // No sort specified? We want to sort by count.
            $this->taxonomies = $this->taxonomies->sortByCount();
        }
    }
}
