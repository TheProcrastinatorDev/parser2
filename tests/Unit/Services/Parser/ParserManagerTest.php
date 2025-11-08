<?php

declare(strict_types=1);

use App\Exceptions\ParserNotFoundException;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\ParserManager;
use App\DTOs\Parser\ParseRequestDTO;

// Test parser implementations
class MockParser1 extends AbstractParser
{
    protected function doParse(ParseRequestDTO $request): array
    {
        return [['title' => 'Mock 1']];
    }

    protected function getName(): string
    {
        return 'mock1';
    }
}

class MockParser2 extends AbstractParser
{
    protected function doParse(ParseRequestDTO $request): array
    {
        return [['title' => 'Mock 2']];
    }

    protected function getName(): string
    {
        return 'mock2';
    }
}

uses(Tests\TestCase::class);

describe('ParserManager', function () {
    beforeEach(function () {
        $this->manager = new ParserManager();
    });

    it('registers parser with string key', function () {
        $parser = new MockParser1();

        $this->manager->register('test_parser', $parser);

        expect($this->manager->has('test_parser'))->toBeTrue();
    });

    it('gets parser by name returns correct instance', function () {
        $parser = new MockParser1();

        $this->manager->register('test_parser', $parser);
        $retrieved = $this->manager->get('test_parser');

        expect($retrieved)->toBe($parser)
            ->and($retrieved)->toBeInstanceOf(MockParser1::class);
    });

    it('lists all registered parsers', function () {
        $parser1 = new MockParser1();
        $parser2 = new MockParser2();

        $this->manager->register('parser1', $parser1);
        $this->manager->register('parser2', $parser2);

        $all = $this->manager->all();

        expect($all)->toBeArray()
            ->and($all)->toHaveCount(2)
            ->and($all)->toHaveKey('parser1')
            ->and($all)->toHaveKey('parser2');
    });

    it('returns parser names', function () {
        $parser1 = new MockParser1();
        $parser2 = new MockParser2();

        $this->manager->register('parser1', $parser1);
        $this->manager->register('parser2', $parser2);

        $names = $this->manager->names();

        expect($names)->toBe(['parser1', 'parser2']);
    });

    it('throws exception when parser not found', function () {
        expect(fn () => $this->manager->get('nonexistent'))
            ->toThrow(ParserNotFoundException::class);
    });

    it('throws exception on duplicate registration', function () {
        $parser1 = new MockParser1();
        $parser2 = new MockParser2();

        $this->manager->register('test', $parser1);

        expect(fn () => $this->manager->register('test', $parser2))
            ->toThrow(\Exception::class)
            ->and(fn () => $this->manager->register('test', $parser2))
            ->toThrow(\Exception::class, 'Parser "test" is already registered');
    });

    it('checks if parser exists', function () {
        $parser = new MockParser1();

        expect($this->manager->has('test'))->toBeFalse();

        $this->manager->register('test', $parser);

        expect($this->manager->has('test'))->toBeTrue();
    });

    it('returns parser count', function () {
        expect($this->manager->count())->toBe(0);

        $this->manager->register('parser1', new MockParser1());

        expect($this->manager->count())->toBe(1);

        $this->manager->register('parser2', new MockParser2());

        expect($this->manager->count())->toBe(2);
    });

    it('allows removing registered parser', function () {
        $parser = new MockParser1();
        $this->manager->register('test', $parser);

        expect($this->manager->has('test'))->toBeTrue();

        $this->manager->remove('test');

        expect($this->manager->has('test'))->toBeFalse();
    });

    it('returns parser details for listing', function () {
        $parser = new MockParser1();
        $this->manager->register('test_parser', $parser);

        $details = $this->manager->getDetails('test_parser');

        expect($details)->toBeArray()
            ->and($details)->toHaveKey('name')
            ->and($details['name'])->toBe('test_parser')
            ->and($details)->toHaveKey('class')
            ->and($details['class'])->toBe(MockParser1::class);
    });

    it('returns all parser details', function () {
        $this->manager->register('parser1', new MockParser1());
        $this->manager->register('parser2', new MockParser2());

        $allDetails = $this->manager->getAllDetails();

        expect($allDetails)->toHaveCount(2)
            ->and($allDetails[0])->toHaveKey('name')
            ->and($allDetails[0])->toHaveKey('class')
            ->and($allDetails[1])->toHaveKey('name')
            ->and($allDetails[1])->toHaveKey('class');
    });
});
