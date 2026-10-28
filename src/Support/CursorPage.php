<?php

namespace Cabuz\Tembo\Support;

use ArrayIterator;
use Cabuz\Tembo\Tembo;
use Countable;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use Traversable;

/**
 * One page of a cursor-paginated Tembo list.
 *
 * Iterate it for the items on this page, call nextPage() to move on, or use
 * lazy() to stream every item across all pages.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
class CursorPage implements Countable, IteratorAggregate
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        protected Tembo $tembo,
        protected string $path,
        protected array $query,
        protected array $response,
        protected string $itemsKey = 'items',
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        $items = $this->response[$this->itemsKey] ?? [];

        return is_array($items) ? array_values($items) : [];
    }

    public function nextCursor(): ?string
    {
        $cursor = $this->response['nextCursor'] ?? null;

        return is_string($cursor) && $cursor !== '' ? $cursor : null;
    }

    public function hasMore(): bool
    {
        return $this->nextCursor() !== null;
    }

    /**
     * The total number of matches, when the endpoint returns it (some only do
     * with includeTotal=true).
     */
    public function totalCount(): ?int
    {
        $total = $this->response['totalCount'] ?? null;

        return is_int($total) ? $total : null;
    }

    /**
     * The full decoded response, including extra keys such as filterOptions.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->response;
    }

    public function nextPage(): ?self
    {
        $cursor = $this->nextCursor();

        if ($cursor === null) {
            return null;
        }

        return $this->tembo->paginate($this->path, ['cursor' => $cursor] + $this->query, $this->itemsKey);
    }

    /**
     * The items on this page.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function collect(): Collection
    {
        return new Collection($this->items());
    }

    /**
     * Every item across this page and all following pages, fetched on demand.
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function lazy(): LazyCollection
    {
        return new LazyCollection(function () {
            $page = $this;

            while ($page !== null) {
                foreach ($page->items() as $item) {
                    yield $item;
                }

                $page = $page->nextPage();
            }
        });
    }

    public function count(): int
    {
        return count($this->items());
    }

    /**
     * @return Traversable<int, array<string, mixed>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items());
    }
}
