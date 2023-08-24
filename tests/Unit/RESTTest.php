<?php declare(strict_types=1);

uses()->group('unit');

beforeEach(function()
{
    $this->rest = \Vaites\ApacheTika\Clients\REST::make('localhost')->setVersion(version());
});

describe('rest', function()
{
    it('cant set retries', function()
    {
        $this->rest->setRetries(7);

        expect($this->rest->getRetries())->toBe(7);
    });
});