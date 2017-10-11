<?php

use Mockery as m;

class IronQueueTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoIron()
    {
        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('postMessage')
            ->once()
            ->with('default', 'encrypted', [])
            ->andReturn((object) ['id' => 1]);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('encrypt')
            ->once()
            ->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => [1, 2, 3], 'queue' => 'default']))
            ->andReturn('encrypted');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', true);
        $queue->setEncrypter($crypt);
        $queue->push('foo', [1, 2, 3]);
    }

    public function testPushProperlyPushesJobOntoIronWithoutEncryption()
    {
        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('postMessage')
            ->once()
            ->with('default', json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => [1, 2, 3], 'queue' => 'default']), [])
            ->andReturn((object) ['id' => 1]);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('encrypt')
            ->never();

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default');
        $queue->setEncrypter($crypt);
        $queue->push('foo', [1, 2, 3]);
    }

    public function testDelayedPushProperlyPushesJobOntoIron()
    {
        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('postMessage')
            ->once()
            ->with('default', 'encrypted', ['delay' => 5])
            ->andReturn((object) ['id' => 1]);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('encrypt')
            ->once()
            ->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => [1, 2, 3], 'queue' => 'default']))
            ->andReturn('encrypted');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', true);
        $queue->setEncrypter($crypt);
        $queue->later(5, 'foo', [1, 2, 3]);
    }

    public function testDelayedPushProperlyPushesJobOntoIronWithTimestamp()
    {
        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('postMessage')
            ->once()
            ->with('default', 'encrypted', ['delay' => 5,])
            ->andReturn((object) ['id' => 1]);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('encrypt')
            ->once()
            ->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => [1, 2, 3], 'queue' => 'default']))
            ->andReturn('encrypted');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', true);
        $queue->setEncrypter($crypt);
        $queue->later(5, 'foo', [1, 2, 3]);
    }

    public function testPopProperlyPopsJobOffOfIron()
    {
        $job = m::mock('IronMQ_Message');
        $job->body = 'foo';

        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('reserveMessage')
            ->once()
            ->with('default', 60)
            ->andReturn($job);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('decrypt')
            ->once()
            ->with('foo')
            ->andReturn('foo');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', true);
        $queue->setEncrypter($crypt);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $this->assertInstanceOf('Collective\IronQueue\Jobs\IronJob', $queue->pop());
    }

    public function testPopProperlyPopsJobOffOfIronWithCustomTimeout()
    {
        $job = m::mock('IronMQ_Message');
        $job->body = 'foo';

        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('reserveMessage')
            ->once()
            ->with('default', 120)
            ->andReturn($job);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('decrypt')
            ->once()
            ->with('foo')
            ->andReturn('foo');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', true, 120);
        $queue->setEncrypter($crypt);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $this->assertInstanceOf('Collective\IronQueue\Jobs\IronJob', $queue->pop());
    }

    public function testPopProperlyPopsJobOffOfIronWithoutEncryption()
    {
        $job = m::mock('IronMQ_Message');
        $job->body = 'foo';

        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('reserveMessage')
            ->once()
            ->with('default', 60)
            ->andReturn($job);

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('decrypt')
            ->never();

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default');
        $queue->setEncrypter($crypt);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $this->assertInstanceOf('Collective\IronQueue\Jobs\IronJob', $queue->pop());
    }

    /**
     * @expectedException IronCore\HttpException
     */
    public function testDeleteJobWithExpiredReservationIdThrowsAnException()
    {
        $iron = m::mock('IronMQ\IronMQ');
        $iron->shouldReceive('deleteMessage')
            ->with('default', 1, 'def456')
            ->andThrow('IronCore\HttpException', '{"msg":"Reservation has timed out"}');

        $queue = new Collective\IronQueue\IronQueue($iron, m::mock('Illuminate\Http\Request'), 'default', false, 30);
        // 'def456' refers to a reservation id that expired
        $queue->deleteMessage('default', 1, 'def456');
    }

    public function testPushedJobsCanBeMarshaled()
    {
        $content = json_encode(['foo' => 'bar']);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('header')
            ->once()
            ->with('iron-message-id')
            ->andReturn('message-id');
        $request->shouldReceive('getContent')
            ->once()
            ->andReturn($content);

        $mockIronJob = m::mock('StdClass');
        $mockIronJob->shouldReceive('fire')
            ->once();

        $job = (object) ['id' => 'message-id', 'body' => json_encode(['foo' => 'bar']), 'pushed' => true];
        $iron = m::mock('IronMQ\IronMQ');

        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $crypt->shouldReceive('decrypt')
            ->once()
            ->with($content)
            ->andReturn($content);

        $queue = $this->getMock('Collective\IronQueue\IronQueue', ['createPushedIronJob'], [$iron, $request, 'default', true]);
        $queue->setEncrypter($crypt);
        $queue->expects($this->once())
            ->method('createPushedIronJob')
            ->with($this->equalTo($job))
            ->will($this->returnValue($mockIronJob));

        $response = $queue->marshal();

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
