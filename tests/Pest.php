<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PiShift\Content\Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
