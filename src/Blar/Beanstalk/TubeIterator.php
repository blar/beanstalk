<?php

namespace Blar\Beanstalk;

use Iterator;

class TubeIterator implements Iterator {

    /**
     * @var \Blar\Beanstalk\Tube
     */
    private Tube $tube;

    private Job $job;

    public function __construct(Tube $tube) {
        $this->tube = $tube;
    }

    public function current(): Job {
        return $this->job;
    }

    public function next(): void {
        $this->job = $this->tube->reserveJob(1);
    }

    public function key(): int {
        return $this->job->getId();
    }

    public function valid(): bool {
        return $this->job !== NULL;
    }

    public function rewind(): void {
        $this->job = $this->tube->reserveJob(1);
    }

}
