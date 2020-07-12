<?php

declare(strict_types = 1);

namespace Blar\Beanstalk;

use Blar\Streams\Stream;
use LimitIterator;
use RuntimeException;
use IteratorAggregate;
use Throwable;

class Tube implements IteratorAggregate {

	const STATUS_INSERTED = 'INSERTED';

	const STATUS_RESERVED = 'RESERVED';

	const STATUS_DELETED = 'DELETED';

	const STATUS_RELEASED = 'RELEASED';

	private string $name;

	private Stream $stream;

    public function __construct(string $name, Stream $stream) {
        $this->name = $name;
        $this->stream = $stream;

        $this->writeFormat("use %s\r\n", $this->getName());
        $this->readLine();

        $this->writeFormat("watch %s\r\n", $this->getName());
        $this->readLine();
    }

    public function getIterator(): TubeIterator {
        return new TubeIterator($this);
    }

    private function write(string $data) {
        $this->stream->write($data);
    }

    private function writeFormat(string $format, ...$values) {
	    $this->stream->writeFormat($format, ...$values);
    }

    public function read(int $length) {
	    return $this->stream->read($length);
    }

    public function readLine() {
	    return $this->stream->readUntil(255, "\r\n");
    }

    public function readStatus(): array {
	    $line = $this->readLine();
	    return explode(' ', $line);
    }

	public function getName(): string {
		return $this->name;
	}

	protected function setName(string $name): void {
		$this->name = $name;
	}

	protected function readJson(int $length) {
		$json = $this->read($length);
		return json_decode($json);
	}

	public function addJob(Job $job): Job {
		$data = json_encode($job->getData());
		$this->writeFormat(
			"put %u %u %u %u\r\n%s\r\n",
			$job->getPriority(),
			$job->getDelay(),
			$job->getTrr(),
			strlen($data),
            $data
		);
		[$response, $id] = $this->readStatus();
		if($response === self::STATUS_INSERTED) {
			return $job->setId((int) $id);
		}
        throw new RuntimeException($response);
	}

	public function reserveJob(int $timeout = 0): ?Job {
        if($timeout) {
            $this->writeFormat("reserve-with-timeout %u\r\n", $timeout);
        }
        else {
            $this->write("reserve\r\n");
        }
		[$response, $id, $length] = $this->readStatus();
		if($response === self::STATUS_RESERVED) {
			$data = $this->readJson($length + 2);
			$job = new Job($data);
			return $job->setId((int) $id);
		}
        if($response === 'TIMED_OUT') {
            return NULL;
        }
		throw new RuntimeException($response);
	}

	public function releaseJob(Job $job): Job {
        $this->writeFormat(
            "release %u %u %u\r\n",
            $job->getId(),
            $job->getPriority(),
            $job->getDelay()
        );
        [$response, $id] = $this->readStatus();
        if($response === self::STATUS_RELEASED) {
            return $job;
        }
        throw new RuntimeException($response);
    }

	public function removeJob(Job $job): Job {
		$this->writeFormat(
			"delete %u\r\n",
			$job->getId()
		);
        [$response] = $this->readStatus();
        if($response === self::STATUS_DELETED) {
            return $job;
        }
        if($response === 'NOT_FOUND') {
            return $job;
        }
        throw new RuntimeException($response);
	}

	public function map(callable $callback) {
        foreach($this as $id => $job) {
            try {
                $callback($job, $id, $this);
                $this->removeJob($job);
            }
            catch(Throwable $exception) {
                var_dump($exception->getMessage());
                var_dump($exception->getFile());
                var_dump($exception->getLine());
                # $job = $job->setDelay(10);
                # $this->releaseJob($job);
                # $this->removeJob($job);
            }
        }
    }

    public function mapMax(int $max, $callback) {
        return new LimitIterator($this, $callback);
    }

}
