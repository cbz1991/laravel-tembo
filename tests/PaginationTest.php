<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Facades\Tembo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class PaginationTest extends TestCase
{
    public function test_a_page_exposes_its_items_cursor_and_total(): void
    {
        Http::fake(['*' => Http::response([
            'items' => [['id' => 'a'], ['id' => 'b']],
            'nextCursor' => 'c2',
            'totalCount' => 3,
        ])]);

        $page = Tembo::sessions()->list(['includeTotal' => true]);

        $this->assertCount(2, $page);
        $this->assertSame([['id' => 'a'], ['id' => 'b']], $page->items());
        $this->assertSame(['a', 'b'], $page->collect()->pluck('id')->all());
        $this->assertSame('c2', $page->nextCursor());
        $this->assertTrue($page->hasMore());
        $this->assertSame(3, $page->totalCount());
        $this->assertSame(['a', 'b'], array_column(iterator_to_array($page), 'id'));
    }

    public function test_lazy_walks_every_page_keeping_the_original_filters(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['items' => [['id' => 'a'], ['id' => 'b']], 'nextCursor' => 'c2'])
            ->push(['items' => [['id' => 'c']], 'nextCursor' => null])]);

        $ids = Tembo::repositories()->list(['search' => 'api', 'limit' => 2])->lazy()->pluck('id')->all();

        $this->assertSame(['a', 'b', 'c'], $ids);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/repositories?cursor=c2&search=api&limit=2');
    }

    public function test_the_last_page_has_no_next_page(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null])]);

        $page = Tembo::agents()->list();

        $this->assertFalse($page->hasMore());
        $this->assertNull($page->nextPage());
        $this->assertNull($page->totalCount());
        $this->assertSame(0, $page->lazy()->count());
    }

    public function test_pins_read_their_items_from_the_resources_key(): void
    {
        Http::fake(['*' => Http::response(['resources' => [['id' => 'p1']], 'nextCursor' => null])]);

        $this->assertSame([['id' => 'p1']], Tembo::pins()->list()->items());
    }

    public function test_raw_keeps_extra_response_keys(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null, 'filterOptions' => ['x' => 1]])]);

        $this->assertSame(['x' => 1], Tembo::agents()->list()->raw()['filterOptions']);
    }
}
