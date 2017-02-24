<?php

namespace spec\Rackspace\CloudFiles;

use PhpSpec\ObjectBehavior;
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;

class BackupSpec extends ObjectBehavior
{
    private $client;

    public function let()
    {
        $this->client = new OpenStack(Rackspace::US_IDENTITY_ENDPOINT, [
            'username' => 'foo',
            'apiKey'   => 'bar'
        ]);

        $this->beConstructedWith($this->client, 'DFW', 'test');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rackspace\CloudFiles\Backup');
    }

    public function it_set_and_get_the_container_name()
    {
        $this->container('Test container')->shouldHaveType('Rackspace\CloudFiles\Backup');

        $this->container()->shouldReturn('Test container');
    }

    public function it_set_and_get_the_region_name()
    {
        $this->region('Test region')->shouldHaveType('Rackspace\CloudFiles\Backup');

        $this->region()->shouldReturn('Test region');
    }

    public function it_set_max_file_in_backup()
    {
        $this->max(30)->shouldHaveType('Rackspace\CloudFiles\Backup');
    }


    public function it_set_and_get_the_directory_name()
    {
        $this->directory(__DIR__ . '/backup')->shouldHaveType('Rackspace\CloudFiles\Backup');

        $this->directory()->shouldReturn(__DIR__ . '/backup');
    }

    public function it_squawks_if_the_provided_directory_is_not_found()
    {
        $this->shouldThrow('Rackspace\CloudFiles\Exceptions\DirectoryNotFoundException')
             ->duringDirectory('test');
    }

    public function it_scan_a_directory()
    {
        $this->directory(__DIR__ . '/backup');
        $this->scan('test/daily')->shouldHaveCount(3);
    }

    public function it_upload_a_file_to_container(\Rackspace\CloudFiles\Backup $backup)
    {
        $backup->upload('test/daily/2015-01-01.sql')->shouldBeCalled();
        $backup->getWrappedObject()->upload('test/daily/2015-01-01.sql');
    }
}
