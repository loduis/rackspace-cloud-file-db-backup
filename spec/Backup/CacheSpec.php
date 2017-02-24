<?php

namespace spec\Rackspace\CloudFiles\Backup;

use PhpSpec\ObjectBehavior;

class CacheSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(realpath(__DIR__ . '/../backup'));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rackspace\CloudFiles\Backup\Cache');
    }

    public function it_get_the_cache_as_array()
    {
        $this->get()->shouldBeArray();
    }

    public function it_put_the_cache_as_json()
    {
        $this->put([])->shouldReturn(true);
    }
}
