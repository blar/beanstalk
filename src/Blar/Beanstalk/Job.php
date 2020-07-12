<?php

declare(strict_types = 1);

namespace Blar\Beanstalk;

class Job {

    private int $id;

	private int $priority = 65335;

	private int $delay = 0;

	private int $trr = 60;

	private $data;

	public function __construct($data) {
	    $this->data = $data;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): Job {
        $this->id = $id;
        return $this;
    }

	public function getPriority(): int {
		return $this->priority;
	}

	public function setPriority(int $priority): Job {
		$this->priority = $priority;
		return $this;
	}

	public function getDelay(): int {
		return $this->delay;
	}

	public function setDelay(int $delay): Job {
		$this->delay = $delay;
		return $this;
	}

	public function getTrr(): int {
		return $this->trr;
	}

	public function setTrr(int $trr): Job {
		$this->trr = $trr;
		return $this;
	}

	public function getData() {
		return $this->data;
	}

}
