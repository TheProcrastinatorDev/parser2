<?php

declare(strict_types=1);

use App\Services\Parser\Exceptions\ParserAlreadyRegisteredException;
use App\Services\Parser\Exceptions\ParserNotFoundException;
use App\Services\Parser\ParserManager;

beforeEach(function (): void {
    $this->manager = new ParserManager();
});

it('registers and resolves parser instances', function (): void {
    $this->manager->register('feeds', fn () => new DummyParser('feeds'));

    $parser = $this->manager->get('feeds');

    expect($parser)
        ->toBeInstanceOf(DummyParser::class)
        ->and($parser->name)
        ->toBe('feeds');
});

it('can determine whether a parser is registered', function (): void {
    $this->manager->register('feeds', fn () => new DummyParser('feeds'));

    expect($this->manager->has('feeds'))->toBeTrue()
        ->and($this->manager->has('reddit'))->toBeFalse();
});

it('returns all registered parser keys', function (): void {
    $this->manager->register('feeds', fn () => new DummyParser('feeds'));
    $this->manager->register('reddit', fn () => new DummyParser('reddit'));

    expect($this->manager->keys())->toBe(['feeds', 'reddit']);
});

it('throws an exception when requesting an unknown parser', function (): void {
    $this->manager->get('unknown');
})->throws(ParserNotFoundException::class);

it('prevents duplicate parser registrations', function (): void {
    $this->manager->register('feeds', fn () => new DummyParser('feeds'));
    $this->manager->register('feeds', fn () => new DummyParser('feeds'));
})->throws(ParserAlreadyRegisteredException::class);

it('registers parsers by class name', function (): void {
    $this->manager->registerClass('feeds', DummyParser::class);

    $parser = $this->manager->get('feeds');

    expect($parser)->toBeInstanceOf(DummyParser::class);
});

final class DummyParser
{
    public function __construct(public readonly string $name = 'dummy')
    {
    }
}
