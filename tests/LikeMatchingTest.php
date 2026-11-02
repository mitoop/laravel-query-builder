<?php

namespace Mitoop\LaravelQueryBuilder\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mitoop\LaravelQueryBuilder\Tests\Fixtures\User;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;
use Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny;
use PHPUnit\Framework\Attributes\DataProvider;

class LikeMatchingTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->boolean('active')->default(true);
            $table->json('profile')->nullable();
        });
    }

    #[DataProvider('literalSearches')]
    public function test_like_matches_literal_characters_on_sqlite(string $search, string $literal, string $other): void
    {
        User::query()->insert([
            ['name' => $literal, 'email' => 'first@example.test'],
            ['name' => $other, 'email' => 'second@example.test'],
        ]);

        $ids = User::query()->filter(['search:name|like' => new Like], ['search' => $search])->pluck('id')->all();

        $this->assertSame([1], $ids);
    }

    #[DataProvider('literalSearches')]
    public function test_like_any_matches_each_column_without_bypassing_other_conditions(string $search, string $literal, string $other): void
    {
        User::query()->insert([
            ['name' => $literal, 'email' => 'first@example.test', 'active' => true],
            ['name' => 'second', 'email' => $literal, 'active' => true],
            ['name' => $other, 'email' => 'third@example.test', 'active' => true],
            ['name' => $literal, 'email' => 'fourth@example.test', 'active' => false],
        ]);

        $ids = User::query()->where('active', true)->filter([
            'search|like_any' => new LikeAny(['name', 'email']),
        ], ['search' => $search])->pluck('id')->all();

        $this->assertSame([1, 2], $ids);
    }

    #[DataProvider('literalSearches')]
    public function test_relation_searches_match_literal_characters(string $search, string $literal, string $other): void
    {
        User::query()->insert([
            ['name' => $literal, 'email' => 'first@example.test', 'manager_id' => null],
            ['name' => 'second', 'email' => 'second@example.test', 'manager_id' => 1],
            ['name' => $other, 'email' => 'third@example.test', 'manager_id' => null],
            ['name' => 'fourth', 'email' => 'fourth@example.test', 'manager_id' => 3],
        ]);

        $query = User::query()->filter([
            'search:manager$name|like' => new Like,
        ], ['search' => $search]);

        $this->assertSame([2], $query->pluck('id')->all());
    }

    public function test_explicit_like_patterns_keep_their_wildcards(): void
    {
        User::query()->insert([
            ['name' => 'Ada', 'email' => 'ada@example.test'],
            ['name' => 'Alan', 'email' => 'alan@example.test'],
        ]);

        $this->assertSame([1], User::query()->filter(['name|like'], ['name' => 'Ad%'])->pluck('id')->all());
    }

    public function test_sqlite_like_wraps_json_selectors(): void
    {
        User::query()->insert([
            ['name' => 'first', 'email' => 'first@example.test', 'profile' => json_encode(['nickname' => '100% literal'])],
            ['name' => 'second', 'email' => 'second@example.test', 'profile' => json_encode(['nickname' => '1000 literal'])],
        ]);

        $ids = User::query()->filter([
            'search:profile->nickname|like' => new Like,
        ], ['search' => '100%'])->pluck('id')->all();

        $this->assertSame([1], $ids);
    }

    /** @return array<string, array{string, string, string}> */
    public static function literalSearches(): array
    {
        return [
            'percent' => ['100%', '100% literal', '1000 literal'],
            'underscore' => ['a_b', 'a_b literal', 'axb literal'],
            'backslash' => ['path\\file', 'path\\file literal', 'pathfile literal'],
            'quoted wildcard' => ["%' OR 1=1 --", "prefix %' OR 1=1 -- suffix", 'ordinary'],
        ];
    }

    #[DataProvider('otherDrivers')]
    public function test_other_database_drivers_keep_their_native_like_sql(string $driver): void
    {
        config(['database.connections.other' => [
            'driver' => $driver,
            'database' => 'testing',
            'host' => '127.0.0.1',
            'username' => 'testing',
            'password' => '',
            'prefix' => '',
        ]]);

        foreach ([
            ['search:name|like' => new Like],
            ['search|like_any' => new LikeAny(['name'])],
        ] as $rules) {
            $builder = User::on('other')->filter($rules, ['search' => '100%']);

            $this->assertStringNotContainsString('escape', strtolower($builder->toSql()));
            $this->assertSame(['%100\\%%'], $builder->getBindings());
        }
    }

    /** @return array<string, array{string}> */
    public static function otherDrivers(): array
    {
        return ['mysql' => ['mysql'], 'pgsql' => ['pgsql']];
    }
}
