<?php

declare(strict_types = 1);

namespace Blar\Beanstalk;

use Blar\Queue\Queue;
use Blar\Sockets\NetworkSocket;
use Blar\Streams\Stream;
use Blar\Streams\TcpStream;

class Beanstalk implements Queue {

	private Socket $socket;

	public function __construct(Socket $socket) {
		$this->socket = $socket;
	}

	protected function setSocket(NetworkSocket $socket): void {
		$this->socket = $socket;
	}

	protected function getSocket(): NetworkSocket {
		return $this->socket;
	}

	protected function getStream(): Stream {
	    return new TcpStream($this->getSocket());
    }

	public function useTube(string $tubeName): Tube {
		return new Tube($tubeName, $this->getStream());
	}

	public function getStatistics() {
	    $stream = $this->getStream();
	    $stream->write("stats\r\n");
	    $response = $stream->readUntil(255, "\r\n");
	    [$status, $length] = explode(' ', $response);
	    return $stream->read((int) $length);

    }

}
