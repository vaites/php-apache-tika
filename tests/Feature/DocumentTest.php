<?php declare(strict_types=1);

uses()->group('feature');

beforeEach(function()
{
    $this->cli = \Vaites\ApacheTika\Clients\CLI::make(binary('app', version()))->setVersion(version());
    $this->rest = \Vaites\ApacheTika\Clients\REST::make('localhost')->setVersion(version());
});

describe('documents', function()
{
    test('can extract document text', function(string $client, string $sample)
    {
        $response = $this->$client->getText($sample);

        expect($response)->toBeString()->toContain('Zenonis est, inquam, hoc Stoici');
    })
    ->with('clients')->with('documents');
});